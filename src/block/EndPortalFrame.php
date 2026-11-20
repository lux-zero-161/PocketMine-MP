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

use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class EndPortalFrame extends Opaque implements HorizontalFacing{
	use FacesOppositePlacingPlayerTrait;

	protected bool $eye = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->eye);
	}

	public function hasEye() : bool{ return $this->eye; }

	/** @return $this */
	public function setEye(bool $eye) : self{
		$this->eye = $eye;
		return $this;
	}

	public function getLightLevel() : int{
		return 1;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if(!$this->eye && $item->getTypeId() === ItemTypeIds::ENDER_EYE){
			$this->eye = true;
			$this->position->getWorld()->setBlock($this->position, $this);
			$item->pop();
			$this->tryActivatePortal();
			return true;
		}

		return false;
	}

	/**
	 * Checks the 12 surrounding frames and fills the 3x3 interior when all of them have eyes.
	 */
	private function tryActivatePortal() : bool{
		$world = $this->position->getWorld();
		$y = $this->position->getFloorY();
		$x = $this->position->getFloorX();
		$z = $this->position->getFloorZ();

		for($offsetX = -2; $offsetX <= 2; ++$offsetX){
			for($offsetZ = -2; $offsetZ <= 2; ++$offsetZ){
				$centerX = $x + $offsetX;
				$centerZ = $z + $offsetZ;
				if(!$this->isRingCompleteAt($centerX, $y, $centerZ)){
					continue;
				}
				for($dx = -1; $dx <= 1; ++$dx){
					for($dz = -1; $dz <= 1; ++$dz){
						$world->setBlockAt($centerX + $dx, $y, $centerZ + $dz, VanillaBlocks::END_PORTAL());
					}
				}
				return true;
			}
		}

		return false;
	}

	private function isRingCompleteAt(int $centerX, int $y, int $centerZ) : bool{
		$world = $this->position->getWorld();
		for($offset = -1; $offset <= 1; ++$offset){
			foreach([[$offset, -2], [$offset, 2], [-2, $offset], [2, $offset]] as [$dx, $dz]){
				$block = $world->getBlockAt($centerX + $dx, $y, $centerZ + $dz);
				if(!$block instanceof EndPortalFrame || !$block->hasEye()){
					return false;
				}
			}
		}

		for($dx = -1; $dx <= 1; ++$dx){
			for($dz = -1; $dz <= 1; ++$dz){
				if(!$world->getBlockAt($centerX + $dx, $y, $centerZ + $dz)->canBeReplaced()){
					return false;
				}
			}
		}

		return true;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::UP, 3 / 16)];
	}
}
