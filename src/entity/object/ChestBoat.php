<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ChestBoat extends Boat{

	public static function getNetworkTypeId() : string{ return EntityIds::CHEST_BOAT; }

	public function getName() : string{
		return "Chest Boat";
	}
}
