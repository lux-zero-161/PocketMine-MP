<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Allay extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::ALLAY; }

	public function getName() : string{ return "Allay"; }
}
