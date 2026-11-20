<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class SkeletonHorse extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::SKELETON_HORSE; }

	public function getName() : string{ return "Skeleton Horse"; }
}
