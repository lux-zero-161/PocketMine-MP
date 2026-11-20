<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class MobBucket extends LiquidBucket{

	/**
	 * @param \Closure(Location) : Entity|null $entityFactory
	 */
	public function __construct(ItemIdentifier $identifier, string $name, private ?\Closure $entityFactory = null){
		parent::__construct($identifier, $name, VanillaBlocks::WATER());
	}

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		$result = parent::onInteractBlock($player, $blockReplace, $blockClicked, $face, $clickVector, $returnedItems);
		if($result === ItemUseResult::SUCCESS && $this->entityFactory !== null){
			$entity = ($this->entityFactory)(Location::fromObject($blockReplace->getPosition()->add(0.5, 0, 0.5), $player->getWorld()));
			$entity->spawnToAll();
		}
		return $result;
	}
}
