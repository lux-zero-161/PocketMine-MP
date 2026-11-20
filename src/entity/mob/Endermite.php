<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Endermite extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::ENDERMITE; }

	public function getName() : string{ return "Endermite"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
