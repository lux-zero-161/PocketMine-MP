<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Parrot extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::PARROT; }

	public function getName() : string{ return "Parrot"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
