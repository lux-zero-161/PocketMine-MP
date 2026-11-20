<?php

declare(strict_types=1);

namespace pocketmine\world;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\Water;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\entity\Living;
use pocketmine\entity\ai\PathFinder;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\entity\Villager;
use pocketmine\entity\effect\VanillaEffects;
use function count;
use function max;
use function min;
use function mt_rand;

/**
 * Simple natural mob spawning around players.
 *
 * Spawns passive animals during the day on grass-like surfaces and hostile mobs in low light. It is intentionally
 * conservative to avoid flooding the world with entities.
 */
final class NaturalSpawner{
	private const ATTEMPTS_PER_PLAYER = 4;
	private const MAX_NEARBY_MOBS = 32;
	private const SPAWN_RANGE = 24;

	private const PASSIVE_MOBS = [
		["minecraft:cow", 25],
		["minecraft:pig", 25],
		["minecraft:sheep", 25],
		["minecraft:chicken", 15],
		["minecraft:rabbit", 10]
	];

	/** @var \WeakMap<World, array<int, int>>|null */
	private static ?\WeakMap $lastVillageRaid = null;

	private const HOSTILE_MOBS = [
		["minecraft:zombie", 30],
		["minecraft:skeleton", 25],
		["minecraft:spider", 25],
		["minecraft:creeper", 20]
	];

	private function __construct(){
		//NOOP
	}

	public static function tick(World $world) : void{
		if($world->getDimensionId() !== World::DIMENSION_OVERWORLD){
			return;
		}

		foreach($world->getPlayers() as $player){
			for($i = 0; $i < self::ATTEMPTS_PER_PLAYER; ++$i){
				self::attemptSpawn($world, $player);
			}
			self::spawnVillagers($world, $player);
		}
	}

	private static function attemptSpawn(World $world, Player $player) : void{
		if(self::countNearbyMobs($world, $player) >= self::MAX_NEARBY_MOBS){
			return;
		}

		$playerPos = $player->getPosition();
		$x = $playerPos->getFloorX() + mt_rand(-self::SPAWN_RANGE, self::SPAWN_RANGE);
		$z = $playerPos->getFloorZ() + mt_rand(-self::SPAWN_RANGE, self::SPAWN_RANGE);
		$y = $playerPos->getFloorY() + mt_rand(-8, 8);
		if($y <= $world->getMinY() + 2 || $y >= $world->getMaxY() - 2){
			return;
		}

		$feet = $world->getBlockAt($x, $y, $z);
		$head = $world->getBlockAt($x, $y + 1, $z);
		$floor = $world->getBlockAt($x, $y - 1, $z);

		if($feet instanceof Water && $head instanceof Water){
			if(mt_rand(0, 3) === 0){
				self::spawn($world, Location::fromObject(new \pocketmine\math\Vector3($x + 0.5, $y, $z + 0.5), $world, mt_rand(0, 359), 0), match(mt_rand(0, 3)){
					0 => "minecraft:cod",
					1 => "minecraft:salmon",
					2 => "minecraft:tropical_fish",
					default => "minecraft:squid"
				});
			}
			return;
		}
		if(!$feet->canBeReplaced() || !$head->canBeReplaced() || !$floor->isFullCube() || $floor instanceof \pocketmine\block\Liquid){
			return;
		}

		$light = $world->getFullLightAt($x, $y, $z);
		$time = $world->getTimeOfDay();
		$isDay = $time < 12000;

		$location = Location::fromObject(new \pocketmine\math\Vector3($x + 0.5, $y, $z + 0.5), $world, mt_rand(0, 359), 0);

		if($isDay && $light >= 9 && self::isGrassLike($floor->getTypeId())){
			self::spawn($world, $location, self::pick(self::PASSIVE_MOBS));
		}elseif($light <= 7){
			self::spawn($world, $location, self::pick(self::HOSTILE_MOBS));
		}
	}

	private static function maybeStartVillageRaid(World $world, Player $player, \pocketmine\math\Vector3 $center) : void{
		$tick = $world->getServer()->getTick();
		self::$lastVillageRaid ??= new \WeakMap();
		$entries = self::$lastVillageRaid[$world] ?? [];
		$key = World::blockHash($center->getFloorX(), $center->getFloorY(), $center->getFloorZ());
		if($tick - ($entries[$key] ?? -100000) < 12000){
			return;
		}
		if(mt_rand(0, 1199) !== 0){
			return;
		}
		$entries[$key] = $tick;
		self::$lastVillageRaid[$world] = $entries;
		Raid::startAt($world, new Position($center->x, $center->y, $center->z, $world), 3);
		$player->sendMessage(\pocketmine\utils\TextFormat::RED . "Ein Raid nähert sich dem Dorf!");
	}

	private static function spawnVillagers(World $world, Player $player) : void{
		foreach(VillageRegistry::findNear($world, $player->getPosition(), 48) as $center){
			if($player->getEffects()->has(VanillaEffects::BAD_OMEN())){
				$player->getEffects()->remove(VanillaEffects::BAD_OMEN());
				Raid::startAt($world, new Position($center->x, $center->y, $center->z, $world), 3);
				$player->sendMessage(\pocketmine\utils\TextFormat::RED . "Dein Bad Omen hat einen Raid ausgelöst!");
				return;
			}
			self::maybeStartVillageRaid($world, $player, $center);
			$existing = 0;
			$box = AxisAlignedBB::one()->expand(24, 12, 24)->offset($center->x, $center->y, $center->z);
			foreach($world->getNearbyEntities($box) as $entity){
				if($entity instanceof Villager && $entity->isAlive()){
					$existing++;
				}
			}
			if($existing >= 3){
				continue;
			}
			for($i = 0; $i < 2; ++$i){
				$x = $center->getFloorX() + mt_rand(-6, 6);
				$y = $center->getFloorY() + mt_rand(-1, 2);
				$z = $center->getFloorZ() + mt_rand(-6, 6);
				if(PathFinder::canStand($world, $x, $y, $z)){
					self::spawn($world, Location::fromObject(new \pocketmine\math\Vector3($x + 0.5, $y, $z + 0.5), $world, mt_rand(0, 359), 0), "minecraft:villager");
				}
			}
		}
	}

	private static function spawn(World $world, Location $location, string $type) : void{
		$entity = EntityFactory::getInstance()->createEntity($world, $type, $location);
		$entity?->spawnToAll();
	}

	/**
	 * @param list<array{string, int}> $weights
	 */
	private static function pick(array $weights) : string{
		$total = 0;
		foreach($weights as [, $weight]){
			$total += $weight;
		}
		$roll = mt_rand(1, $total);
		foreach($weights as [$type, $weight]){
			$roll -= $weight;
			if($roll <= 0){
				return $type;
			}
		}
		return "minecraft:pig";
	}

	private static function isGrassLike(int $typeId) : bool{
		return $typeId === BlockTypeIds::GRASS || $typeId === BlockTypeIds::DIRT || $typeId === BlockTypeIds::SAND || $typeId === BlockTypeIds::PODZOL;
	}

	private static function countNearbyMobs(World $world, Player $player) : int{
		$box = AxisAlignedBB::one()->expand(32, 16, 32)->offset($player->getPosition()->x, $player->getPosition()->y, $player->getPosition()->z);
		$count = 0;
		foreach($world->getNearbyEntities($box) as $entity){
			if($entity instanceof Living && !$entity instanceof Player && $entity->isAlive()){
				$count++;
			}
		}
		return $count;
	}
}
