<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ZombieVillager extends SimpleMob{

	public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE_VILLAGER; }

	public function getName() : string{ return "Zombie Villager"; }
}
