<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class GlowSquid extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::GLOW_SQUID; }

	public function getName() : string{ return "Glow Squid"; }
}
