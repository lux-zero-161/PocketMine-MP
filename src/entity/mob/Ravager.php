<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Ravager extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::RAVAGER; }

	public function getName() : string{ return "Ravager"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
