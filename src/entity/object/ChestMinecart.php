<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ChestMinecart extends Minecart{
	public static function getNetworkTypeId() : string{ return EntityIds::CHEST_MINECART; }
	public function getName() : string{ return "Chest Minecart"; }
}
