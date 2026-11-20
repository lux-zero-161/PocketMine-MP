<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class WitherSkeleton extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::WITHER_SKELETON; }

	public function getName() : string{ return "Wither Skeleton"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
