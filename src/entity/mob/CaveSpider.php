<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class CaveSpider extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::CAVE_SPIDER; }

	public function getName() : string{ return "Cave Spider"; }
}
