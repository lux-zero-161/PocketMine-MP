<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Shelf as TileShelf;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Shelf extends Transparent implements HorizontalFacing{
	use HorizontalFacingTrait;
	use FacesOppositePlacingPlayerTrait;

	public const MAX_POWERED_SHELF_TYPE = 3;

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

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player !== null){
			$tile = $this->position->getWorld()->getTile($this->position);
			if($tile instanceof TileShelf){
				$player->setCurrentWindow($tile->getInventory());
				return true;
			}
		}
		return false;
	}
}
