<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Phantom extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::PHANTOM; }

	public function getName() : string{ return "Phantom"; }
}
