<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\nbt\tag\CompoundTag;
use function max;
use function min;

class CalibratedSculkSensor extends Tile{
	private const TAG_TUNED_FREQUENCY = "frequency";
	private const TAG_LAST_FREQUENCY = "lastFrequency";
	public const MAX_FREQUENCY = 35;

	private int $tunedFrequency = 0;
	private int $lastFrequency = 0;

	public function readSaveData(CompoundTag $nbt) : void{
		$this->tunedFrequency = max(0, min(self::MAX_FREQUENCY, $nbt->getInt(self::TAG_TUNED_FREQUENCY, 0)));
		$this->lastFrequency = max(0, min(self::MAX_FREQUENCY, $nbt->getInt(self::TAG_LAST_FREQUENCY, 0)));
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setInt(self::TAG_TUNED_FREQUENCY, $this->tunedFrequency);
		$nbt->setInt(self::TAG_LAST_FREQUENCY, $this->lastFrequency);
	}

	/**
	 * 0 means "react to every frequency".
	 */
	public function getTunedFrequency() : int{ return $this->tunedFrequency; }

	public function setTunedFrequency(int $frequency) : void{
		if($frequency < 0 || $frequency > self::MAX_FREQUENCY){
			throw new \InvalidArgumentException("Frequency must be in range 0 ... " . self::MAX_FREQUENCY);
		}
		$this->tunedFrequency = $frequency;
	}

	public function getLastFrequency() : int{ return $this->lastFrequency; }

	public function setLastFrequency(int $frequency) : void{
		if($frequency < 0 || $frequency > self::MAX_FREQUENCY){
			throw new \InvalidArgumentException("Frequency must be in range 0 ... " . self::MAX_FREQUENCY);
		}
		$this->lastFrequency = $frequency;
	}
}
