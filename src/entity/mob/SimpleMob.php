<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\ai\PathFinder;
use pocketmine\entity\projectile\SmallFireball;
use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\entity\Location;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\Dye;
use pocketmine\item\PotionType;
use pocketmine\item\Shears;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\Explosion;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\HeartParticle;
use function in_array;
use function mt_rand;

/**
 * Shared base for the generated simple mob implementations.
 *
 * These mobs have basic wandering/hostile movement, simple drops and basic breeding.
 * Pathfinding, special behaviours and full vanilla AI are not implemented yet.
 */
abstract class SimpleMob extends Living{
	private const HOSTILE = [
		"Blaze", "Bogged", "Breeze", "Cave Spider", "Creaking", "Creeper", "Drowned", "Elder Guardian",
		"Enderman", "Endermite", "Evoker", "Frostbite", "Ghast", "Guardian", "Hoglin", "Husk", "Magma Cube",
		"Parched", "Phantom", "Piglin", "Piglin Brute", "Pillager", "Ravager", "Shulker", "Silverfish",
		"Skeleton", "Skeleton Horse", "Slime", "Spider", "Stray", "Vex", "Vindicator", "Warden", "Witch",
		"Wither Skeleton", "Zoglin", "Zombie Horse", "Zombie Nautilus", "Zombie Pigman", "Zombie Villager"
	];

	private int $inLoveTicks = 0;
	private int $breedingCooldown = 0;
	private bool $baby = false;

	/** @var Vector3[]|null */
	private ?array $path = null;
	private int $pathIndex = 0;
	private int $pathCooldown = 0;
	private int $fleeTicks = 0;
	private ?Vector3 $fleeFrom = null;
	private const BURNS_IN_DAYLIGHT = [
		"Zombie", "Husk", "Drowned", "Zombie Villager", "Zombie Pigman",
		"Skeleton", "Stray", "Bogged", "Wither Skeleton", "Phantom"
	];

	private int $creeperFuse = -1;
	private bool $sheared = false;
	private int $woolRegrowTicks = 0;
	private int $eggLayTicks = 0;
	private ?DyeColor $woolColor = null;
	private bool $tamed = false;
	private ?Player $tamedBy = null;

