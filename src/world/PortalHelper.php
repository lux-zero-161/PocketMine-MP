<?php

declare(strict_types=1);

namespace pocketmine\world;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\generator\hell\Nether;
use pocketmine\world\generator\VoidGenerator;
use function floor;
use function min;

/**
 * Handles Nether and End portal travel between worlds.
 *
 * This implementation keeps portal destinations simple: it looks for a nearby portal in the target world and
 * otherwise builds a small portal at a safe spawn position.
 */
final class PortalHelper{
	/** @var \WeakMap<Player, int>|null */
	private static ?\WeakMap $cooldowns = null;

	private function __construct(){
		//NOOP
	}

	public static function useNetherPortal(Player $player) : bool{
		if(!self::checkCooldown($player)){
			return false;
		}

		$current = $player->getWorld();
		$worldManager = $current->getServer()->getWorldManager();

		if($current->getDimensionId() === World::DIMENSION_NETHER){
			$targetName = $worldManager->getDefaultWorld()?->getFolderName() ?? "world";
			$dimensionId = World::DIMENSION_OVERWORLD;
			$scale = 8;
		}elseif($current->getDimensionId() === World::DIMENSION_OVERWORLD){
			$targetName = "nether";
			$dimensionId = World::DIMENSION_NETHER;
			$scale = 1 / 8;
		}else{
			return false;
		}

		$target = $worldManager->getWorldByName($targetName);
		if($target === null){
			$options = WorldCreationOptions::create()->setSeed($current->getSeed());
			$options->setGeneratorClass($dimensionId === World::DIMENSION_NETHER ? Nether::class : \pocketmine\world\generator\normal\Normal::class);
			if(!$worldManager->generateWorld($targetName, $options, false)){
				return false;
			}
			$target = $worldManager->getWorldByName($targetName);
			if($target === null){
				return false;
			}
		}

		$x = (int) floor($player->getLocation()->x * $scale);
		$z = (int) floor($player->getLocation()->z * $scale);
		$searchY = min(120, $target->getMaxY() - 2);

		$portal = self::findPortal($target, $x, $searchY, $z, 24);
		if($portal !== null){
			$destination = $portal;
		}else{
			$safe = $target->getSafeSpawn(new Vector3($x, $searchY, $z));
			$x = $safe->getFloorX();
			$y = $safe->getFloorY();
			$z = $safe->getFloorZ();
			self::buildNetherPortal($target, $x, $y, $z);
			$destination = new Position($x + 0.5, $y + 1, $z + 0.5, $target);
		}

		return $player->switchWorld($target, $destination, $dimensionId);
	}

	public static function useEndPortal(Player $player) : bool{
		if(!self::checkCooldown($player)){
			return false;
		}

		$current = $player->getWorld();
		$worldManager = $current->getServer()->getWorldManager();

		if($current->getDimensionId() === World::DIMENSION_THE_END){
			$target = $worldManager->getDefaultWorld();
			if($target === null){
				return false;
			}
			return $player->switchWorld($target, $target->getSafeSpawn(), World::DIMENSION_OVERWORLD);
		}
		if($current->getDimensionId() !== World::DIMENSION_OVERWORLD){
			return false;
		}

		$targetName = "the_end";
		$target = $worldManager->getWorldByName($targetName);
		if($target === null){
			$options = WorldCreationOptions::create()->setSeed($current->getSeed())->setGeneratorClass(VoidGenerator::class);
			if(!$worldManager->generateWorld($targetName, $options, false)){
				return false;
			}
			$target = $worldManager->getWorldByName($targetName);
			if($target === null){
				return false;
			}
		}

		self::buildEndPlatform($target, 0, 64, 0);
		return $player->switchWorld($target, new Position(0.5, 66, 0.5, $target), World::DIMENSION_THE_END);
	}

	private static function checkCooldown(Player $player) : bool{
		$now = $player->getWorld()->getServer()->getTick();
		self::$cooldowns ??= new \WeakMap();
		$last = self::$cooldowns[$player] ?? -100;
		if($now - $last < 50){
			return false;
		}
		self::$cooldowns[$player] = $now;
		return true;
	}

	private static function findPortal(World $world, int $x, int $y, int $z, int $radius) : ?Position{
		for($dx = -$radius; $dx <= $radius; ++$dx){
			for($dy = -4; $dy <= 4; ++$dy){
				for($dz = -$radius; $dz <= $radius; ++$dz){
					$bx = $x + $dx;
					$by = $y + $dy;
					$bz = $z + $dz;
					if(!$world->isInWorld($bx, $by, $bz)){
						continue;
					}
					if($world->getBlockAt($bx, $by, $bz)->getTypeId() === BlockTypeIds::NETHER_PORTAL){
						return new Position($bx + 0.5, $by, $bz + 0.5, $world);
					}
				}
			}
		}
		return null;
	}

	private static function buildNetherPortal(World $world, int $x, int $y, int $z) : void{
		for($dx = -1; $dx <= 2; ++$dx){
			for($dy = 0; $dy <= 4; ++$dy){
				$border = $dx === -1 || $dx === 2 || $dy === 0 || $dy === 4;
				$block = $border ? VanillaBlocks::OBSIDIAN() : VanillaBlocks::NETHER_PORTAL();
				$world->setBlockAt($x + $dx, $y + $dy, $z, $block, false);
			}
		}
	}

	private static function buildEndPlatform(World $world, int $x, int $y, int $z) : void{
		for($dx = -2; $dx <= 2; ++$dx){
			for($dz = -2; $dz <= 2; ++$dz){
				$world->setBlockAt($x + $dx, $y, $z + $dz, VanillaBlocks::END_STONE(), false);
			}
		}
		$world->setBlockAt($x, $y, $z, VanillaBlocks::END_PORTAL(), false);
	}
}
