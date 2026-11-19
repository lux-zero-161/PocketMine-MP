<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Shelf as TileShelf;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Shelf extends Transparent implements HorizontalFacing{
	use HorizontalFacingTrait;
	use FacesOppositePlacingPlayerTrait;

	public const MAX_POWERED_SHELF_TYPE = 3;
	public const SLOT_COUNT = 3;

	protected bool $powered = false;
	protected int $poweredShelfType = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->powered);
		$w->boundedIntAuto(0, self::MAX_POWERED_SHELF_TYPE, $this->poweredShelfType);
	}

	public function isPowered() : bool{ return $this->powered; }

	/** @return $this */
	public function setPowered(bool $powered) : self{
		$this->powered = $powered;
		return $this;
	}

	public function getPoweredShelfType() : int{ return $this->poweredShelfType; }

	/** @return $this */
	public function setPoweredShelfType(int $type) : self{
		if($type < 0 || $type > self::MAX_POWERED_SHELF_TYPE){
			throw new \InvalidArgumentException("Powered shelf type must be in range 0 ... " . self::MAX_POWERED_SHELF_TYPE);
		}
		$this->poweredShelfType = $type;
		return $this;
	}

	public function onNearbyBlockChange() : void{
		$powered = RedstonePowerHelper::isPowered($this);
		if($powered !== $this->powered){
			$this->powered = $powered;
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	/**
	 * Returns the comparator signal for the occupied slots.
	 *
	 * The left, middle and right slot are worth 1, 2 and 4 signal strength respectively.
	 */
	public function getComparatorOutput() : int{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileShelf){
			return 0;
		}

		$output = 0;
		foreach([1, 2, 4] as $slot => $weight){
			if(!$tile->getInventory()->getItem($slot)->isNull()){
				$output += $weight;
			}
		}
		return $output;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileShelf){
			return false;
		}

		$inventory = $tile->getInventory();
		if(!$item->isNull()){
			for($slot = 0; $slot < self::SLOT_COUNT; $slot++){
				if($inventory->getItem($slot)->isNull()){
					$inventory->setItem($slot, clone $item);
					$item->pop($item->getCount());
					return true;
				}
			}
			return false;
		}

		for($slot = self::SLOT_COUNT - 1; $slot >= 0; $slot--){
			$stored = $inventory->getItem($slot);
			if(!$stored->isNull()){
				$returnedItems[] = $stored;
				$inventory->clear($slot);
				return true;
			}
		}
		return false;
	}
}
