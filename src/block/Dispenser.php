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

namespace pocketmine\block;

use pocketmine\block\utils\AnyFacing;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;

class Dispenser extends Opaque implements AnyFacing{
	protected int $facing = Facing::DOWN;
	protected bool $triggered = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
		$w->bool($this->triggered);
	}

	public function onNearbyBlockChange() : void{
		$powered = RedstonePowerHelper::isPowered($this);
		if($powered && !$this->triggered){
			$this->triggered = true;
			$world = $this->position->getWorld();
			$world->setBlock($this->position, $this);
			$world->scheduleDelayedBlockUpdate($this->position, 2);
		}elseif(!$powered && $this->triggered){
			$this->triggered = false;
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function onScheduledUpdate() : void{
		if($this->triggered){
			$this->triggered = false;
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		Facing::validate($facing);
		$this->facing = $facing;
		return $this;
	}

	public function isTriggered() : bool{ return $this->triggered; }

	/** @return $this */
	public function setTriggered(bool $triggered) : self{
		$this->triggered = $triggered;
		return $this;
	}
}
