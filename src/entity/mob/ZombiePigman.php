<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ZombiePigman extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE_PIGMAN; }

	public function getName() : string{ return "Zombie Pigman"; }
}
