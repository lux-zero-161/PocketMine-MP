<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\CalibratedSculkSensor as TileCalibratedSculkSensor;
use pocketmine\block\utils\AnalogRedstoneSignalEmitter;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use function floor;
use function max;
use function sqrt;

class SculkSensor extends Opaque implements AnalogRedstoneSignalEmitter{
	public const MAX_PHASE = 2;

	//Vanilla-style vibration frequencies (subset used by the implemented game events)
	public const FREQUENCY_STEP = 1;
	public const FREQUENCY_SWIM = 2;
	public const FREQUENCY_FLAP = 3;
	public const FREQUENCY_PROJECTILE_LAND = 4;
	public const FREQUENCY_ITEM_INTERACT_START = 5;
	public const FREQUENCY_ITEM_INTERACT_FINISH = 6;
	public const FREQUENCY_PROJECTILE_SHOOT = 7;
	public const FREQUENCY_ENTITY_TELEPORT = 11;
	public const FREQUENCY_BLOCK_DESTROY = 12;
	public const FREQUENCY_BLOCK_PLACE = 13;
	public const FREQUENCY_BLOCK_CHANGE = 14;
	public const FREQUENCY_BLOCK_OPEN = 15;
	public const FREQUENCY_BLOCK_CLOSE = 16;
	public const FREQUENCY_BLOCK_ATTACH = 17;
	public const FREQUENCY_BLOCK_DETACH = 18;
	public const FREQUENCY_PRIME_FUSE = 26;
	public const FREQUENCY_EXPLODE = 27;
	public const FREQUENCY_NOTE_BLOCK_PLAY = 30;
	public const FREQUENCY_SPLASH = 31;
	public const FREQUENCY_FLUID_PICKUP = 32;
	public const FREQUENCY_FLUID_PLACE = 33;
	public const FREQUENCY_ENTITY_DIE = 34;
	public const FREQUENCY_ENTITY_HURT = 35;

	public const MAX_VIBRATION_DISTANCE = 8;

	protected int $phase = 0;

	/**
	 * Current signal strengths and the tick on which self-triggered updates should be ignored.
	 *
	 * @var \WeakMap<World, array<int, array{int, int}>>|null
	 */
	private static ?\WeakMap $state = null;

	/**
	 * Prevents phase updates from re-triggering every adjacent sensor.
	 */
	private static bool $updating = false;

	/**
	 * State IDs of all sculk sensor and shrieker blocks, built lazily to avoid creating Block objects during scans.
	 *
	 * @var array<int, true>|null
	 */
	private static ?array $sensorStateIds = null;

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

	/**
	 * Broadcasts a vibration to nearby sculk sensors and shriekers.
	 *
	 * @param int $frequency one of the FREQUENCY_* constants
	 */
	public static function submitVibration(World $world, Vector3 $position, int $frequency, ?Entity $source = null) : void{
		$centerX = $position->getFloorX();
		$centerY = $position->getFloorY();
		$centerZ = $position->getFloorZ();

		self::$sensorStateIds ??= self::buildSensorStateIds();
		$registry = RuntimeBlockStateRegistry::getInstance();

		for($dx = -self::MAX_VIBRATION_DISTANCE; $dx <= self::MAX_VIBRATION_DISTANCE; ++$dx){
			for($dy = -self::MAX_VIBRATION_DISTANCE; $dy <= self::MAX_VIBRATION_DISTANCE; ++$dy){
				for($dz = -self::MAX_VIBRATION_DISTANCE; $dz <= self::MAX_VIBRATION_DISTANCE; ++$dz){
					$distanceSquared = $dx * $dx + $dy * $dy + $dz * $dz;
					if($distanceSquared > self::MAX_VIBRATION_DISTANCE * self::MAX_VIBRATION_DISTANCE){
						continue;
					}
					$x = $centerX + $dx;
					$y = $centerY + $dy;
					$z = $centerZ + $dz;
					if(!$world->isInWorld($x, $y, $z)){
						continue;
					}
					$chunk = $world->getChunk($x >> \pocketmine\world\format\Chunk::COORD_BIT_SIZE, $z >> \pocketmine\world\format\Chunk::COORD_BIT_SIZE);
					if($chunk === null){
						continue;
					}
					$stateId = $chunk->getBlockStateId($x & \pocketmine\world\format\Chunk::COORD_MASK, $y, $z & \pocketmine\world\format\Chunk::COORD_MASK);
					if(!isset(self::$sensorStateIds[$stateId])){
						continue;
					}
					$block = $registry->fromStateId($stateId);
					if($block instanceof self){
						$block->receiveVibration($frequency, max(1, 15 - (int) floor(sqrt($distanceSquared))), $source);
					}elseif($block instanceof SculkShrieker){
						$block->receiveVibration($frequency, $source);
					}
				}
			}
		}
	}

	/**
	 * Called when a vibration reaches this sensor.
	 *
	 * @param int $signalStrength 1-15, derived from the distance to the vibration source
	 */
	public function receiveVibration(int $frequency, int $signalStrength, ?Entity $source = null) : void{
		if($this instanceof CalibratedSculkSensor){
			$tile = $this->position->getWorld()->getTile($this->position);
			if($tile instanceof TileCalibratedSculkSensor){
				$tile->setLastFrequency($frequency);
				$tuned = $tile->getTunedFrequency();
				if($tuned !== 0 && $tuned !== $frequency){
					return;
				}
			}
		}

		$world = $this->position->getWorld();
		$key = $this->getBlockHash();
		$entries = self::$state[$world] ?? [];
		$current = $entries[$key] ?? null;
		if($current !== null && $current[0] >= $signalStrength && $current[1] > $world->getServer()->getTick()){
			return;
		}

		$entries[$key] = [$signalStrength, $world->getServer()->getTick() + 20];
		self::$state ??= new \WeakMap();
		self::$state[$world] = $entries;

		if($this->phase !== 1){
			$this->phase = 1;
			self::$updating = true;
			$world->setBlock($this->position, $this);
			self::$updating = false;
			$world->notifyNeighbourBlockUpdate($this->position);
		}
		$world->scheduleDelayedBlockUpdate($this->position, 20);
	}

	public function onNearbyBlockChange() : void{
		if(self::$updating){
			return;
		}

		$world = $this->position->getWorld();
		$map = self::$state ??= new \WeakMap();
		$entries = $map[$world] ?? [];
		$key = $this->getBlockHash();
		$entry = $entries[$key] ?? null;
		if($entry !== null && $world->getServer()->getTick() <= $entry[1]){
			return;
		}

		$this->receiveVibration(self::FREQUENCY_BLOCK_CHANGE, 15);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$map = self::$state ??= new \WeakMap();
		$entries = $map[$world] ?? [];
		$key = $this->getBlockHash();
		$entries[$key] = [0, $world->getServer()->getTick()];
		$map[$world] = $entries;
		$this->phase = 0;
		self::$updating = true;
		$world->setBlock($this->position, $this);
		self::$updating = false;
		$world->notifyNeighbourBlockUpdate($this->position);
	}

	/**
	 * @return array<int, true>
	 */
	private static function buildSensorStateIds() : array{
		$result = [];
		foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $stateId => $state){
			if($state instanceof self || $state instanceof SculkShrieker){
				$result[$stateId] = true;
			}
		}
		return $result;
	}

	private function getBlockHash() : int{
		return World::blockHash($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ());
	}
}
