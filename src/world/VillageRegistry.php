<?php

declare(strict_types=1);

namespace pocketmine\world;

use pocketmine\math\Vector3;

/**
 * Tracks village bell positions discovered while chunks are loaded.
 */
final class VillageRegistry{
	/** @var \WeakMap<World, array<int, Vector3>>|null */
	private static ?\WeakMap $centers = null;

	private function __construct(){
		//NOOP
	}

	public static function register(World $world, Vector3 $position) : void{
		self::$centers ??= new \WeakMap();
		$entries = self::$centers[$world] ?? [];
		$key = World::blockHash($position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
		$entries[$key] = $position->asVector3();
		self::$centers[$world] = $entries;
	}

	/**
	 * @return Vector3[]
	 */
	public static function findNear(World $world, Vector3 $position, float $radius) : array{
		if(self::$centers === null || !isset(self::$centers[$world])){
			return [];
		}
		$radiusSquared = $radius * $radius;
		$result = [];
		foreach(self::$centers[$world] as $center){
			if($position->distanceSquared($center) <= $radiusSquared){
				$result[] = $center;
			}
		}
		return $result;
	}
}
