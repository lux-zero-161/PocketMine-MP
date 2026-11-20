<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\network\mcpe\protocol\types\BossBarOverlay;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use function cos;
use function max;
use function min;
use function sin;
use function mt_rand;
use function spl_object_id;

class EnderDragon extends Living{
	public const MAX_HEALTH = 200;

	private int $attackCooldown = 0;
	private int $barUpdateCooldown = 0;
	private float $circleAngle = 0.0;

	public function __construct(Location $location, ?CompoundTag $nbt = null){
		parent::__construct($location, $nbt);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::ENDER_DRAGON; }

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(8.0, 16.0, 5.0); }

	protected function getInitialDragMultiplier() : float{ return 0.09; }

	protected function getInitialGravity() : float{ return 0.0; }

	public function getName() : string{ return "Ender Dragon"; }

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->setMaxHealth(self::MAX_HEALTH);
		$this->setHealth(self::MAX_HEALTH);
		$this->setNameTag("Ender Dragon");
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);
		if($source instanceof \pocketmine\event\entity\EntityDamageByEntityEvent){
			$damager = $source->getDamager();
			if($damager instanceof Player){
				$this->setTargetEntity($damager);
			}
		}
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->isAlive()){
			$target = $this->resolveTarget();
			if($target !== null && $this->location->distanceSquared($target->getLocation()) < 64 * 64){
				$this->chase($target);
				if($this->location->distanceSquared($target->getLocation()) < 25){
					if(--$this->attackCooldown <= 0){
						$this->attackCooldown = 20;
						$target->attack(new \pocketmine\event\entity\EntityDamageByEntityEvent(
							$this,
							$target,
							EntityDamageEvent::CAUSE_ENTITY_ATTACK,
							10
						));
					}
				}
			}else{
				$this->circle();
			}

			if($this->barUpdateCooldown > 0){
				--$this->barUpdateCooldown;
			}else{
				$this->barUpdateCooldown = 20;
				$this->updateBossBars();
			}
		}

		return parent::onUpdate($currentTick);
	}

	public function spawnTo(Player $player) : void{
		parent::spawnTo($player);
		if(isset($this->hasSpawned[spl_object_id($player)])){
			$this->sendBossBar($player);
		}
	}

	public function despawnFrom(Player $player, bool $send = true) : void{
		$wasSpawned = isset($this->hasSpawned[spl_object_id($player)]);
		parent::despawnFrom($player, $send);
		if($wasSpawned){
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::hide($this->getId()));
		}
	}

	protected function onDeath() : void{
		foreach($this->getViewers() as $player){
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::hide($this->getId()));
		}
		parent::onDeath();
	}

	public function getDrops() : array{
		return [VanillaBlocks::DRAGON_EGG()->asItem()];
	}

	public function getXpDropAmount() : int{
		return 12000;
	}

	private function resolveTarget() : ?Player{
		$current = $this->getTargetEntity();
		if($current instanceof Player && $current->isAlive()){
			return $current;
		}
		$box = AxisAlignedBB::one()->expand(48, 48, 48)->offset($this->location->x, $this->location->y, $this->location->z);
		$nearest = null;
		$best = 48 * 48;
		foreach($this->getWorld()->getNearbyEntities($box, $this) as $entity){
			if($entity instanceof Player && $entity->isAlive() && !$entity->isSpectator()){
				$dist = $this->location->distanceSquared($entity->getLocation());
				if($dist < $best){
					$best = $dist;
					$nearest = $entity;
				}
			}
		}
		if($nearest !== null){
			$this->setTargetEntity($nearest);
		}
		return $nearest;
	}

	private function chase(Player $target) : void{
		$desired = $target->getPosition()->add(0, 5, 0);
		$diff = $desired->subtractVector($this->location);
		$distance = $diff->length();
		if($distance < 0.5){
			$this->setMotion(new Vector3(0, 0, 0));
			return;
		}
		$this->setMotion($diff->normalize()->multiply(min(0.4, 0.15 + $distance / 120)));
	}

	private function circle() : void{
		$this->circleAngle += 0.05;
		$radius = 20.0;
		$center = $this->getWorld()->getSpawnLocation();
		$desired = new Vector3(
			$center->x + cos($this->circleAngle) * $radius,
			max($center->y + 20, $this->location->y),
			$center->z + sin($this->circleAngle) * $radius
		);
		$diff = $desired->subtractVector($this->location);
		if($diff->lengthSquared() > 1.0){
			$this->setMotion($diff->normalize()->multiply(0.2));
		}else{
			$this->setMotion(new Vector3(mt_rand(-10, 10) / 100, 0, mt_rand(-10, 10) / 100));
		}
	}

	private function updateBossBars() : void{
		$percent = max(0.0, min(1.0, $this->getHealth() / $this->getMaxHealth()));
		foreach($this->getViewers() as $player){
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::healthPercent($this->getId(), $percent));
		}
	}

	private function sendBossBar(Player $player) : void{
		$percent = max(0.0, min(1.0, $this->getHealth() / $this->getMaxHealth()));
		$player->getNetworkSession()->sendDataPacket(BossEventPacket::show(
			$this->getId(),
			"Ender Dragon",
			$percent,
			false,
			BossBarColor::PURPLE,
			BossBarOverlay::PROGRESS
		));
	}
}
