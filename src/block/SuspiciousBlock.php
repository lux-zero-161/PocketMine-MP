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

use pocketmine\data\runtime\RuntimeDataDescriber;

class SuspiciousBlock extends Opaque{
	public const MAX_BRUSHED_PROGRESS = 3;

	protected int $brushedProgress = 0;
	protected bool $hanging = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_BRUSHED_PROGRESS, $this->brushedProgress);
		$w->bool($this->hanging);
	}

	public function getBrushedProgress() : int{ return $this->brushedProgress; }

	/** @return $this */
	public function setBrushedProgress(int $brushedProgress) : self{
		if($brushedProgress < 0 || $brushedProgress > self::MAX_BRUSHED_PROGRESS){
			throw new \InvalidArgumentException("Brushed progress must be in range 0 ... " . self::MAX_BRUSHED_PROGRESS);
		}
		$this->brushedProgress = $brushedProgress;
		return $this;
	}

	public function isHanging() : bool{ return $this->hanging; }

	/** @return $this */
	public function setHanging(bool $hanging) : self{
		$this->hanging = $hanging;
		return $this;
	}
}
