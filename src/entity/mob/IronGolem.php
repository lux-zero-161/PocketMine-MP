<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class IronGolem extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::IRON_GOLEM; }

	public function getName() : string{ return "Iron Golem"; }
}
