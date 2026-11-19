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

use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;

class DriedGhast extends Opaque implements HorizontalFacing{
	use HorizontalFacingTrait;

	public const MAX_REHYDRATION_LEVEL = 3;

	protected int $rehydrationLevel = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->boundedIntAuto(0, self::MAX_REHYDRATION_LEVEL, $this->rehydrationLevel);
	}

	public function getRehydrationLevel() : int{ return $this->rehydrationLevel; }

	/** @return $this */
	public function setRehydrationLevel(int $rehydrationLevel) : self{
		if($rehydrationLevel < 0 || $rehydrationLevel > self::MAX_REHYDRATION_LEVEL){
			throw new \InvalidArgumentException("Rehydration level must be in range 0 ... " . self::MAX_REHYDRATION_LEVEL);
		}
		$this->rehydrationLevel = $rehydrationLevel;
		return $this;
	}
}
