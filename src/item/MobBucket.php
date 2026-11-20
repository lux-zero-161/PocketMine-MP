<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\VanillaBlocks;

class MobBucket extends LiquidBucket{

	public function __construct(ItemIdentifier $identifier, string $name){
		parent::__construct($identifier, $name, VanillaBlocks::WATER());
	}
}
