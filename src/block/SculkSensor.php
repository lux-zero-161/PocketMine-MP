<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\AnalogRedstoneSignalEmitter;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\world\World;
use function max;

class SculkSensor extends Opaque implements AnalogRedstoneSignalEmitter{
	public const MAX_PHASE = 2;

	protected int $phase = 0;

	/**
	 * Current signal strengths and the tick on which self-triggered updates should be ignored.
	 *
	 * @var \WeakMap<World, array<int, array{int, int}>>|null
	 */
	private static ?\WeakMap $state = null;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_PHASE, $this->phase);
	}

	public function getPhase() : int{ return $this->phase; }

	/** @return $this */
	public function setPhase(int $phase) : self{
		if($phase < 0 || $phase > self::MAX_PHASE){
			throw new \InvalidArgumentException("Phase must be in range 0 ... " . self::MAX_PHASE);
		}
		$this->phase = $phase;
		return $this;
	}

	public function getOutputSignalStrength() : int{
		$world = $this->position->world;
		if($world === null || self::$state === null){
			return 0;
		}
		$entry = self::$state[$world][$this->getBlockHash()] ?? null;
		return $entry[0] ?? 0;
	}

	/**
	 * The signal is derived from nearby vibrations and cannot be assigned directly.
	 *
	 * @return $this
	 */
	public function setOutputSignalStrength(int $signalStrength) : self{
		return $this;
	}

	public function onNearbyBlockChange() : void{
		$world = $this->position->getWorld();
		$map = self::$state ??= new \WeakMap();
		$entries = $map[$world] ?? [];
		$key = $this->getBlockHash();
		$entry = $entries[$key] ?? null;
		if($entry !== null && $world->getServer()->getTick() <= $entry[1]){
			return;
		}

		$entries[$key] = [15, $world->getServer()->getTick()];
		$map[$world] = $entries;
		$this->phase = 1;
		$world->setBlock($this->position, $this);
		$world->notifyNeighbourBlockUpdate($this->position);
		$world->scheduleDelayedBlockUpdate($this->position, 20);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$map = self::$state ??= new \WeakMap();
		$entries = $map[$world] ?? [];
		$key = $this->getBlockHash();
		$entries[$key] = [0, $world->getServer()->getTick()];
		$map[$world] = $entries;
		$this->phase = 0;
		$world->setBlock($this->position, $this);
		$world->notifyNeighbourBlockUpdate($this->position);
	}

	private function getBlockHash() : int{
		return World::blockHash($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ());
	}
}
