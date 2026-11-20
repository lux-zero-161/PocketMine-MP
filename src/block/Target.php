<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\AnalogRedstoneSignalEmitter;
use pocketmine\entity\projectile\Projectile;
use pocketmine\item\Item;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\player\Player;
use pocketmine\world\World;
use function abs;
use function ceil;
use function max;
use function min;

class Target extends Opaque implements AnalogRedstoneSignalEmitter{
	public const MAX_SIGNAL = 15;

	/**
	 * Signal strength and remaining ticks, keyed by world and block hash.
	 *
	 * @var \WeakMap<World, array<int, array{int, int}>>|null
	 */
	private static ?\WeakMap $signals = null;

	public function getOutputSignalStrength() : int{
		$world = $this->position->world;
		if($world === null || self::$signals === null || !isset(self::$signals[$world])){
			return 0;
		}
		$state = self::$signals[$world][World::blockHash($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ())] ?? null;
		return $state[0] ?? 0;
	}

	/**
	 * The signal is derived from the last projectile hit and cannot be assigned directly.
	 *
	 * @return $this
	 */
	public function setOutputSignalStrength(int $signalStrength) : self{
		return $this;
	}

	public function onProjectileHit(Projectile $projectile, RayTraceResult $hitResult) : void{
		$hit = $hitResult->getHitVector();
		$distanceX = abs($hit->x - $hit->getFloorX() - 0.5);
		$distanceY = abs($hit->y - $hit->getFloorY() - 0.5);
		$distanceZ = abs($hit->z - $hit->getFloorZ() - 0.5);

		$distance = match(Facing::axis($hitResult->getHitFace())){
			Axis::Y => max($distanceX, $distanceZ),
			Axis::Z => max($distanceX, $distanceY),
			default => max($distanceY, $distanceZ)
		};

		$signal = (int) min(self::MAX_SIGNAL, max(1, ceil(self::MAX_SIGNAL * ((0.5 - $distance) / 0.5))));
		$duration = $projectile instanceof \pocketmine\entity\projectile\Arrow || $projectile instanceof \pocketmine\entity\projectile\Trident ? 20 : 8;

		$world = $this->position->getWorld();
		$map = self::$signals ??= new \WeakMap();
		$entries = $map[$world] ?? [];
		$entries[$this->getBlockHash()] = [$signal, $duration];
		$map[$world] = $entries;

		$world->notifyNeighbourBlockUpdate($this->position);
		$world->scheduleDelayedBlockUpdate($this->position, 1);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$map = self::$signals ??= new \WeakMap();
		$entries = $map[$world] ?? [];
		$key = $this->getBlockHash();
		if(!isset($entries[$key])){
			return;
		}

		if(--$entries[$key][1] <= 0){
			unset($entries[$key]);
		}else{
			$world->scheduleDelayedBlockUpdate($this->position, 1);
		}
		$map[$world] = $entries;
		$world->notifyNeighbourBlockUpdate($this->position);
	}

	public function onBreak(Item $item, ?Player $player = null, array &$returnedItems = []) : bool{
		if(self::$signals !== null){
			$world = $this->position->getWorld();
			$entries = self::$signals[$world] ?? [];
			unset($entries[$this->getBlockHash()]);
			self::$signals[$world] = $entries;
		}
		return parent::onBreak($item, $player, $returnedItems);
	}

	private function getBlockHash() : int{
		$position = $this->position;
		return World::blockHash($position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
	}
}
