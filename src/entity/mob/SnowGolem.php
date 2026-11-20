<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class SnowGolem extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::SNOW_GOLEM; }

	public function getName() : string{ return "Snow Golem"; }
}
