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

use pocketmine\block\utils\CrackedState;
use pocketmine\block\utils\TurtleEggCount;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class TurtleEgg extends Transparent{
	protected CrackedState $crackedState = CrackedState::NO_CRACKS;
	protected TurtleEggCount $count = TurtleEggCount::ONE;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->crackedState);
		$w->enum($this->count);
	}

	public function getCrackedState() : CrackedState{ return $this->crackedState; }

	/** @return $this */
	public function setCrackedState(CrackedState $crackedState) : self{
		$this->crackedState = $crackedState;
		return $this;
	}

	public function getCount() : TurtleEggCount{ return $this->count; }

	/** @return $this */
	public function setCount(TurtleEggCount $count) : self{
		$this->count = $count;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		$width = match($this->count){
			TurtleEggCount::ONE => 4 / 16,
			TurtleEggCount::TWO => 8 / 16,
			TurtleEggCount::THREE, TurtleEggCount::FOUR => 1
		};
		$x = (1 - $width) / 2;
		return [AxisAlignedBB::one()->trim(Facing::UP, 7 / 16)->contract($x, 0, $x)];
	}
}
