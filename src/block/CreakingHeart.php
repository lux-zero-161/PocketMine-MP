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

use pocketmine\block\utils\CreakingHeartState;
use pocketmine\block\utils\PillarRotation;
use pocketmine\block\utils\PillarRotationTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;

class CreakingHeart extends Opaque implements PillarRotation{
	use PillarRotationTrait;

	protected CreakingHeartState $state = CreakingHeartState::UPROOTED;
	protected bool $natural = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->axis($this->axis);
		$w->enum($this->state);
		$w->bool($this->natural);
	}

	public function getState() : CreakingHeartState{ return $this->state; }

	/** @return $this */
	public function setState(CreakingHeartState $state) : self{
		$this->state = $state;
		return $this;
	}

	public function isNatural() : bool{ return $this->natural; }

	/** @return $this */
	public function setNatural(bool $natural) : self{
		$this->natural = $natural;
		return $this;
	}
}