	abstract public function createBaby(Location $location) : self;

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.8, 0.6);
	}

	public function isBaby() : bool{ return $this->baby; }

	public function setBaby(bool $baby) : void{
		$this->baby = $baby;
		$this->setScale($baby ? 0.5 : 1.0);
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->breedingCooldown > 0){
			--$this->breedingCooldown;
		}
		if($this->inLoveTicks > 0){
			--$this->inLoveTicks;
			if($currentTick % 10 === 0){
				$this->getWorld()->addParticle($this->location->add(0, $this->size->getHeight(), 0), new HeartParticle());
			}
			if($currentTick % 20 === 0){
				$this->tryBreed();
			}
		}

		if($this->isAlive()){
			$this->tickSpecial($currentTick);
			if($this->isHostile()){
				$this->tickHostile($currentTick);
			}elseif($this->fleeTicks > 0){
				$this->tickFlee();
			}elseif($currentTick % 40 === 0 && mt_rand(0, 2) === 0 && $this->isOnGround()){
				$this->setMotion(new Vector3(mt_rand(-10, 10) / 100, 0.2, mt_rand(-10, 10) / 100));
			}
		}
		return parent::onUpdate($currentTick);
	}

	private function handleSpecialInteraction(Player $player) : bool{
		$item = $player->getInventory()->getItemInHand();
		$name = $this->getName();

		if($name === "Cow" && $item instanceof \pocketmine\item\Bucket){
			$item->pop();
			$player->getInventory()->setItemInHand($item);
			$player->getInventory()->addItem(VanillaItems::MILK_BUCKET());
			return true;
		}
		if($name === "Mooshroom" && $item instanceof \pocketmine\item\Bowl){
			$item->pop();
			$player->getInventory()->setItemInHand($item);
			$player->getInventory()->addItem(VanillaItems::MUSHROOM_STEW());
			return true;
		}
		if($name === "Sheep" && $item instanceof Dye){
			$this->woolColor = $item->getColor();
			$item->pop();
			$player->getInventory()->setItemInHand($item);
			return true;
		}
		if($name === "Mooshroom" && $item instanceof Shears && !$this->sheared){
			$this->sheared = true;
			$this->woolRegrowTicks = 1200;
			$item->applyDamage(1);
			$player->getInventory()->setItemInHand($item);
			for($i = 0; $i < 5; ++$i){
				$this->getWorld()->dropItem($this->location->add(0, $this->size->getHeight() / 2, 0), VanillaBlocks::RED_MUSHROOM()->asItem());
			}
			return true;
		}
		if(($name === "Wolf" || $name === "Cat") && !$this->tamed){
			$food = $name === "Wolf" ? ItemTypeIds::BONE : null;
			if(($food !== null && $item->getTypeId() === $food) || ($name === "Cat" && ($item instanceof \pocketmine\item\RawFish || $item instanceof \pocketmine\item\RawSalmon || $item instanceof \pocketmine\item\Clownfish))){
				$item->pop();
				$player->getInventory()->setItemInHand($item);
				if(mt_rand(0, 2) === 0){
					$this->tamed = true;
					$this->tamedBy = $player;
					$this->setNameTag($player->getName() . "'s " . $name);
					$this->getWorld()->addParticle($this->location->add(0, $this->size->getHeight(), 0), new HeartParticle());
				}
				return true;
			}
		}
		if($name === "Sheep" && $item instanceof Shears && !$this->sheared){
			$this->sheared = true;
			$this->woolRegrowTicks = 1200;
			$item->applyDamage(1);
			$player->getInventory()->setItemInHand($item);
			$wool = VanillaBlocks::WOOL();
			if($this->woolColor !== null){
				$wool->setColor($this->woolColor);
			}
			$this->getWorld()->dropItem($this->location->add(0, $this->size->getHeight() / 2, 0), $wool->asItem()->setCount(mt_rand(1, 3)));
			return true;
		}
		return false;
	}

	private function tickSpecial(int $currentTick) : void{
		$name = $this->getName();

		if($this->woolRegrowTicks > 0 && --$this->woolRegrowTicks === 0){
			$this->sheared = false;
		}

		if($name === "Chicken" && !$this->baby){
			if($this->eggLayTicks <= 0){
				$this->eggLayTicks = mt_rand(6000, 12000);
			}elseif(--$this->eggLayTicks === 0){
				$this->getWorld()->dropItem($this->location->add(0, $this->size->getHeight() / 2, 0), VanillaItems::EGG());
			}
		}

		if($name === "Creeper"){
			$player = $this->getNearestPlayer(3.5);
			if($player !== null){
				if($this->creeperFuse < 0){
					$this->creeperFuse = 30;
				}elseif(--$this->creeperFuse <= 0){
					$this->explode();
					return;
				}
			}elseif($this->creeperFuse >= 0){
				$this->creeperFuse = -1;
			}
		}

		if($name === "Enderman" && $currentTick % 100 === 0 && $this->getNearestPlayer(12) !== null){
			$this->teleportRandomly();
		}

		if($this->tamed && $this->tamedBy !== null && $this->tamedBy->isAlive()){
			$owner = $this->tamedBy;
			if($this->location->distanceSquared($owner->getLocation()) > 64){
				$this->moveTowards($owner->getPosition(), 0.3);
			}
		}

		if($name === "Iron Golem" && $currentTick % 10 === 0){
			$target = $this->getNearestHostileMob(10);
			if($target !== null){
				$diff = $target->getPosition()->subtractVector($this->location);
				if($diff->length() > 2){
					$this->moveTowards($target->getPosition(), 0.25);
				}else{
					$target->attack(new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 7));
				}
			}
		}

		if($name === "Snow Golem" && $currentTick % 40 === 0){
			$target = $this->getNearestHostileMob(10);
			if($target !== null){
				$direction = $target->getEyePos()->subtractVector($this->getEyePos());
				if($direction->lengthSquared() > 0.01){
					$snowball = new Snowball(Location::fromObject($this->getEyePos(), $this->getWorld(), $this->location->yaw, $this->location->pitch), $this);
					$snowball->setBaseDamage(1.0);
					$snowball->setMotion($direction->normalize()->multiply(0.7));
					$snowball->spawnToAll();
				}
			}
		}

		if(($name === "Witch" || $name === "Blaze" || $name === "Ghast") && $currentTick % 60 === 0){
			$player = $this->getNearestPlayer(16);
			if($player !== null){
				$direction = $player->getEyePos()->subtractVector($this->getEyePos());
				if($direction->lengthSquared() > 0.01){
					if($name === "Witch"){
						$projectile = new SplashPotion(Location::fromObject($this->getEyePos(), $this->getWorld(), $this->location->yaw, $this->location->pitch), $this, PotionType::HARMING);
					}else{
						$projectile = new SmallFireball(Location::fromObject($this->getEyePos(), $this->getWorld(), $this->location->yaw, $this->location->pitch), $this);
					}
					$projectile->setMotion($direction->normalize()->multiply(0.7));
					$projectile->spawnToAll();
				}
			}
		}

		if($currentTick % 100 === 0 && in_array($name, self::BURNS_IN_DAYLIGHT, true)){
			$this->tickDaylightBurning();
		}

		if($currentTick % 100 === 0 && !$this->isHostile() && $this->fleeTicks <= 0 && $this->inLoveTicks <= 0 && mt_rand(0, 1) === 0){
			$this->tickHerd();
		}
	}

	private function tickDaylightBurning() : void{
		$world = $this->getWorld();
		if($world->getTimeOfDay() >= 12000 || $this->isUnderwater()){
			return;
		}
		$light = $world->getRealBlockSkyLightAt($this->location->getFloorX(), $this->location->getFloorY() + 1, $this->location->getFloorZ());
		if($light >= 12){
			$this->setOnFire(8);
		}
	}

	private function tickHerd() : void{
		$box = AxisAlignedBB::one()->expand(8, 6, 8)->offset($this->location->x, $this->location->y, $this->location->z);
		foreach($this->getWorld()->getNearbyEntities($box, $this) as $entity){
			if($entity instanceof SimpleMob && $entity->getName() === $this->getName() && !$entity->isBaby()){
				$diff = $entity->getPosition()->subtractVector($this->location);
				if($diff->lengthSquared() > 4){
					$this->moveTowards($entity->getPosition(), 0.12);
				}
				return;
			}
		}
	}

	private function explode() : void{
		if($this->isClosed()){
			return;
		}
		$ev = new EntityPreExplodeEvent($this, 3);
		$ev->call();
		if(!$ev->isCancelled()){
			$explosion = new Explosion(
				Position::fromObject($this->location->add(0, $this->size->getHeight() / 2, 0), $this->getWorld()),
				$ev->getRadius(),
				$this,
				$ev->getFireChance()
			);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
		$this->flagForDespawn();
	}

	private function teleportRandomly() : void{
		$world = $this->getWorld();
		for($i = 0; $i < 10; ++$i){
			$x = $this->location->getFloorX() + mt_rand(-8, 8);
			$y = $this->location->getFloorY() + mt_rand(-3, 3);
			$z = $this->location->getFloorZ() + mt_rand(-8, 8);
			if(PathFinder::canStand($world, $x, $y, $z)){
				$this->teleport(new Position($x + 0.5, $y, $z + 0.5, $world));
				return;
			}
		}
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);
		if(!$source->isCancelled() && !$this->isHostile() && $source instanceof EntityDamageByEntityEvent){
			$damager = $source->getDamager();
			if($damager !== null){
				$this->fleeTicks = 100;
				$this->fleeFrom = $damager->getPosition()->asVector3();
			}
		}
	}

	private function isHostile() : bool{
		return in_array($this->getName(), self::HOSTILE, true);
	}

	private function tickHostile(int $currentTick) : void{
		$player = $this->getNearestPlayer(16);
		if($player === null){
			$this->path = null;
			if($currentTick % 40 === 0 && mt_rand(0, 3) === 0 && $this->isOnGround()){
				$this->setMotion(new Vector3(mt_rand(-10, 10) / 100, 0.2, mt_rand(-10, 10) / 100));
			}
			return;
		}

		$distance = $this->location->distance($player->getLocation());
		if($distance <= 1.5){
			$this->setMotion(new Vector3(0, $this->getMotion()->y, 0));
			if($currentTick % 20 === 0){
				$player->attack(new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 2));
			}
			return;
		}

		if($this->pathCooldown > 0){
			--$this->pathCooldown;
		}else{
			$this->path = PathFinder::findPath($this->getWorld(), $this->location, $player->getLocation());
			$this->pathIndex = 0;
			$this->pathCooldown = 20;
		}

		$waypoint = $this->path[$this->pathIndex] ?? null;
		while($waypoint !== null && $this->location->distanceSquared($waypoint) < 0.4){
			$this->pathIndex++;
			$waypoint = $this->path[$this->pathIndex] ?? null;
		}

		if($waypoint !== null){
			$this->moveTowards($waypoint, 0.22);
		}else{
			$diff = $player->getPosition()->subtractVector($this->location);
			$this->moveTowards($this->location->addVector($diff), 0.22);
		}
	}

	private function tickFlee() : void{
		--$this->fleeTicks;
		if($this->fleeTicks <= 0 || $this->fleeFrom === null){
			$this->fleeTicks = 0;
			$this->fleeFrom = null;
			return;
		}
		$diff = $this->location->subtractVector($this->fleeFrom);
		if($diff->lengthSquared() < 0.01){
			return;
		}
		$this->moveTowards($this->location->addVector($diff), 0.25);
	}

	private function moveTowards(Vector3 $target, float $speed) : void{
		$diff = $target->subtractVector($this->location);
		$diff->y = 0;
		if($diff->lengthSquared() < 0.0001){
			return;
		}
		$motion = $diff->normalize()->multiply($speed);
		if($target->y > $this->location->y + 0.6 && $this->isOnGround()){
			$motion->y = 0.35;
		}
		$this->setMotion($motion);
	}

	protected function onDeath() : void{
		parent::onDeath();
		if(!$this->baby && ($this->getName() === "Slime" || $this->getName() === "Magma Cube")){
			for($i = 0; $i < 2; ++$i){
				$child = $this->createBaby(Location::fromObject($this->location->add(mt_rand(-1, 1), 0, mt_rand(-1, 1)), $this->getWorld(), $this->location->yaw, 0));
				$child->setBaby(true);
				$child->spawnToAll();
			}
		}
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		if($this->handleSpecialInteraction($player)){
			return true;
		}
		if($this->baby || $this->breedingCooldown > 0 || $this->inLoveTicks > 0){
			return false;
		}

		$item = $player->getInventory()->getItemInHand();
		if(!$this->isBreedingFood($item)){
			return false;
		}

		$item->pop();
		$this->inLoveTicks = 600;
		$this->getWorld()->addParticle($this->location->add(0, $this->size->getHeight(), 0), new HeartParticle());
		return true;
	}

	private function tryBreed() : void{
		$box = AxisAlignedBB::one()
			->expand(8, 8, 8)
			->offset($this->location->x, $this->location->y, $this->location->z);
		foreach($this->getWorld()->getNearbyEntities($box, $this) as $entity){
			if(
				$entity instanceof SimpleMob &&
				$entity->getName() === $this->getName() &&
				$entity->inLoveTicks > 0 &&
				!$entity->baby
			){
				$location = Location::fromObject(
					$this->location->addVector($entity->location)->multiply(0.5),
					$this->getWorld(),
					$this->location->yaw,
					0
				);
				$baby = $this->createBaby($location);
				$baby->setBaby(true);
				$baby->spawnToAll();

				$this->inLoveTicks = 0;
				$entity->inLoveTicks = 0;
				$this->breedingCooldown = 6000;
				$entity->breedingCooldown = 6000;
				return;
			}
		}
	}

	private function isBreedingFood(Item $item) : bool{
		return match($this->getName()){
			"Cow", "Mooshroom", "Sheep" => $item->getTypeId() === ItemTypeIds::WHEAT,
			"Pig" => $item instanceof \pocketmine\item\Carrot || $item instanceof \pocketmine\item\Potato || $item instanceof \pocketmine\item\Beetroot,
			"Chicken" => $item instanceof \pocketmine\item\WheatSeeds || $item instanceof \pocketmine\item\MelonSeeds || $item instanceof \pocketmine\item\PumpkinSeeds || $item instanceof \pocketmine\item\BeetrootSeeds,
			"Rabbit" => $item instanceof \pocketmine\item\Carrot || ItemTypeIds::toBlockTypeId($item->getTypeId()) === BlockTypeIds::DANDELION,
			"Wolf" => $item instanceof \pocketmine\item\RawBeef || $item instanceof \pocketmine\item\RawPorkchop || $item instanceof \pocketmine\item\RawChicken || $item instanceof \pocketmine\item\RawMutton || $item instanceof \pocketmine\item\RawRabbit || $item instanceof \pocketmine\item\RawFish || $item instanceof \pocketmine\item\RawSalmon || $item instanceof \pocketmine\item\Clownfish || $item instanceof \pocketmine\item\Pufferfish,
			"Horse", "Donkey", "Mule", "Llama", "Trader Llama" => $item instanceof \pocketmine\item\GoldenApple || $item instanceof \pocketmine\item\GoldenCarrot,
			default => false
		};
	}

	private function getNearestHostileMob(float $radius) : ?SimpleMob{
		$box = AxisAlignedBB::one()->expand($radius, $radius, $radius)->offset($this->location->x, $this->location->y, $this->location->z);
		$nearest = null;
		$bestDistance = $radius * $radius;
		foreach($this->getWorld()->getNearbyEntities($box, $this) as $entity){
			if($entity instanceof self && $entity !== $this && $entity->isAlive() && in_array($entity->getName(), self::HOSTILE, true)){
				$distance = $this->location->distanceSquared($entity->getLocation());
				if($distance < $bestDistance){
					$bestDistance = $distance;
					$nearest = $entity;
				}
			}
		}
		return $nearest;
	}

	private function getNearestPlayer(float $radius) : ?Player{
		$box = AxisAlignedBB::one()
			->expand($radius, $radius, $radius)
			->offset($this->location->x, $this->location->y, $this->location->z);
		$nearest = null;
		$bestDistance = $radius * $radius;
		foreach($this->getWorld()->getNearbyEntities($box, $this) as $entity){
			if($entity instanceof Player && $entity->isAlive() && !$entity->isSpectator()){
				$distance = $this->location->distanceSquared($entity->getPosition());
				if($distance < $bestDistance){
					$bestDistance = $distance;
					$nearest = $entity;
				}
			}
		}
		return $nearest;
	}

	private function getWoolDrop() : Item{
		$wool = VanillaBlocks::WOOL();
		if($this->woolColor !== null){
			$wool->setColor($this->woolColor);
		}
		return $wool->asItem();
	}

	public function getDrops() : array{
		return match($this->getName()){
			"Cow" => [VanillaItems::RAW_BEEF()->setCount(mt_rand(1, 3)), VanillaItems::LEATHER()->setCount(mt_rand(0, 2))],
			"Pig" => [VanillaItems::RAW_PORKCHOP()->setCount(mt_rand(1, 3))],
			"Sheep" => [VanillaItems::RAW_MUTTON()->setCount(mt_rand(1, 2)), $this->getWoolDrop()],
			"Chicken" => [VanillaItems::RAW_CHICKEN()->setCount(1), VanillaItems::FEATHER()->setCount(mt_rand(0, 2))],
			"Rabbit" => [VanillaItems::RAW_RABBIT()->setCount(mt_rand(0, 1)), VanillaItems::RABBIT_HIDE()->setCount(mt_rand(0, 1)), VanillaItems::RABBIT_FOOT()->setCount(mt_rand(0, 1))],
			"Cod" => [VanillaItems::RAW_FISH()->setCount(1)],
			"Salmon" => [VanillaItems::RAW_SALMON()->setCount(1)],
			"Tropical Fish" => [VanillaItems::CLOWNFISH()->setCount(1)],
			"Pufferfish" => [VanillaItems::PUFFERFISH()->setCount(1)],
			"Zombie", "Husk", "Drowned", "Zombie Villager", "Zombie Pigman" => [VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))],
			"Skeleton", "Stray", "Bogged", "Wither Skeleton" => [VanillaItems::BONE()->setCount(mt_rand(0, 2)), VanillaItems::ARROW()->setCount(mt_rand(0, 2))],
			"Spider", "Cave Spider" => [VanillaItems::STRING()->setCount(mt_rand(0, 2)), VanillaItems::SPIDER_EYE()->setCount(mt_rand(0, 1))],
			"Creeper" => [VanillaItems::GUNPOWDER()->setCount(mt_rand(0, 2))],
			"Enderman" => [VanillaItems::ENDER_PEARL()->setCount(mt_rand(0, 1))],
			"Slime" => [VanillaItems::SLIMEBALL()->setCount(mt_rand(0, 2))],
			"Magma Cube" => [VanillaItems::MAGMA_CREAM()->setCount(mt_rand(0, 1))],
			"Blaze" => [VanillaItems::BLAZE_ROD()->setCount(mt_rand(0, 1))],
			"Ghast" => [VanillaItems::GHAST_TEAR()->setCount(mt_rand(0, 1)), VanillaItems::GUNPOWDER()->setCount(mt_rand(0, 2))],
			"Witch" => [VanillaItems::GLOWSTONE_DUST()->setCount(mt_rand(0, 2)), VanillaItems::REDSTONE_DUST()->setCount(mt_rand(0, 2)), VanillaItems::SPIDER_EYE()->setCount(mt_rand(0, 2))],
			"Iron Golem" => [VanillaItems::IRON_INGOT()->setCount(mt_rand(3, 5)), VanillaBlocks::POPPY()->asItem()->setCount(mt_rand(0, 2))],
			"Snow Golem" => [VanillaItems::SNOWBALL()->setCount(mt_rand(0, 15))],
			default => []
		};
	}

	public function getXpDropAmount() : int{
		return in_array($this->getName(), self::HOSTILE, true) ? mt_rand(1, 5) : mt_rand(0, 3);
	}
}
