<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class CamelHusk extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::CAMEL_HUSK; }

	public function getName() : string{ return "Camel Husk"; }
}
