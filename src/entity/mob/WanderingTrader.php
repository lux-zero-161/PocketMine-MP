<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class WanderingTrader extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::WANDERING_TRADER; }

	public function getName() : string{ return "Wandering Trader"; }
}
