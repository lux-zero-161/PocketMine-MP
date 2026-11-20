<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\entity\object\ChestMinecart;
use pocketmine\entity\object\HopperMinecart;
use pocketmine\entity\object\Minecart as MinecartEntity;
use pocketmine\entity\object\TNTMinecart;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Minecart extends Item{

	public function __construct(ItemIdentifier $identifier, string $name, private string $kind = "normal"){
		parent::__construct($identifier, $name);
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		if(!$blockReplace->canBeReplaced()){
			return ItemUseResult::NONE;
		}

		$location = Location::fromObject(
			$blockReplace->getPosition()->add(0.5, 0, 0.5),
			$player->getWorld(),
			$player->getLocation()->yaw,
			0
		);
		$dropItem = (clone $this)->setCount(1);
		$entity = match($this->kind){
			"chest" => new ChestMinecart($location, null, $dropItem),
			"hopper" => new HopperMinecart($location, null, $dropItem),
			"tnt" => new TNTMinecart($location, null, $dropItem),
			default => new MinecartEntity($location, null, $dropItem)
		};

		$this->pop();
		$entity->spawnToAll();
		return ItemUseResult::SUCCESS;
	}
}
