<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class CopperGolem extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::COPPER_GOLEM; }

	public function getName() : string{ return "Copper Golem"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
