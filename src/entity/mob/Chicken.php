<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Chicken extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::CHICKEN; }

	public function getName() : string{ return "Chicken"; }
}
