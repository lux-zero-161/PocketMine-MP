<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\map\MapManager;

class EmptyMap extends Item{

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		$map = MapManager::createMap($player->getWorld(), $player->getPosition());
		$filled = VanillaItems::FILLED_MAP();
		$filled->setMapId($map->getId());
		$filled->setCustomName("Map");

		$this->pop();
		if($this->isNull()){
			$player->getInventory()->setItemInHand($filled);
		}else{
			$player->getInventory()->setItemInHand($this);
			$player->getInventory()->addItem($filled);
		}

		MapManager::sendMap($player, $map->getId());
		return ItemUseResult::SUCCESS;
	}
}
