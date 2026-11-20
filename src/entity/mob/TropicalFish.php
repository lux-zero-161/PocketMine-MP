<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class TropicalFish extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::TROPICALFISH; }

	public function getName() : string{ return "Tropical Fish"; }
}
