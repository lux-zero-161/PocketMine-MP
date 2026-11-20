<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\entity\Location;
use pocketmine\inventory\SimpleEntityInventory;
use pocketmine\item\BoatType;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class ChestBoat extends Boat{
	private const TAG_ITEMS = "Items";

	private SimpleEntityInventory $inventory;

	public function __construct(Location $location, ?CompoundTag $nbt = null, BoatType $boatType = BoatType::OAK, ?Item $dropItem = null){
		$this->inventory = new SimpleEntityInventory($this, 27);
		parent::__construct($location, $nbt, $boatType, $dropItem);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::CHEST_BOAT; }

	public function getName() : string{ return "Chest Boat"; }

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
				$item = Item::safeNbtDeserialize($itemTag, "Chest boat item");
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
		if($player->isSneaking()){
			$player->setCurrentWindow($this->inventory);
			return true;
		}
		return parent::onInteract($player, $clickPos);
	}

	/**
	 * @return Item[]
	 */
	public function getDrops() : array{
		return [$this->getChestBoatItem()];
	}

	private function getChestBoatItem() : Item{
		return match($this->getBoatType()){
			BoatType::SPRUCE => VanillaItems::SPRUCE_CHEST_BOAT(),
			BoatType::BIRCH => VanillaItems::BIRCH_CHEST_BOAT(),
			BoatType::JUNGLE => VanillaItems::JUNGLE_CHEST_BOAT(),
			BoatType::ACACIA => VanillaItems::ACACIA_CHEST_BOAT(),
			BoatType::DARK_OAK => VanillaItems::DARK_OAK_CHEST_BOAT(),
			BoatType::MANGROVE => VanillaItems::MANGROVE_CHEST_BOAT(),
			BoatType::CHERRY => VanillaItems::CHERRY_CHEST_BOAT(),
			BoatType::PALE_OAK => VanillaItems::PALE_OAK_CHEST_BOAT(),
			BoatType::POPLAR => VanillaItems::POPLAR_CHEST_BOAT(),
			default => VanillaItems::OAK_CHEST_BOAT()
		};
	}
}
