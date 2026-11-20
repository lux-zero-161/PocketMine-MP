<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\entity\Location;
use pocketmine\inventory\SimpleEntityInventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class ChestMinecart extends Minecart{
	private const TAG_ITEMS = "Items";

	private SimpleEntityInventory $inventory;

	public function __construct(Location $location, ?CompoundTag $nbt = null, ?Item $dropItem = null){
		$this->inventory = new SimpleEntityInventory($this, 27);
		parent::__construct($location, $nbt, $dropItem);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::CHEST_MINECART; }

	public function getName() : string{ return "Chest Minecart"; }

	public function getInventory() : SimpleEntityInventory{ return $this->inventory; }

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->inventory->clearAll();
		$items = $nbt->getListTag(self::TAG_ITEMS, CompoundTag::class);
		if($items !== null){
			$slot = 0;
			foreach($items->getValue() as $itemTag){
				if($slot >= $this->inventory->getSize()){
					break;
				}
				$item = Item::safeNbtDeserialize($itemTag, "Chest minecart item");
				if(!$item->isNull()){
					$this->inventory->setItem($slot++, $item);
				}
			}
		}
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$items = [];
		foreach($this->inventory->getContents() as $item){
			$items[] = $item->nbtSerialize();
		}
		$nbt->setTag(self::TAG_ITEMS, new ListTag($items, NBT::TAG_Compound));
		return $nbt;
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		$player->setCurrentWindow($this->inventory);
		return true;
	}

	public function getDrops() : array{
		$drop = $this->getDropItem();
		return [$drop ?? VanillaItems::CHEST_MINECART()];
	}
}
