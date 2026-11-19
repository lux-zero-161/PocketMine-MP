<?php

declare(strict_types=1);

namespace pocketmine\world\generator\object;

use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\BlockTransaction;
use pocketmine\world\ChunkManager;

class PoplarTree extends Tree{

	public function __construct(){
		parent::__construct(VanillaBlocks::POPLAR_LOG(), VanillaBlocks::ORANGE_POPLAR_LEAVES());
	}

	public function getBlockTransaction(ChunkManager $world, int $x, int $y, int $z, Random $random) : ?BlockTransaction{
		$this->treeHeight = $random->nextBoundedInt(3) + 5;
		return parent::getBlockTransaction($world, $x, $y, $z, $random);
	}
}
