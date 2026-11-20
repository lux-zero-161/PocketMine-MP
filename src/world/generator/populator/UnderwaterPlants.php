<?php

declare(strict_types=1);

namespace pocketmine\world\generator\populator;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\Water;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use function in_array;
use function mt_rand;

/**
 * Places kelp and seagrass on underwater floors.
 */
class UnderwaterPlants implements Populator{
	private int $randomAmount = 6;

	public function setRandomAmount(int $amount) : void{
		$this->randomAmount = $amount;
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random) : void{
		$amount = $random->nextRange(0, $this->randomAmount);
		for($i = 0; $i < $amount; ++$i){
			$x = $random->nextRange($chunkX * Chunk::EDGE_LENGTH, $chunkX * Chunk::EDGE_LENGTH + (Chunk::EDGE_LENGTH - 1));
			$z = $random->nextRange($chunkZ * Chunk::EDGE_LENGTH, $chunkZ * Chunk::EDGE_LENGTH + (Chunk::EDGE_LENGTH - 1));
			$floorY = $this->findFloor($world, $x, $z);
			if($floorY === null || !($world->getBlockAt($x, $floorY + 1, $z) instanceof Water)){
				continue;
			}

			$floor = $world->getBlockAt($x, $floorY, $z);
			if(!in_array($floor->getTypeId(), [BlockTypeIds::GRAVEL, BlockTypeIds::SAND, BlockTypeIds::DIRT, BlockTypeIds::CLAY], true)){
				continue;
			}

			if(mt_rand(0, 2) === 0){
				$world->setBlockAt($x, $floorY + 1, $z, VanillaBlocks::SEAGRASS());
			}else{
				$height = mt_rand(1, 3);
				for($y = 1; $y <= $height; ++$y){
					if(!($world->getBlockAt($x, $floorY + $y, $z) instanceof Water)){
						break;
					}
					$world->setBlockAt($x, $floorY + $y, $z, VanillaBlocks::KELP()->setAge(mt_rand(0, 10)));
				}
			}
		}
	}

	private function findFloor(ChunkManager $world, int $x, int $z) : ?int{
		$chunk = $world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
		if($chunk === null){
			return null;
		}
		$highest = $chunk->getHighestBlockAt($x & Chunk::COORD_MASK, $z & Chunk::COORD_MASK);
		if($highest === null){
			return null;
		}
		for($y = $highest; $y > $world->getMinY(); --$y){
			$block = $world->getBlockAt($x, $y, $z);
			if(!$block instanceof Water && !$block->canBeReplaced()){
				return $y;
			}
		}
		return null;
	}
}
