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

use pocketmine\block\utils\AnyFacing;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;
use pocketmine\world\World;

class Observer extends Opaque implements AnyFacing{
	protected int $facing = Facing::DOWN;
	protected bool $powered = false;

	/**
	 * Tick at which each observer last switched its output off, used to ignore the block update caused by that write.
	 *
	 * @var \WeakMap<World, array<int, int>>|null
	 */
	private static ?\WeakMap $lastPowerOffTick = null;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
		$w->bool($this->powered);
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		Facing::validate($facing);
		$this->facing = $facing;
		return $this;
	}

	private function getBlockHash() : int{
		$position = $this->position;
		return World::blockHash($position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
	}

	public function onNearbyBlockChange() : void{
		$world = $this->position->getWorld();
		$map = self::$lastPowerOffTick ??= new \WeakMap();
		$key = $this->getBlockHash();
		$ticks = $map[$world] ?? null;
		if($ticks !== null && isset($ticks[$key])){
			if($world->getServer()->getTick() <= $ticks[$key]){
				//this update was caused by the observer switching itself off
				return;
			}
			unset($ticks[$key]);
			$map[$world] = $ticks;
		}

		if($this->powered){
			return;
		}
		$this->powered = true;
		$world->setBlock($this->position, $this);
		$world->scheduleDelayedBlockUpdate($this->position, 2);
	}

	public function onScheduledUpdate() : void{
		if($this->powered){
			$this->powered = false;
			$world = $this->position->getWorld();
			$map = self::$lastPowerOffTick ??= new \WeakMap();
			$ticks = $map[$world] ?? [];
			$ticks[$this->getBlockHash()] = $world->getServer()->getTick();
			$map[$world] = $ticks;
			$world->setBlock($this->position, $this);
		}
	}

	public function isPowered() : bool{ return $this->powered; }

	/** @return $this */
	public function setPowered(bool $powered) : self{
		$this->powered = $powered;
		return $this;
	}
}
