<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class PiglinBrute extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::PIGLIN_BRUTE; }

	public function getName() : string{ return "Piglin Brute"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
