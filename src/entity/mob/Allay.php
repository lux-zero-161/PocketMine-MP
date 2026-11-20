<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Allay extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::ALLAY; }

	public function getName() : string{ return "Allay"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
