<?php

declare(strict_types=1);

namespace pocketmine\block\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Button;
use pocketmine\block\Lever;
use pocketmine\block\RedstoneTorch;
use pocketmine\math\Facing;

final class RedstonePowerHelper{

	private function __construct(){
		//NOOP
	}

	/**
	 * Returns whether any directly adjacent block provides a redstone signal.
	 *
	 * This intentionally only handles direct neighbours; wire propagation is a separate system.
	 */
	public static function isPowered(Block $block) : bool{
		foreach(Facing::ALL as $face){
			$side = $block->getSide($face);
			if(
				($side instanceof Lever && $side->isActivated()) ||
				($side instanceof Button && $side->isPressed()) ||
				($side instanceof RedstoneTorch && $side->isLit()) ||
				$side->getTypeId() === BlockTypeIds::REDSTONE_BLOCK ||
				($side instanceof PoweredByRedstone && $side->isPowered())
			){
				return true;
			}
		}
		return false;
	}
}
