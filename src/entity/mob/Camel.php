<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Camel extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::CAMEL; }

	public function getName() : string{ return "Camel"; }
}
