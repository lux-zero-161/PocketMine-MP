<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class HopperMinecart extends Minecart{
	public static function getNetworkTypeId() : string{ return EntityIds::HOPPER_MINECART; }
	public function getName() : string{ return "Hopper Minecart"; }
}
