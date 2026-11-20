<?php

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\NeverSavedWithChunkEntity;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class SmallFireball extends Throwable implements NeverSavedWithChunkEntity{
	public function __construct(Location $location, ?Entity $shootingEntity, ?\pocketmine\nbt\tag\CompoundTag $nbt = null){
		parent::__construct($location, $shootingEntity, $nbt);
		$this->setBaseDamage(5.0);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::SMALL_FIREBALL; }

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.25, 0.25); }

	protected function getInitialDragMultiplier() : float{ return 0.01; }

	protected function getInitialGravity() : float{ return 0.0; }

	public function getName() : string{ return "Small Fireball"; }

	protected function onHit(ProjectileHitEvent $event) : void{
		parent::onHit($event);
		$this->flagForDespawn();
	}
}
