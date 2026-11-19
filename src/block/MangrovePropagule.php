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

use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;

class MangrovePropagule extends Flowable{
	use StaticSupportTrait;

	public const MAX_STAGE = 4;

	protected bool $hanging = false;
	protected int $stage = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->bool($this->hanging);
		$w->boundedIntAuto(0, self::MAX_STAGE, $this->stage);
	}

	public function isHanging() : bool{ return $this->hanging; }

	/** @return $this */
	public function setHanging(bool $hanging) : self{
		$this->hanging = $hanging;
		return $this;
	}

	public function getStage() : int{ return $this->stage; }

	/** @return $this */
	public function setStage(int $stage) : self{
		if($stage < 0 || $stage > self::MAX_STAGE){
			throw new \InvalidArgumentException("Stage must be in range 0 ... " . self::MAX_STAGE);
		}
		$this->stage = $stage;
		return $this;
	}

	private function canBeSupportedAt(Block $block) : bool{
		if($this->hanging){
			return $block->getSide(Facing::UP)->getTypeId() !== BlockTypeIds::AIR;
		}
		$supportBlock = $block->getSide(Facing::DOWN);
		return $supportBlock->hasTypeTag(BlockTypeTags::DIRT) || $supportBlock->hasTypeTag(BlockTypeTags::MUD);
	}
}
