<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Hoglin extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::HOGLIN; }

	public function getName() : string{ return "Hoglin"; }
}
