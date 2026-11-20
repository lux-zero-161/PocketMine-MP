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

use pocketmine\block\tile\DecoratedPot as TileDecoratedPot;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\data\runtime\RuntimeDataDescriber;

class DecoratedPot extends Opaque{
	public const MAX_DIRECTION = 3;

	protected int $direction = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_DIRECTION, $this->direction);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileDecoratedPot){
			return false;
		}

		$inventory = $tile->getInventory();
		$current = $inventory->getItem(0);
		if($item->isNull()){
			if(!$current->isNull()){
				$returnedItems[] = $current;
				$inventory->clear(0);
			}
			return true;
		}

		if($current->isNull()){
			$inventory->setItem(0, (clone $item)->setCount(1));
			$item->pop();
			return true;
		}

		if($current->equals($item) && $current->getCount() < $current->getMaxStackSize()){
			$inventory->setItem(0, $current->setCount($current->getCount() + 1));
			$item->pop();
			return true;
		}

		return true;
	}

	public function getDirection() : int{ return $this->direction; }

	/** @return $this */
	public function setDirection(int $direction) : self{
		if($direction < 0 || $direction > self::MAX_DIRECTION){
			throw new \InvalidArgumentException("Direction must be in range 0 ... " . self::MAX_DIRECTION);
		}
		$this->direction = $direction;
		return $this;
	}
}
