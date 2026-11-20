<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Evoker extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::EVOCATION_ILLAGER; }

	public function getName() : string{ return "Evoker"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
