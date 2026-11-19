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

use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;
use function min;

class Scaffolding extends Transparent{
	public const MAX_STABILITY = 7;

	protected int $stability = 0;
	protected bool $stabilityCheck = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_STABILITY, $this->stability);
		$w->bool($this->stabilityCheck);
	}

	public function getStability() : int{ return $this->stability; }

	/** @return $this */
	public function setStability(int $stability) : self{
		if($stability < 0 || $stability > self::MAX_STABILITY){
			throw new \InvalidArgumentException("Stability must be in range 0 ... " . self::MAX_STABILITY);
		}
		$this->stability = $stability;
		return $this;
	}

	public function isStabilityCheck() : bool{ return $this->stabilityCheck; }

	/** @return $this */
	public function setStabilityCheck(bool $stabilityCheck) : self{
		$this->stabilityCheck = $stabilityCheck;
		return $this;
	}

	public function canClimb() : bool{
		return true;
	}

	public function getFlameEncouragement() : int{
		return 60;
	}

	public function getFlammability() : int{
		return 20;
	}

	/**
	 * Recomputes the distance from the nearest support.
	 *
	 * @return int|null null when the block has no support and should break
	 */
	private function calculateStability() : ?int{
		$below = $this->getSide(Facing::DOWN);
		if($below instanceof Scaffolding){
			return $below->getStability() < self::MAX_STABILITY ? $below->getStability() : null;
		}
		if($below->getSupportType(Facing::UP) !== SupportType::NONE){
			return 0;
		}

		$lowestNeighbour = null;
		foreach(Facing::HORIZONTAL as $face){
			$side = $this->getSide($face);
			if($side instanceof Scaffolding && $side->getStability() < self::MAX_STABILITY){
				$lowestNeighbour = $lowestNeighbour === null ? $side->getStability() : min($lowestNeighbour, $side->getStability());
			}
		}

		if($lowestNeighbour === null || $lowestNeighbour + 1 >= self::MAX_STABILITY){
			return null;
		}
		return $lowestNeighbour + 1;
	}

	public function onNearbyBlockChange() : void{
		$stability = $this->calculateStability();
		$world = $this->position->getWorld();
		if($stability === null){
			$world->useBreakOn($this->position);
		}elseif($stability !== $this->stability){
			$world->setBlock($this->position, $this->setStability($stability));
		}
	}
}
