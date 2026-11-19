<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;

class ShelfMushroom extends Flowable implements HorizontalFacing{
	use HorizontalFacingTrait;
	use StaticSupportTrait;

	protected bool $grown = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->grown);
	}

	public function isGrown() : bool{ return $this->grown; }

	/** @return $this */
	public function setGrown(bool $grown) : self{
		$this->grown = $grown;
		return $this;
	}

	private function canBeSupportedAt(Block $block) : bool{
		return $block->getSide(Facing::DOWN)->getTypeId() !== BlockTypeIds::AIR;
	}
}
