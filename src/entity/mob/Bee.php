<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Bee extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::BEE; }

	public function getName() : string{ return "Bee"; }
}
