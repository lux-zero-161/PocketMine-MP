<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\WitherSkull;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\network\mcpe\protocol\types\BossBarOverlay;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use function max;
use function min;
use function mt_rand;
use function spl_object_id;

class Wither extends Living{
	public const MAX_HEALTH = 300;

	private int $shootCooldown = 40;
	private int $barUpdateCooldown = 0;

	public function __construct(Location $location, ?CompoundTag $nbt = null){
		parent::__construct($location, $nbt);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::WITHER; }

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(3.5, 1.0, 2.5); }

	protected function getInitialDragMultiplier() : float{ return 0.09; }

	protected function getInitialGravity() : float{ return 0.0; }

	public function getName() : string{ return "Wither"; }

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->setMaxHealth(self::MAX_HEALTH);
		$this->setHealth(self::MAX_HEALTH);
		$this->setNameTag("Wither");
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
			if($target !== null){
				$this->flyTowards($target);
				if(--$this->shootCooldown <= 0){
					$this->shootCooldown = mt_rand(30, 60);
					$this->shootAt($target);
				}
			}elseif($currentTick % 40 === 0){
				$this->setMotion(new Vector3(mt_rand(-10, 10) / 100, 0, mt_rand(-10, 10) / 100));
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
		return [VanillaItems::NETHER_STAR()];
	}

	public function getXpDropAmount() : int{
		return 50;
	}

	private function resolveTarget() : ?Player{
		$current = $this->getTargetEntity();
		if($current instanceof Player && $current->isAlive() && $this->location->distanceSquared($current->getLocation()) < 64 * 64){
			return $current;
		}
		$box = AxisAlignedBB::one()->expand(32, 32, 32)->offset($this->location->x, $this->location->y, $this->location->z);
		$nearest = null;
		$best = 32 * 32;
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

	private function flyTowards(Player $target) : void{
		$desired = $target->getPosition()->add(0, 3, 0);
		$diff = $desired->subtractVector($this->location);
		$distance = $diff->length();
		if($distance < 1.0){
			$this->setMotion(new Vector3(0, 0, 0));
			return;
		}
		$this->setMotion($diff->normalize()->multiply(min(0.25, 0.08 + $distance / 100)));
	}

	private function shootAt(Player $target) : void{
		$direction = $target->getEyePos()->subtractVector($this->getEyePos());
		if($direction->lengthSquared() < 0.01){
			return;
		}
		$skull = new WitherSkull(
			Location::fromObject($this->getEyePos(), $this->getWorld(), $this->location->yaw, $this->location->pitch),
			$this
		);
		$skull->setMotion($direction->normalize()->multiply(0.9));
		$skull->spawnToAll();
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
			"Wither",
			$percent,
			false,
			BossBarColor::PURPLE,
			BossBarOverlay::PROGRESS
		));
	}
}
