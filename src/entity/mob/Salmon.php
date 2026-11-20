<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Salmon extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::SALMON; }

	public function getName() : string{ return "Salmon"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
