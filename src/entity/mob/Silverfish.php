<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Silverfish extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::SILVERFISH; }

	public function getName() : string{ return "Silverfish"; }
}
