<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ZombieHorse extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE_HORSE; }

	public function getName() : string{ return "Zombie Horse"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
