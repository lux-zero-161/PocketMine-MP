<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\ai\PathFinder;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\world\World;
use function mt_rand;

class SculkShrieker extends Transparent{
	private const WARNINGS_TO_SUMMON = 4;
	private const WARNING_TIMEOUT = 200;

	/** @var \WeakMap<World, array<int, array{int, int}>>|null */
	private static ?\WeakMap $warnings = null;

	protected bool $active = false;
	protected bool $canSummon = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->bool($this->active);
		$w->bool($this->canSummon);
	}

	public function isActive() : bool{ return $this->active; }

	/** @return $this */
	public function setActive(bool $active) : self{
		$this->active = $active;
		return $this;
	}

	public function canSummon() : bool{ return $this->canSummon; }

	public function receiveVibration(int $frequency, ?Entity $source = null) : void{
		if(!$source instanceof Player || $source->isSpectator()){
			return;
		}

		$world = $this->position->getWorld();
		$tick = $world->getServer()->getTick();
		self::$warnings ??= new \WeakMap();
		$entries = self::$warnings[$world] ?? [];
		$key = World::blockHash($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ());
		[$count, $lastTick] = $entries[$key] ?? [0, 0];
		if($tick - $lastTick > self::WARNING_TIMEOUT){
			$count = 0;
		}
		$entries[$key] = [$count + 1, $tick];
		self::$warnings[$world] = $entries;

		if(!$this->active){
			$this->active = true;
			$world->setBlock($this->position, $this);
		}
		$world->scheduleDelayedBlockUpdate($this->position, 40);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$key = World::blockHash($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ());
		$entry = self::$warnings !== null ? (self::$warnings[$world][$key] ?? null) : null;

		if($this->active){
			$this->active = false;
			$world->setBlock($this->position, $this);
		}

		if($entry !== null && $entry[0] >= self::WARNINGS_TO_SUMMON && $this->canSummon && $this->hasNearbyPlayer(8)){
			$this->canSummon = false;
			$world->setBlock($this->position, $this);
			$this->summonWarden();
			if(self::$warnings !== null){
				$entries = self::$warnings[$world] ?? [];
				$entries[$key] = [0, $world->getServer()->getTick()];
				self::$warnings[$world] = $entries;
			}
		}
	}

	private function hasNearbyPlayer(float $radius) : bool{
		foreach($this->position->getWorld()->getNearbyEntities(\pocketmine\math\AxisAlignedBB::one()->expand($radius, $radius, $radius)->offset($this->position->x, $this->position->y, $this->position->z)) as $entity){
			if($entity instanceof Player && $entity->isAlive()){
				return true;
			}
		}
		return false;
	}

	private function summonWarden() : void{
		$world = $this->position->getWorld();
		for($i = 0; $i < 12; ++$i){
			$x = $this->position->getFloorX() + mt_rand(-4, 4);
			$y = $this->position->getFloorY() + mt_rand(-1, 2);
			$z = $this->position->getFloorZ() + mt_rand(-4, 4);
			if(PathFinder::canStand($world, $x, $y, $z)){
				$warden = EntityFactory::getInstance()->createEntity($world, "minecraft:warden", Location::fromObject(new \pocketmine\math\Vector3($x + 0.5, $y, $z + 0.5), $world));
				$warden?->spawnToAll();
				return;
			}
		}
	}

	/** @return $this */
	public function setCanSummon(bool $canSummon) : self{
		$this->canSummon = $canSummon;
		return $this;
	}
}
