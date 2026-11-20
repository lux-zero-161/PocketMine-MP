<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\block\tile\Container;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use function count;
use function min;

class HopperMinecart extends Minecart{
	private const TAG_ITEMS = "Items";
	private const TRANSFER_INTERVAL = 8;

	private SimpleInventory $inventory;
	private int $transferCooldown = 0;

	public function __construct(\pocketmine\entity\Location $location, ?CompoundTag $nbt = null, ?Item $dropItem = null){
		$this->inventory = new SimpleInventory(5);
		parent::__construct($location, $nbt, $dropItem);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::HOPPER_MINECART; }

	public function getName() : string{ return "Hopper Minecart"; }

	public function getInventory() : SimpleInventory{ return $this->inventory; }

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$items = $nbt->getListTag(self::TAG_ITEMS, CompoundTag::class);
		if($items !== null){
			$slot = 0;
			foreach($items->getValue() as $itemTag){
				if($slot >= $this->inventory->getSize()){
					break;
				}
				$item = Item::safeNbtDeserialize($itemTag, "Hopper minecart item");
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
		$nbt->setTag(self::TAG_ITEMS, new ListTag($items, \pocketmine\nbt\NBT::TAG_Compound));
		return $nbt;
	}

	public function onUpdate(int $currentTick) : bool{
		if(--$this->transferCooldown <= 0){
			$this->transferCooldown = self::TRANSFER_INTERVAL;
			$this->collectItems();
			$this->pushItems();
		}
		return parent::onUpdate($currentTick);
	}

	private function collectItems() : void{
		$box = AxisAlignedBB::one()
			->expand(0.5, 1.0, 0.5)
			->offset($this->location->x, $this->location->y, $this->location->z);
		foreach($this->getWorld()->getNearbyEntities($box, $this) as $entity){
			if(!$entity instanceof ItemEntity || $entity->isClosed() || $entity->getPickupDelay() > 0){
				continue;
			}
			$item = $entity->getItem();
			if($item->isNull()){
				continue;
			}
			if($this->inventory->getAddableItemQuantity($item) < $item->getCount()){
				continue;
			}
			$leftover = $this->inventory->addItem(clone $item);
			if(count($leftover) === 0){
				$entity->flagForDespawn();
			}
		}
	}

	private function pushItems() : void{
		$target = $this->getWorld()->getTile($this->location->subtract(0, 0.5, 0));
		if(!$target instanceof Container){
			return;
		}
		$targetInventory = $target->getRealInventory();
		foreach($this->inventory->getContents() as $slot => $item){
			$amount = min($item->getCount(), $targetInventory->getAddableItemQuantity($item));
			if($amount <= 0){
				continue;
			}
			$leftover = $targetInventory->addItem((clone $item)->setCount($amount));
			if(count($leftover) > 0){
				continue;
			}
			if($amount >= $item->getCount()){
				$this->inventory->clear($slot);
			}else{
				$this->inventory->setItem($slot, (clone $item)->setCount($item->getCount() - $amount));
			}
			break;
		}
	}
}
