<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Blaze extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::BLAZE; }

	public function getName() : string{ return "Blaze"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
