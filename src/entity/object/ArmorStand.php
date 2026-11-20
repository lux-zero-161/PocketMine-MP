<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class ArmorStand extends Living{

	public static function getNetworkTypeId() : string{ return EntityIds::ARMOR_STAND; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.975, 0.5);
	}

	public function getName() : string{
		return "Armor Stand";
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		$item = $player->getInventory()->getItemInHand();
		if($item instanceof Armor){
			$slot = $item->getArmorSlot();
			$old = $this->armorInventory->getItem($slot);
			$this->armorInventory->setItem($slot, (clone $item)->setCount(1));
			$item->pop();

			if(!$old->isNull()){
				if($player->getInventory()->canAddItem($old)){
					$player->getInventory()->addItem($old);
				}else{
					$player->getWorld()->dropItem($this->location, $old);
				}
			}
			return true;
		}

		return false;
	}

	public function getDrops() : array{
		$drops = parent::getDrops();
		$drops[] = VanillaItems::ARMOR_STAND();
		foreach($this->armorInventory->getContents() as $item){
			$drops[] = $item;
		}
		return $drops;
	}

	public function getXpDropAmount() : int{
		return 0;
	}
}
