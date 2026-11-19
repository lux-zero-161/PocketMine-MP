<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_|  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\DripstoneThickness;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class PointedDripstone extends Transparent{
	protected DripstoneThickness $thickness = DripstoneThickness::TIP;
	protected bool $hanging = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->thickness);
		$w->bool($this->hanging);
	}

	public function getThickness() : DripstoneThickness{ return $this->thickness; }

	/** @return $this */
	public function setThickness(DripstoneThickness $thickness) : self{
		$this->thickness = $thickness;
		return $this;
	}

	public function isHanging() : bool{ return $this->hanging; }

	/** @return $this */
	public function setHanging(bool $hanging) : self{
		$this->hanging = $hanging;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		$width = match($this->thickness){
			DripstoneThickness::TIP => 2 / 16,
			DripstoneThickness::FRUSTUM => 4 / 16,
			DripstoneThickness::MIDDLE => 6 / 16,
			DripstoneThickness::BASE => 8 / 16,
			DripstoneThickness::MERGE => 1
		};
		$height = $this->thickness === DripstoneThickness::MERGE ? 1 : 6 / 16;

		$box = AxisAlignedBB::one()
			->contract(($x = (1 - $width) / 2), 0, $x)
			->trim($this->hanging ? Facing::DOWN : Facing::UP, 1 - $height);
		return [$box];
	}
}
