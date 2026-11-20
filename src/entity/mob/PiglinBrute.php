<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class PiglinBrute extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::PIGLIN_BRUTE; }

	public function getName() : string{ return "Piglin Brute"; }
}
