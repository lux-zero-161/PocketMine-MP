<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\block\inventory\CrafterInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Crafter extends Tile implements Container{
	use ContainerTrait;

	private CrafterInventory $inventory;

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
		$this->inventory = new CrafterInventory($this->position);
	}

	public function getInventory() : CrafterInventory{
		return $this->inventory;
	}

	public function getRealInventory() : CrafterInventory{
		return $this->inventory;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveItems($nbt);
	}
}
