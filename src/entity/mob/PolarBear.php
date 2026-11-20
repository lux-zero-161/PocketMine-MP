<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class PolarBear extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::POLAR_BEAR; }

	public function getName() : string{ return "Polar Bear"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
