<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
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

use pocketmine\data\runtime\RuntimeDataDescriber;

class Sponge extends Opaque{
	protected bool $wet = false;

	public function describeBlockItemState(RuntimeDataDescriber $w) : void{
		$w->bool($this->wet);
	}

	public function isWet() : bool{ return $this->wet; }

	/** @return $this */
	public function setWet(bool $wet) : self{
		$this->wet = $wet;
		return $this;
	}

	public function onPostPlace() : void{
		$this->absorbWater();
	}

	public function onNearbyBlockChange() : void{
		if(!$this->wet){
			$this->absorbWater();
		}
	}

	/**
	 * Absorbs nearby water blocks and turns this sponge into a wet sponge.
	 *
	 * TODO: wet sponges should dry when placed in the nether
	 */
	private function absorbWater() : void{
		$world = $this->position->getWorld();
		$absorbed = false;

		$x = $this->position->getFloorX();
		$y = $this->position->getFloorY();
		$z = $this->position->getFloorZ();
		for($dx = -2; $dx <= 2; ++$dx){
			for($dy = -2; $dy <= 2; ++$dy){
				for($dz = -2; $dz <= 2; ++$dz){
					if($world->getBlockAt($x + $dx, $y + $dy, $z + $dz) instanceof Water){
						$world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::AIR());
						$absorbed = true;
					}
				}
			}
		}

		if($absorbed){
			$this->wet = true;
			$world->setBlock($this->position, $this);
		}
	}
}
