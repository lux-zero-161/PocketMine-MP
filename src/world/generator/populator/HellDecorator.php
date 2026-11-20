<?php

declare(strict_types=1);

namespace pocketmine\world\generator\populator;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\object\TreeFactory;
use pocketmine\world\generator\object\TreeType;
use function mt_rand;

/**
 * Adds Nether decoration: soul sand/gravel patches, magma, glowstone clusters and fungi trees.
 */
class HellDecorator implements Populator{

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random) : void{
		for($i = 0; $i < 8; ++$i){
			$x = $random->nextRange($chunkX * Chunk::EDGE_LENGTH, $chunkX * Chunk::EDGE_LENGTH + Chunk::EDGE_LENGTH - 1);
			$z = $random->nextRange($chunkZ * Chunk::EDGE_LENGTH, $chunkZ * Chunk::EDGE_LENGTH + Chunk::EDGE_LENGTH - 1);
			$y = $random->nextRange(35, 100);

			$block = $world->getBlockAt($x, $y, $z);
			if($block->getTypeId() !== BlockTypeIds::NETHERRACK || !$world->getBlockAt($x, $y + 1, $z)->canBeReplaced()){
				continue;
			}

			$roll = mt_rand(0, 99);
			if($roll < 25){
				$this->patch($world, $x, $y, $z, VanillaBlocks::SOUL_SAND(), $random);
			}elseif($roll < 50){
				$this->patch($world, $x, $y, $z, VanillaBlocks::GRAVEL(), $random);
			}elseif($roll < 65){
				$world->setBlockAt($x, $y, $z, VanillaBlocks::MAGMA());
			}elseif($roll < 78){
				$this->glowstone($world, $x, $y + 3, $z, $random);
			}else{
				$this->tree($world, $x, $y + 1, $z, $random);
			}
		}
	}

	private function patch(ChunkManager $world, int $x, int $y, int $z, \pocketmine\block\Block $block, Random $random) : void{
		for($dx = -1; $dx <= 1; ++$dx){
			for($dz = -1; $dz <= 1; ++$dz){
				if($random->nextBoundedInt(3) === 0){
					continue;
				}
				if($world->getBlockAt($x + $dx, $y, $z + $dz)->getTypeId() === BlockTypeIds::NETHERRACK){
					$world->setBlockAt($x + $dx, $y, $z + $dz, clone $block);
				}
			}
		}
	}

	private function glowstone(ChunkManager $world, int $x, int $y, int $z, Random $random) : void{
		for($i = 0; $i < 5; ++$i){
			$bx = $x + $random->nextRange(-2, 2);
			$by = $y + $random->nextRange(-2, 2);
			$bz = $z + $random->nextRange(-2, 2);
			if($world->getBlockAt($bx, $by, $bz)->canBeReplaced() && $world->getBlockAt($bx, $by + 1, $bz)->getTypeId() === BlockTypeIds::NETHERRACK){
				$world->setBlockAt($bx, $by, $bz, VanillaBlocks::GLOWSTONE());
			}
		}
	}

	private function tree(ChunkManager $world, int $x, int $y, int $z, Random $random) : void{
		if($world->getBlockAt($x, $y - 1, $z)->getTypeId() !== BlockTypeIds::NETHERRACK || !$world->getBlockAt($x, $y, $z)->canBeReplaced()){
			return;
		}
		for($dy = 1; $dy <= 4; ++$dy){
			if(!$world->getBlockAt($x, $y + $dy, $z)->canBeReplaced()){
				return;
			}
		}
		$tree = TreeFactory::get($random, $random->nextFloat() < 0.5 ? TreeType::CRIMSON : TreeType::WARPED);
		$transaction = $tree?->getBlockTransaction($world, $x, $y, $z, $random);
		$transaction?->apply();
	}
}
