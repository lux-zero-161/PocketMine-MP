<?php

declare(strict_types=1);

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\normal\Normal;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * Generates a few chunks in memory to smoke-test world generation without booting a server.
 *
 * Usage:
 *   php build/audit/worldgen.php
 */

final class InMemoryChunkManager implements ChunkManager{
	/** @var array<int, Chunk> */
	private array $chunks = [];

	public function getBlockAt(int $x, int $y, int $z) : Block{
		$chunk = $this->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
		return RuntimeBlockStateRegistry::getInstance()->fromStateId($chunk->getBlockStateId($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK));
	}

	public function setBlockAt(int $x, int $y, int $z, Block $block) : void{
		$chunk = $this->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
		$chunk->setBlockStateId($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK, $block->getStateId());
	}

	public function getChunk(int $chunkX, int $chunkZ) : Chunk{
		$key = ($chunkX << 32) ^ ($chunkZ & 0xffffffff);
		if(!isset($this->chunks[$key])){
			$this->chunks[$key] = new Chunk([], false);
		}
		return $this->chunks[$key];
	}

	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk) : void{
		$this->chunks[($chunkX << 32) ^ ($chunkZ & 0xffffffff)] = $chunk;
	}

	public function getMinY() : int{ return -64; }

	public function getMaxY() : int{ return 320; }

	public function isInWorld(int $x, int $y, int $z) : bool{
		return $y >= $this->getMinY() && $y < $this->getMaxY();
	}
}

$manager = new InMemoryChunkManager();
$generator = new Normal(12345, "");

$start = hrtime(true);
$errors = 0;
for($chunkX = -1; $chunkX <= 1; ++$chunkX){
	for($chunkZ = -1; $chunkZ <= 1; ++$chunkZ){
		try{
			$generator->generateChunk($manager, $chunkX, $chunkZ);
			$generator->populateChunk($manager, $chunkX, $chunkZ);
		}catch(\Throwable $e){
			$errors++;
			fwrite(STDERR, "Chunk $chunkX,$chunkZ: " . $e->getMessage() . "\n");
		}
	}
}
$elapsed = (hrtime(true) - $start) / 1e9;

printf("Generated 9 chunks in %.3f s (%.1f chunks/s), errors: %d\n", $elapsed, 9 / max($elapsed, 1e-9), $errors);
if($errors > 0){
	exit(1);
}
print("Worldgen smoke test passed.\n");
