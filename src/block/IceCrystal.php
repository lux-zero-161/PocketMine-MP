<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\AnyFacing;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;

class IceCrystal extends Opaque implements AnyFacing{
	protected int $facing = Facing::DOWN;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		Facing::validate($facing);
		$this->facing = $facing;
		return $this;
	}

	public function getLightLevel() : int{
		return 4;
	}
}
