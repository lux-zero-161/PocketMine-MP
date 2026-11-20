<?php

declare(strict_types=1);

namespace pocketmine\entity\mob;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
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

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
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
