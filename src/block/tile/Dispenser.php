<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\inventory\SimpleInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Dispenser extends Tile implements Container{
	use ContainerTrait;

	private SimpleInventory $inventory;

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
		$this->inventory = new SimpleInventory(9);
	}

	public function getInventory() : SimpleInventory{ return $this->inventory; }
	public function getRealInventory() : SimpleInventory{ return $this->inventory; }

	public function readSaveData(CompoundTag $nbt) : void{
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveItems($nbt);
	}
}
