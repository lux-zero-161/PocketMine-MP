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

use pocketmine\block\utils\SeaGrassType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\Shears;

class Seagrass extends Flowable{
	protected SeaGrassType $type = SeaGrassType::DEFAULT;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->type);
	}

	public function getType() : SeaGrassType{ return $this->type; }

	/** @return $this */
	public function setType(SeaGrassType $type) : self{
		$this->type = $type;
		return $this;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return $item instanceof Shears ? [$this->asItem()] : [];
	}
}
