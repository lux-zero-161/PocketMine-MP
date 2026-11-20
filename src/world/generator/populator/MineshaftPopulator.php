<?php

declare(strict_types=1);

namespace pocketmine\world\generator\populator;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\World;
use pocketmine\world\format\Chunk;

/**
 * Small chunk-local mineshaft segments with plank supports, rails, cobwebs and a loot chest.
 */
class MineshaftPopulator implements Populator{

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random) : void{
		if($random->nextBoundedInt(70) !== 0){
			return;
		}

		$baseX = $chunkX * Chunk::EDGE_LENGTH;
		$baseZ = $chunkZ * Chunk::EDGE_LENGTH;
		$y = $random->nextRange(15, 45);
		$alongX = $random->nextBoolean();
		$length = 12;

		if($alongX){
			$startX = $baseX + 2;
			$startZ = $baseZ + $random->nextRange(2, Chunk::EDGE_LENGTH - 3);
		}else{
			$startX = $baseX + $random->nextRange(2, Chunk::EDGE_LENGTH - 3);
			$startZ = $baseZ + 2;
		}

		for($i = 0; $i < $length; ++$i){
			$x = $startX + ($alongX ? $i : 0);
			$z = $startZ + ($alongX ? 0 : $i);

			for($dx = -1; $dx <= 1; ++$dx){
				for($dz = -1; $dz <= 1; ++$dz){
					for($dy = 0; $dy <= 2; ++$dy){
						$world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::AIR());
					}
					$world->setBlockAt($x + $dx, $y - 1, $z + $dz, VanillaBlocks::OAK_PLANKS());
				}
			}

			$world->setBlockAt($x, $y - 1, $z, $random->nextBoundedInt(4) === 0 ? VanillaBlocks::RAIL() : VanillaBlocks::OAK_PLANKS());

			if($i % 4 === 0){
				for($dy = 0; $dy <= 2; ++$dy){
					$world->setBlockAt($x - 1, $y + $dy, $z, VanillaBlocks::OAK_FENCE());
					$world->setBlockAt($x + 1, $y + $dy, $z, VanillaBlocks::OAK_FENCE());
				}
				$world->setBlockAt($x, $y + 2, $z, VanillaBlocks::OAK_PLANKS());
				$world->setBlockAt($x, $y + 1, $z, VanillaBlocks::TORCH());
				if($random->nextBoundedInt(2) === 0){
					$world->setBlockAt($x - 1, $y + 2, $z, VanillaBlocks::COBWEB());
				}
			}
		}

		$endX = $startX + ($alongX ? $length - 1 : 0);
		$endZ = $startZ + ($alongX ? 0 : $length - 1);
		$world->setBlockAt($endX, $y, $endZ, VanillaBlocks::CHEST());
		if($world instanceof World){
			$tile = $world->getTileAt($endX, $y, $endZ);
			if($tile instanceof \pocketmine\block\tile\Chest){
				$inventory = $tile->getInventory();
				for($slot = 0; $slot < 4; ++$slot){
					$inventory->setItem($random->nextRange(0, $inventory->getSize() - 1), match($random->nextBoundedInt(4)){
						0 => VanillaItems::IRON_INGOT()->setCount($random->nextRange(1, 3)),
						1 => VanillaItems::GOLD_INGOT()->setCount($random->nextRange(1, 2)),
						2 => VanillaItems::COAL()->setCount($random->nextRange(2, 6)),
						default => VanillaItems::BREAD()->setCount($random->nextRange(1, 3))
					});
				}
			}
		}
	}
}
