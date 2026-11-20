<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;
use function mt_rand;

/**
 * Shared base for the generated simple mob implementations.
 *
 * These mobs have basic movement only; vanilla AI, breeding, drops and special behaviours are not implemented yet.
 */
abstract class SimpleMob extends Living{

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.8, 0.6);
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->isAlive() && $currentTick % 40 === 0 && mt_rand(0, 2) === 0 && $this->isOnGround()){
			$this->setMotion(new Vector3(mt_rand(-10, 10) / 100, 0.2, mt_rand(-10, 10) / 100));
		}
		return parent::onUpdate($currentTick);
	}

	public function getDrops() : array{
		return [];
	}

	public function getXpDropAmount() : int{
		return 0;
	}
}
