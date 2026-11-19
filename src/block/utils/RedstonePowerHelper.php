<?php

declare(strict_types=1);

namespace pocketmine\block\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Button;
use pocketmine\block\Lever;
use pocketmine\block\RedstoneTorch;
use pocketmine\block\RedstoneWire;
use pocketmine\block\RedstoneRepeater;
use pocketmine\block\RedstoneComparator;
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
	public static function isPowered(Block $block, ?Block $exclude = null) : bool{
		foreach(Facing::ALL as $face){
			$side = $block->getSide($face);
			if($exclude !== null && $side->getPosition()->equals($exclude->getPosition())){
				continue;
			}
			if(
				($side instanceof Lever && $side->isActivated()) ||
				($side instanceof Button && $side->isPressed()) ||
				($side instanceof RedstoneTorch && $side->isLit()) ||
				$side->getTypeId() === BlockTypeIds::REDSTONE ||
				($side instanceof PoweredByRedstone && $side->isPowered()) ||
				($side instanceof RedstoneWire && $side->getOutputSignalStrength() > 0) ||
				($side instanceof RedstoneRepeater && $side->isPowered()) ||
				($side instanceof RedstoneComparator && $side->isPowered())
			){
				return true;
			}
		}
		return false;
	}

	/**
	 * Recomputes a wire's signal strength from adjacent wires and power sources.
	 * Returns true if the wire changed and needs to be written back to the world.
	 */
	public static function updateWire(RedstoneWire $wire) : bool{
		$strength = 0;
		foreach(Facing::ALL as $face){
			$side = $wire->getSide($face);
			if($side instanceof RedstoneWire){
				$strength = max($strength, $side->getOutputSignalStrength() - 1);
			}elseif(
				($side instanceof Lever && $side->isActivated()) ||
				($side instanceof Button && $side->isPressed()) ||
				($side instanceof RedstoneTorch && $side->isLit()) ||
				$side->getTypeId() === BlockTypeIds::REDSTONE ||
				($side instanceof RedstoneRepeater && $side->isPowered()) ||
				($side instanceof RedstoneComparator && $side->isPowered())
			){
				$strength = 15;
			}
		}
		if($strength !== $wire->getOutputSignalStrength()){
			$wire->setOutputSignalStrength($strength);
			return true;
		}
		return false;
	}
}
