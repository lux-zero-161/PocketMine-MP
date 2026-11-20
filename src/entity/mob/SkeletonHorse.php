<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class SkeletonHorse extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::SKELETON_HORSE; }

	public function getName() : string{ return "Skeleton Horse"; }
	public function createBaby(Location $location) : self{
		return new self($location);
	}
}
