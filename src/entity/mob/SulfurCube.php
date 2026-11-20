<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class SulfurCube extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::SULFUR_CUBE; }

	public function getName() : string{ return "Sulfur Cube"; }
}
