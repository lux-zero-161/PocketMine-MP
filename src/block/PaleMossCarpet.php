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

use pocketmine\block\utils\WallConnectionType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class PaleMossCarpet extends Transparent{
	/**
	 * @var WallConnectionType[]
	 * @phpstan-var array<2|3|4|5, WallConnectionType>
	 */
	protected array $connections = [];
	protected bool $upper = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->wallConnections($this->connections);
		$w->bool($this->upper);
	}

	public function getConnection(int $face) : ?WallConnectionType{
		return $this->connections[$face] ?? null;
	}

	/**
	 * @phpstan-param 2|3|4|5 $face
	 * @return $this
	 */
	public function setConnection(int $face, ?WallConnectionType $type) : self{
		if($type === null){
			unset($this->connections[$face]);
		}else{
			$this->connections[$face] = $type;
		}
		return $this;
	}

	public function isUpper() : bool{ return $this->upper; }

	/** @return $this */
	public function setUpper(bool $upper) : self{
		$this->upper = $upper;
		return $this;
	}

	public function isSolid() : bool{
		return true;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::UP, 15 / 16)];
	}
}
