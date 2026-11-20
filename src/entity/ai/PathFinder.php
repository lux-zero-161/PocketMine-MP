<?php

declare(strict_types=1);

namespace pocketmine\entity\ai;

use pocketmine\math\Vector3;
use pocketmine\world\World;
use function abs;
use function floor;

/**
 * Small grid-based A* path finder for ground mobs.
 *
 * It is intentionally conservative: it only walks on solid floors, can step up one block and fall up to three
 * blocks. It does not understand doors, ladders, water or hazards.
 */
final class PathFinder{
	private const MAX_NODES = 512;
	private const MAX_STEP_UP = 1;
	private const MAX_FALL = 3;

	private function __construct(){
		//NOOP
	}

	/**
	 * @return Vector3[]|null list of waypoints (block centres), or null if no path was found
	 */
	public static function findPath(World $world, Vector3 $start, Vector3 $target, float $maxDistance = 24.0) : ?array{
		$sx = (int) floor($start->x);
		$sy = (int) floor($start->y);
		$sz = (int) floor($start->z);
		$tx = (int) floor($target->x);
		$ty = (int) floor($target->y);
		$tz = (int) floor($target->z);

		if(abs($tx - $sx) + abs($tz - $sz) > $maxDistance){
			return null;
		}

		$startKey = self::key($sx, $sy, $sz);
		$goalKey = self::key($tx, $ty, $tz);

		$open = new \SplPriorityQueue();
		$open->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
		$open->insert([$sx, $sy, $sz], -self::heuristic($sx, $sy, $sz, $tx, $ty, $tz));

		$cameFrom = [];
		$gScore = [$startKey => 0];
		$visited = 0;

		while(!$open->isEmpty()){
			/** @var array{data: array{int, int, int}, priority: int} $current */
			$current = $open->extract();
			[$x, $y, $z] = $current["data"];
			$key = self::key($x, $y, $z);

			if($key === $goalKey || ($x === $tx && $z === $tz && abs($y - $ty) <= 1)){
				return self::reconstruct($cameFrom, $key);
			}

			if(++$visited > self::MAX_NODES){
				break;
			}

			$g = $gScore[$key] ?? PHP_INT_MAX;
			foreach(self::getNeighbours($world, $x, $y, $z) as [$nx, $ny, $nz]){
				$nKey = self::key($nx, $ny, $nz);
				$tentative = $g + 1;
				if($tentative >= ($gScore[$nKey] ?? PHP_INT_MAX)){
					continue;
				}
				$cameFrom[$nKey] = [$x, $y, $z];
				$gScore[$nKey] = $tentative;
				$f = $tentative + self::heuristic($nx, $ny, $nz, $tx, $ty, $tz);
				$open->insert([$nx, $ny, $nz], -$f);
			}
		}

		return null;
	}

	/**
	 * @return array<int, array{int, int, int}>
	 */
	private static function getNeighbours(World $world, int $x, int $y, int $z) : array{
		$result = [];
		foreach([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dz]){
			$nx = $x + $dx;
			$nz = $z + $dz;
			for($dy = self::MAX_STEP_UP; $dy >= -self::MAX_FALL; --$dy){
				$ny = $y + $dy;
				if(self::canStand($world, $nx, $ny, $nz)){
					$result[] = [$nx, $ny, $nz];
					break;
				}
			}
		}
		return $result;
	}

	public static function canStand(World $world, int $x, int $y, int $z) : bool{
		if($y < $world->getMinY() + 1 || $y >= $world->getMaxY() - 1){
			return false;
		}
		$feet = $world->getBlockAt($x, $y, $z);
		$head = $world->getBlockAt($x, $y + 1, $z);
		$floor = $world->getBlockAt($x, $y - 1, $z);
		return $feet->canBeReplaced() && $head->canBeReplaced() && !$floor->canBeReplaced();
	}

	private static function heuristic(int $x, int $y, int $z, int $tx, int $ty, int $tz) : int{
		return abs($x - $tx) + abs($y - $ty) + abs($z - $tz);
	}

	private static function key(int $x, int $y, int $z) : string{
		return $x . ":" . $y . ":" . $z;
	}

	/**
	 * @param array<string, array{int, int, int}> $cameFrom
	 * @return Vector3[]
	 */
	private static function reconstruct(array $cameFrom, string $key) : array{
		$path = [];
		while(isset($cameFrom[$key])){
			[$x, $y, $z] = $cameFrom[$key];
			$path[] = new Vector3($x + 0.5, $y, $z + 0.5);
			$key = self::key($x, $y, $z);
		}
		return array_reverse($path);
	}
}
