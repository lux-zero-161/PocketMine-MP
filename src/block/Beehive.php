<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Beehive as TileBeehive;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;

class Beehive extends Opaque{
	public const MAX_HONEY_LEVEL = 5;
	public const MAX_DIRECTION = 3;

	protected int $honeyLevel = 0;
	protected int $direction = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_DIRECTION, $this->direction);
		$w->boundedIntAuto(0, self::MAX_HONEY_LEVEL, $this->honeyLevel);
	}

	public function getHoneyLevel() : int{ return $this->honeyLevel; }

	/** @return $this */
	public function setHoneyLevel(int $level) : self{
		if($level < 0 || $level > self::MAX_HONEY_LEVEL){
			throw new \InvalidArgumentException("Honey level must be in range 0 ... " . self::MAX_HONEY_LEVEL);
		}
		$this->honeyLevel = $level;
		return $this;
	}

	public function getDirection() : int{ return $this->direction; }

	/** @return $this */
	public function setDirection(int $direction) : self{
		if($direction < 0 || $direction > self::MAX_DIRECTION){
			throw new \InvalidArgumentException("Direction must be in range 0 ... " . self::MAX_DIRECTION);
		}
		$this->direction = $direction;
		return $this;
	}
}
