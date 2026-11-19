<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\data\runtime\RuntimeDataDescriber;

class Icicle extends PointedDripstone{
	protected bool $attached = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		parent::describeBlockOnlyState($w);
		$w->bool($this->attached);
	}

	public function isAttached() : bool{ return $this->attached; }

	/** @return $this */
	public function setAttached(bool $attached) : self{
		$this->attached = $attached;
		return $this;
	}
}
