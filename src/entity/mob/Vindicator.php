<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Vindicator extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::VINDICATOR; }

	public function getName() : string{ return "Vindicator"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
