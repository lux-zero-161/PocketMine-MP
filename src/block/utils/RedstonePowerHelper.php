<?php

declare(strict_types=1);

namespace pocketmine\block\utils;

use pocketmine\block\Beehive;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Button;
use pocketmine\block\Cake;
use pocketmine\block\Composter;
use pocketmine\block\Jukebox;
use pocketmine\block\Lever;
use pocketmine\block\RedstoneTorch;
use pocketmine\block\RedstoneWire;
use pocketmine\block\RedstoneRepeater;
use pocketmine\block\RedstoneComparator;
use pocketmine\block\tile\Container;
use pocketmine\inventory\Inventory;
use pocketmine\math\Facing;
use function floor;
use function min;

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
			//$face points from $block to $side, so the opposite is the direction the source emits in
			if(self::providesPowerTo($side, Facing::opposite($face))){
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether the given source block emits a redstone signal in the given direction.
	 *
	 * Directional components (repeaters and comparators) only power the block they face; other sources power all
	 * adjacent blocks.
	 *
	 * @param int $facing one of Facing::*, the direction from the source towards the consumer
	 */
	public static function providesPowerTo(Block $source, int $facing) : bool{
		return self::getSignalStrengthTowards($source, $facing) > 0;
	}

	/**
	 * Returns the signal strength the given source block emits in the given direction.
	 *
	 * @param int $facing one of Facing::*, the direction from the source towards the consumer
	 */
	public static function getSignalStrengthTowards(Block $source, int $facing) : int{
		if($source instanceof RedstoneRepeater){
			return $source->isPowered() && $source->getFacing() === $facing ? 15 : 0;
		}
		if($source instanceof RedstoneComparator){
			return $source->isPowered() && $source->getFacing() === $facing ? $source->getOutputSignalStrength() : 0;
		}
		return self::getEmittedSignalStrength($source);
	}

	/**
	 * Returns the analog signal strength a block provides to a comparator, including container fullness and
	 * blocks with a dedicated comparator output such as composters and cake.
	 */
	public static function getAnalogSignalStrength(Block $block) : int{
		if($block instanceof RedstoneRepeater || $block instanceof RedstoneComparator){
			//directional components are handled by getSignalStrengthTowards()
			return 0;
		}
		if($block instanceof Composter){
			return $block->getFillLevel();
		}
		if($block instanceof Cake){
			//7 slices, each one worth 2 signal strength
			return (7 - $block->getBites()) * 2;
		}
		if($block instanceof Beehive){
			return $block->getHoneyLevel();
		}
		if($block instanceof Jukebox){
			return $block->getComparatorOutput();
		}

		$emitted = self::getEmittedSignalStrength($block);
		if($emitted > 0){
			return $emitted;
		}

		$world = $block->getPosition()->world;
		if($world !== null){
			$tile = $world->getTile($block->getPosition());
			if($tile instanceof Container){
				return self::getContainerSignalStrength($tile->getRealInventory());
			}
		}

		return 0;
	}

	private static function getContainerSignalStrength(Inventory $inventory) : int{
		$size = $inventory->getSize();
		if($size === 0){
			return 0;
		}

		$fill = 0.0;
		foreach($inventory->getContents() as $item){
			$fill += $item->getCount() / $item->getMaxStackSize();
		}
		if($fill <= 0.0){
			return 0;
		}

		return (int) min(15, floor($fill / $size * 14) + 1);
	}

	/**
	 * Returns the signal strength emitted by the given block itself, ignoring neighbouring blocks.
	 *
	 * Wire propagation and directional output of repeaters/comparators are handled by {@link providesPowerTo()}.
	 */
	public static function getEmittedSignalStrength(Block $block) : int{
		if($block instanceof AnalogRedstoneSignalEmitter){
			return $block->getOutputSignalStrength();
		}
		if(
			($block instanceof Lever && $block->isActivated()) ||
			($block instanceof Button && $block->isPressed()) ||
			($block instanceof RedstoneTorch && $block->isLit()) ||
			$block->getTypeId() === BlockTypeIds::REDSTONE
		){
			return 15;
		}
		return 0;
	}

	/**
	 * Returns whether a repeater or comparator is locked by a powered repeater or comparator pointing into its side.
	 */
	public static function isLocked(Block $block) : bool{
		if(!$block instanceof RedstoneRepeater && !$block instanceof RedstoneComparator){
			throw new \InvalidArgumentException("Only repeaters and comparators can be locked");
		}
		$facing = $block->getFacing();

		foreach([Facing::rotateY($facing, false), Facing::rotateY($facing, true)] as $side){
			$neighbour = $block->getSide($side);
			if(
				($neighbour instanceof RedstoneRepeater || $neighbour instanceof RedstoneComparator) &&
				$neighbour->isPowered() &&
				$neighbour->getFacing() === Facing::opposite($side)
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
			}else{
				$strength = max($strength, self::getSignalStrengthTowards($side, Facing::opposite($face)));
			}
		}
		if($strength !== $wire->getOutputSignalStrength()){
			$wire->setOutputSignalStrength($strength);
			return true;
		}
		return false;
	}
}
