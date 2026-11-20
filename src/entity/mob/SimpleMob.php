<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function in_array;
use function mt_rand;

/**
 * Shared base for the generated simple mob implementations.
 *
 * These mobs have basic wandering/hostile movement and simple vanilla drops.
 * Pathfinding, breeding and special behaviours are not implemented yet.
 */
abstract class SimpleMob extends Living{
	private const HOSTILE = [
		"Blaze", "Bogged", "Breeze", "Cave Spider", "Creaking", "Creeper", "Drowned", "Elder Guardian",
		"Enderman", "Endermite", "Evoker", "Frostbite", "Ghast", "Guardian", "Hoglin", "Husk", "Magma Cube",
		"Parched", "Phantom", "Piglin", "Piglin Brute", "Pillager", "Ravager", "Shulker", "Silverfish",
		"Skeleton", "Skeleton Horse", "Slime", "Spider", "Stray", "Vex", "Vindicator", "Warden", "Witch",
		"Wither Skeleton", "Zoglin", "Zombie Horse", "Zombie Nautilus", "Zombie Pigman", "Zombie Villager"
	];

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.8, 0.6);
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->isAlive()){
			if(in_array($this->getName(), self::HOSTILE, true) && $currentTick % 10 === 0){
				$player = $this->getNearestPlayer(16);
				if($player !== null){
					$diff = $player->getPosition()->subtractVector($this->location);
					$distance = $diff->length();
					if($distance > 1.5){
						$this->setMotion($diff->normalize()->multiply(0.22)->add(0, 0.2, 0));
					}elseif($currentTick % 20 === 0){
						$player->attack(new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 2));
					}
				}
			}elseif($currentTick % 40 === 0 && mt_rand(0, 2) === 0 && $this->isOnGround()){
				$this->setMotion(new Vector3(mt_rand(-10, 10) / 100, 0.2, mt_rand(-10, 10) / 100));
			}
		}
		return parent::onUpdate($currentTick);
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

	public function getDrops() : array{
		return match($this->getName()){
			"Cow" => [VanillaItems::RAW_BEEF()->setCount(mt_rand(1, 3)), VanillaItems::LEATHER()->setCount(mt_rand(0, 2))],
			"Pig" => [VanillaItems::RAW_PORKCHOP()->setCount(mt_rand(1, 3))],
			"Sheep" => [VanillaItems::RAW_MUTTON()->setCount(mt_rand(1, 2)), VanillaBlocks::WOOL()->asItem()],
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
