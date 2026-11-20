<?php

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\NeverSavedWithChunkEntity;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\Explosion;
use pocketmine\world\Position;

class WitherSkull extends Throwable implements NeverSavedWithChunkEntity{
	private bool $exploded = false;

	public function __construct(Location $location, ?Entity $shootingEntity, ?\pocketmine\nbt\tag\CompoundTag $nbt = null){
		parent::__construct($location, $shootingEntity, $nbt);
		$this->setBaseDamage(8.0);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::WITHER_SKULL; }

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.25, 0.25); }

	protected function getInitialDragMultiplier() : float{ return 0.01; }

	protected function getInitialGravity() : float{ return 0.0; }

	public function getName() : string{ return "Wither Skull"; }

	protected function onHit(ProjectileHitEvent $event) : void{
		parent::onHit($event);
		if(!$this->exploded){
			$this->exploded = true;
			$explosion = new Explosion(Position::fromObject($this->location, $this->getWorld()), 1.0, $this, 0.0);
			$explosion->explodeA();
			$explosion->explodeB();
		}
		$this->flagForDespawn();
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		parent::onHitBlock($blockHit, $hitResult);
		if(!$this->exploded){
			$this->exploded = true;
			$explosion = new Explosion(Position::fromObject($this->location, $this->getWorld()), 1.0, $this, 0.0);
			$explosion->explodeA();
			$explosion->explodeB();
		}
		$this->flagForDespawn();
	}
}
