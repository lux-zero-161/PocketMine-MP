<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

class Beehive extends Tile{
	private const TAG_BEES = "Bees";

	private ?ListTag $bees = null;

	public function readSaveData(CompoundTag $nbt) : void{
		$this->bees = $nbt->getListTag(self::TAG_BEES);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		if($this->bees !== null){
			$nbt->setTag(self::TAG_BEES, $this->bees);
		}
	}
}
