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

use pocketmine\block\utils\CrafterOrientation;
use pocketmine\data\runtime\RuntimeDataDescriber;

class Crafter extends Opaque{
	protected CrafterOrientation $orientation = CrafterOrientation::DOWN_EAST;
	protected bool $triggered = false;
	protected bool $crafting = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->orientation);
		$w->bool($this->triggered);
		$w->bool($this->crafting);
	}

	public function getOrientation() : CrafterOrientation{ return $this->orientation; }

	/** @return $this */
	public function setOrientation(CrafterOrientation $orientation) : self{
		$this->orientation = $orientation;
		return $this;
	}

	public function isTriggered() : bool{ return $this->triggered; }

	/** @return $this */
	public function setTriggered(bool $triggered) : self{
		$this->triggered = $triggered;
		return $this;
	}

	public function isCrafting() : bool{ return $this->crafting; }

	/** @return $this */
	public function setCrafting(bool $crafting) : self{
		$this->crafting = $crafting;
		return $this;
	}
}
