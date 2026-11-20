<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Vex extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::VEX; }

	public function getName() : string{ return "Vex"; }
}
