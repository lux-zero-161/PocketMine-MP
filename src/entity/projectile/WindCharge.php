<?php

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use function max;

class WindCharge extends Throwable{

	public static function getNetworkTypeId() : string{ return EntityIds::WIND_CHARGE_PROJECTILE; }

	public function getResultDamage() : int{
		return -1;
	}

	protected function onHit(ProjectileHitEvent $event) : void{
		$world = $this->getWorld();
		$box = AxisAlignedBB::one()->expand(3, 3, 3)->offset($this->location->x, $this->location->y, $this->location->z);
		foreach($world->getNearbyEntities($box, $this) as $entity){
			$diff = $entity->getPosition()->subtractVector($this->location);
			$length = $diff->length();
			if($length <= 0.01){
				continue;
			}
			$entity->setMotion($entity->getMotion()->addVector($diff->normalize()->multiply(max(0.5, 3 - $length) * 0.4))->add(0, 0.4, 0));
		}
	}
}
