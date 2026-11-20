<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class TraderLlama extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::TRADER_LLAMA; }

	public function getName() : string{ return "Trader Llama"; }
}
