<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Strider extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::STRIDER; }

	public function getName() : string{ return "Strider"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
