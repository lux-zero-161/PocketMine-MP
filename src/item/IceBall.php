<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\IceBomb;
use pocketmine\entity\projectile\Throwable;
use pocketmine\player\Player;

class IceBall extends ProjectileItem{

	public function getMaxStackSize() : int{
		return 16;
	}

	protected function createEntity(Location $location, Player $thrower) : Throwable{
		return new IceBomb($location, $thrower);
	}

	public function getThrowForce() : float{
		return 1.5;
	}
}
