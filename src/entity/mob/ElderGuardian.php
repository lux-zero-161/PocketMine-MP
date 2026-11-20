<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ElderGuardian extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::ELDER_GUARDIAN; }

	public function getName() : string{ return "Elder Guardian"; }
}
