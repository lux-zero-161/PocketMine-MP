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

use pocketmine\block\tile\CalibratedSculkSensor as TileCalibratedSculkSensor;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class CalibratedSculkSensor extends SculkSensor implements HorizontalFacing{
	use HorizontalFacingTrait;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->boundedIntAuto(0, self::MAX_PHASE, $this->phase);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($item->getTypeId() !== ItemTypeIds::AMETHYST_SHARD){
			return false;
		}
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileCalibratedSculkSensor || $tile->getLastFrequency() === 0){
			return false;
		}

		$tile->setTunedFrequency($tile->getTunedFrequency() === $tile->getLastFrequency() ? 0 : $tile->getLastFrequency());
		$item->pop();
		if($player !== null){
			$player->getInventory()->setItemInHand($item);
			$player->sendMessage($tile->getTunedFrequency() === 0
				? "Calibrated Sculk Sensor: all frequencies"
				: "Calibrated Sculk Sensor: frequency " . $tile->getTunedFrequency()
			);
		}
		return true;
	}
}
