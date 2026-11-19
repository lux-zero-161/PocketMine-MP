<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;

class StrawBed extends Opaque implements HorizontalFacing{
	use HorizontalFacingTrait;

	protected bool $head = false;
	protected bool $occupied = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->head);
		$w->bool($this->occupied);
	}

	public function isHead() : bool{ return $this->head; }

	/** @return $this */
	public function setHead(bool $head) : self{
		$this->head = $head;
		return $this;
	}

	public function isOccupied() : bool{ return $this->occupied; }

	/** @return $this */
	public function setOccupied(bool $occupied) : self{
		$this->occupied = $occupied;
		return $this;
	}
}
