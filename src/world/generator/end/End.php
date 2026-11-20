<?php

declare(strict_types=1);

namespace pocketmine\world\generator\end;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\InvalidGeneratorOptionsException;
use pocketmine\world\generator\noise\Simplex;
use pocketmine\world\World;
use function sqrt;

/**
 * Simplified End dimension generator with a central island and scattered outer islands.
 */
class End extends Generator{
	private Simplex $noise;

	/**
	 * @throws InvalidGeneratorOptionsException
	 */
	public function __construct(int $seed, string $preset){
		parent::__construct($seed, $preset);

		$this->noise = new Simplex($this->random, 2, 1 / 2, 1 / 64);
		$this->random->setSeed($this->seed);
	}

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void{
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);

		$chunk = $world->getChunk($chunkX, $chunkZ) ?? throw new \InvalidArgumentException("Chunk $chunkX $chunkZ does not yet exist");
		$endStone = VanillaBlocks::END_STONE()->getStateId();

		$baseX = $chunkX * Chunk::EDGE_LENGTH;
		$baseZ = $chunkZ * Chunk::EDGE_LENGTH;

		for($x = 0; $x < Chunk::EDGE_LENGTH; ++$x){
			for($z = 0; $z < Chunk::EDGE_LENGTH; ++$z){
				for($y = World::Y_MIN; $y < World::Y_MAX; ++$y){
					$chunk->setBiomeId($x, $y, $z, BiomeIds::THE_END);
				}

				$worldX = $baseX + $x;
				$worldZ = $baseZ + $z;
				$distance = sqrt($worldX * $worldX + $worldZ * $worldZ);

				$top = null;
				$bottom = null;
				if($distance < 64){
					$top = 66 - (int) ($distance / 8);
					$bottom = 55 + (int) ($distance / 16);
				}else{
					$noise = $this->noise->getNoise2D($worldX / 96, $worldZ / 96);
					if($noise > 0.55){
						$top = 64 + (int) (($noise - 0.55) * 24);
						$bottom = 52 + (int) (($noise - 0.55) * 8);
					}
				}

				if($top === null || $bottom === null){
					continue;
				}
				for($y = $bottom; $y <= $top; ++$y){
					if($y >= 0 && $y < 128){
						$chunk->setBlockStateId($x, $y, $z, $endStone);
					}
				}
			}
		}
	}

	public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void{
		//Occasional obsidian pillars on the central island
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
		if($chunkX === 0 && $chunkZ === 0){
			$world->setBlockAt(3, 67, 0, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(3, 68, 0, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(-3, 67, 0, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(-3, 68, 0, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(0, 67, 3, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(0, 68, 3, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(0, 67, -3, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(0, 68, -3, VanillaBlocks::OBSIDIAN());
			$world->setBlockAt(0, 66, 0, VanillaBlocks::BEDROCK());
		}
	}
}
