<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_|  | | | | | |  __/_____| |  | |  __/
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

namespace pocketmine\block;

use pocketmine\block\tile\Chest as TileChest;
use pocketmine\block\utils\AnalogRedstoneSignalEmitter;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function count;
use function min;

class TrappedChest extends Chest implements AnalogRedstoneSignalEmitter{

	/**
	 * A trapped chest outputs a signal strength equal to the number of players currently viewing it.
	 */
	public function getOutputSignalStrength() : int{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileChest){
			return 0;
		}
		return min(15, count($tile->getInventory()->getViewers()));
	}

	/**
	 * The signal is derived from the current viewers, so it cannot be assigned directly.
	 *
	 * @return $this
	 */
	public function setOutputSignalStrength(int $signalStrength) : self{
		return $this;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		$result = parent::onInteract($item, $face, $clickVector, $player, $returnedItems);
		if($result){
			$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 1);
		}
		return $result;
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$world->notifyNeighbourBlockUpdate($this->position);
		if($this->getOutputSignalStrength() > 0){
			//poll while players have the chest open so closing it updates the signal too
			$world->scheduleDelayedBlockUpdate($this->position, 20);
		}
	}
}
