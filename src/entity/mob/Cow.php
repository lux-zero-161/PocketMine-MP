<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Cow extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::COW; }

	public function getName() : string{ return "Cow"; }
}
