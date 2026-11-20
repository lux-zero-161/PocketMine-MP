<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Spider extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::SPIDER; }

	public function getName() : string{ return "Spider"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
