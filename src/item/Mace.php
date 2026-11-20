<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Entity;

class Mace extends Tool{

	public function getMaxDurability() : int{
		return 500;
	}

	public function getAttackPoints() : int{
		return 6;
	}

	public function onAttackEntity(Entity $victim, array &$returnedItems) : bool{
		return $this->applyDamage(1);
	}
}
