<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class HappyGhast extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::HAPPY_GHAST; }

	public function getName() : string{ return "Happy Ghast"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
