<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\HangingSign as TileHangingSign;
use pocketmine\data\runtime\RuntimeDataDescriber;

class PoplarHangingSign extends Transparent{

	protected bool $attached = false;
	protected int $facing = 0;
	protected int $groundSignDirection = 0;
	protected bool $hanging = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->bool($this->attached);
		$w->facing($this->facing);
		$w->boundedIntAuto(0, 15, $this->groundSignDirection);
		$w->bool($this->hanging);
	}

	public function isAttached() : bool{ return $this->attached; }
	public function setAttached(bool $v) : self{ $this->attached = $v; return $this; }
	public function getFacing() : int{ return $this->facing; }
	public function setFacing(int $v) : self{ $this->facing = $v; return $this; }
	public function getGroundSignDirection() : int{ return $this->groundSignDirection; }
	public function setGroundSignDirection(int $v) : self{ $this->groundSignDirection = $v; return $this; }
	public function isHanging() : bool{ return $this->hanging; }
	public function setHanging(bool $v) : self{ $this->hanging = $v; return $this; }
}
