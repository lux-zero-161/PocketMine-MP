<?php

declare(strict_types=1);

namespace pocketmine\world\generator\populator;

use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use function abs;
use function crc32;
use function floor;

/**
 * Generates small deterministic village houses and a central well/bell.
 *
 * Villages occupy a 256x256 region and are split per chunk, so buildings that cross chunk borders are completed as
 * neighbouring chunks are generated.
 */
class VillagePopulator implements Populator{
	private const REGION_SIZE = 256;
	private const VILLAGE_RADIUS = 56;

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random) : void{
		$baseX = $chunkX * Chunk::EDGE_LENGTH;
		$baseZ = $chunkZ * Chunk::EDGE_LENGTH;
		$minRegionX = (int) floor(($baseX - self::VILLAGE_RADIUS) / self::REGION_SIZE);
		$maxRegionX = (int) floor(($baseX + Chunk::EDGE_LENGTH + self::VILLAGE_RADIUS) / self::REGION_SIZE);
		$minRegionZ = (int) floor(($baseZ - self::VILLAGE_RADIUS) / self::REGION_SIZE);
		$maxRegionZ = (int) floor(($baseZ + Chunk::EDGE_LENGTH + self::VILLAGE_RADIUS) / self::REGION_SIZE);

		for($regionX = $minRegionX; $regionX <= $maxRegionX; ++$regionX){
			for($regionZ = $minRegionZ; $regionZ <= $maxRegionZ; ++$regionZ){
				if($this->hash($regionX, $regionZ) % 6 !== 0){
					continue;
				}
				$centerX = $regionX * self::REGION_SIZE + 48 + $this->hash($regionX, $regionZ, 1) % 160;
				$centerZ = $regionZ * self::REGION_SIZE + 48 + $this->hash($regionX, $regionZ, 2) % 160;
				if(abs($baseX - $centerX) > self::VILLAGE_RADIUS || abs($baseZ - $centerZ) > self::VILLAGE_RADIUS){
					continue;
				}
				$this->buildVillage($world, $centerX, $centerZ, $baseX, $baseZ);
			}
		}
	}

	private function buildVillage(ChunkManager $world, int $centerX, int $centerZ, int $baseX, int $baseZ) : void{
		$wellY = $this->groundY($world, $centerX, $centerZ);
		if($wellY !== null){
			for($dx = -1; $dx <= 1; ++$dx){
				for($dz = -1; $dz <= 1; ++$dz){
					$this->place($world, $centerX + $dx, $wellY - 1, $centerZ + $dz, VanillaBlocks::COBBLESTONE(), $baseX, $baseZ);
				}
			}
			$this->place($world, $centerX, $wellY, $centerZ, VanillaBlocks::BELL(), $baseX, $baseZ);
		}

		foreach([[-14, 0], [14, 0], [0, -14], [0, 14]] as [$dx, $dz]){
			$this->buildHouse($world, $centerX + $dx, $centerZ + $dz, $baseX, $baseZ);
		}

		$this->buildFarm($world, $centerX + 14, $centerZ + 14, $baseX, $baseZ);
		$this->buildLampPosts($world, $centerX, $centerZ, $wellY, $baseX, $baseZ);
	}

	private function buildFarm(ChunkManager $world, int $centerX, int $centerZ, int $baseX, int $baseZ) : void{
		$groundY = $this->groundY($world, $centerX, $centerZ);
		if($groundY === null){
			return;
		}
		$crops = [VanillaBlocks::WHEAT(), VanillaBlocks::CARROTS(), VanillaBlocks::POTATOES()];
		for($x = -3; $x <= 3; ++$x){
			for($z = -2; $z <= 2; ++$z){
				$wx = $centerX + $x;
				$wz = $centerZ + $z;
				if($z === 0){
					$this->place($world, $wx, $groundY - 1, $wz, VanillaBlocks::WATER(), $baseX, $baseZ);
				}else{
					$this->place($world, $wx, $groundY - 1, $wz, VanillaBlocks::FARMLAND(), $baseX, $baseZ);
					$crop = clone $crops[abs($x + $z) % 3];
					$crop->setAge($crop->getMaxAge());
					$this->place($world, $wx, $groundY, $wz, $crop, $baseX, $baseZ);
				}
			}
		}
		for($x = -4; $x <= 4; ++$x){
			$this->place($world, $centerX + $x, $groundY, $centerZ - 3, VanillaBlocks::OAK_FENCE(), $baseX, $baseZ);
			$this->place($world, $centerX + $x, $groundY, $centerZ + 3, VanillaBlocks::OAK_FENCE(), $baseX, $baseZ);
		}
	}

	private function buildLampPosts(ChunkManager $world, int $centerX, int $centerZ, ?int $groundY, int $baseX, int $baseZ) : void{
		if($groundY === null){
			return;
		}
		foreach([[7, 7], [-7, 7], [7, -7], [-7, -7]] as [$dx, $dz]){
			$x = $centerX + $dx;
			$z = $centerZ + $dz;
			$this->place($world, $x, $groundY, $z, VanillaBlocks::OAK_FENCE(), $baseX, $baseZ);
			$this->place($world, $x, $groundY + 1, $z, VanillaBlocks::TORCH(), $baseX, $baseZ);
		}
	}

	private function buildHouse(ChunkManager $world, int $centerX, int $centerZ, int $baseX, int $baseZ) : void{
		$groundY = $this->groundY($world, $centerX, $centerZ);
		if($groundY === null){
			return;
		}

		$planks = VanillaBlocks::OAK_PLANKS();
		$logs = VanillaBlocks::OAK_LOG();
		$cobble = VanillaBlocks::COBBLESTONE();

		for($x = -3; $x <= 3; ++$x){
			for($z = -3; $z <= 3; ++$z){
				$wx = $centerX + $x;
				$wz = $centerZ + $z;
				$this->place($world, $wx, $groundY - 1, $wz, $cobble, $baseX, $baseZ);

				$border = abs($x) === 3 || abs($z) === 3;
				$corner = abs($x) === 3 && abs($z) === 3;
				if($border){
					$material = $corner ? $logs : $planks;
					for($y = 0; $y < 3; ++$y){
						//door gap at the front (south side)
						if($z === 3 && $x === 0 && $y < 2){
							$this->place($world, $wx, $groundY + $y, $wz, VanillaBlocks::AIR(), $baseX, $baseZ);
						}else{
							$this->place($world, $wx, $groundY + $y, $wz, $material, $baseX, $baseZ);
						}
					}
				}else{
					for($y = 0; $y < 3; ++$y){
						$this->place($world, $wx, $groundY + $y, $wz, VanillaBlocks::AIR(), $baseX, $baseZ);
					}
				}

				$this->place($world, $wx, $groundY + 3, $wz, $planks, $baseX, $baseZ);
			}
		}

		$this->place($world, $centerX, $groundY, $centerZ, VanillaBlocks::TORCH(), $baseX, $baseZ);
	}

	private function groundY(ChunkManager $world, int $x, int $z) : ?int{
		$chunk = $world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
		if($chunk === null){
			return null;
		}
		$highest = $chunk->getHighestBlockAt($x & Chunk::COORD_MASK, $z & Chunk::COORD_MASK);
		if($highest === null || $highest <= $world->getMinY()){
			return null;
		}
		if($world->getBlockAt($x, $highest, $z) instanceof Liquid){
			return null;
		}
		return $highest + 1;
	}

	private function place(ChunkManager $world, int $x, int $y, int $z, Block $block, int $baseX, int $baseZ) : void{
		if($x < $baseX || $x >= $baseX + Chunk::EDGE_LENGTH || $z < $baseZ || $z >= $baseZ + Chunk::EDGE_LENGTH){
			return;
		}
		if($y < $world->getMinY() || $y >= $world->getMaxY()){
			return;
		}
		$world->setBlockAt($x, $y, $z, $block);
	}

	private function hash(int $x, int $z, int $salt = 0) : int{
		return crc32("village:$x:$z:$salt") & 0x7fffffff;
	}
}
