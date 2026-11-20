<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\entity\object\ArmorStand as ArmorStandEntity;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class ArmorStand extends Item{

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		if(!$blockReplace->canBeReplaced()){
			return ItemUseResult::NONE;
		}

		$entity = new ArmorStandEntity(Location::fromObject(
			$blockReplace->getPosition()->add(0.5, 0, 0.5),
			$player->getWorld(),
			$player->getLocation()->getYaw(),
			0
		));
		if($this->hasCustomName()){
			$entity->setNameTag($this->getCustomName());
		}

		$this->pop();
		$entity->spawnToAll();
		return ItemUseResult::SUCCESS;
	}
}
