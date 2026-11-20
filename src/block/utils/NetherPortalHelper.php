<?php

declare(strict_types=1);

namespace pocketmine\block\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\NetherPortal;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;

final class NetherPortalHelper{
	private const MIN_WIDTH = 2;
	private const MIN_HEIGHT = 3;
	private const MAX_WIDTH = 21;
	private const MAX_HEIGHT = 21;

	private function __construct(){
		//NOOP
	}

	/**
	 * Tries to build a nether portal around the given (air) position.
	 *
	 * @return bool true if a portal was created
	 */
	public static function tryActivate(World $world, Vector3 $pos) : bool{
		foreach([Axis::X, Axis::Z] as $axis){
			if(self::tryActivateOnAxis($world, $pos, $axis)){
				return true;
			}
		}

		return false;
	}

	private static function tryActivateOnAxis(World $world, Vector3 $pos, int $axis) : bool{
		$startX = $pos->getFloorX();
		$startY = $pos->getFloorY();
		$startZ = $pos->getFloorZ();

		$stepX = $axis === Axis::X ? 1 : 0;
		$stepZ = $axis === Axis::Z ? 1 : 0;

		$start = $world->getBlockAt($startX, $startY, $startZ);
		if(!self::canBePortalSpace($start)){
			return false;
		}

		$getBlock = fn(int $horizontal, int $y) : Block => $world->getBlockAt(
			$startX + $horizontal * $stepX,
			$y,
			$startZ + $horizontal * $stepZ
		);

		$left = self::findFrame($getBlock, 0, $startY, -1, self::MAX_WIDTH + 1);
		$right = self::findFrame($getBlock, 0, $startY, 1, self::MAX_WIDTH + 1);
		if($left === null || $right === null){
			return false;
		}

		$bottom = self::findVerticalFrame($getBlock, 0, $startY, -1, self::MAX_HEIGHT + 1);
		$top = self::findVerticalFrame($getBlock, 0, $startY, 1, self::MAX_HEIGHT + 1);
		if($bottom === null || $top === null){
			return false;
		}

		$minHorizontal = $left + 1;
		$maxHorizontal = $right - 1;
		$minY = $bottom + 1;
		$maxY = $top - 1;

		$width = $maxHorizontal - $minHorizontal + 1;
		$height = $maxY - $minY + 1;
		if($width < self::MIN_WIDTH || $width > self::MAX_WIDTH || $height < self::MIN_HEIGHT || $height > self::MAX_HEIGHT){
			return false;
		}

		//the whole interior must be replaceable
		for($h = $minHorizontal; $h <= $maxHorizontal; ++$h){
			for($y = $minY; $y <= $maxY; ++$y){
				if(!self::canBePortalSpace($getBlock($h, $y))){
					return false;
				}
			}
		}

		//check the obsidian frame
		for($h = $left; $h <= $right; ++$h){
			if(!self::isObsidian($getBlock($h, $bottom)) || !self::isObsidian($getBlock($h, $top))){
				return false;
			}
		}
		for($y = $bottom; $y <= $top; ++$y){
			if(!self::isObsidian($getBlock($left, $y)) || !self::isObsidian($getBlock($right, $y))){
				return false;
			}
		}

		$portal = VanillaBlocks::NETHER_PORTAL()->setAxis($axis);
		for($h = $minHorizontal; $h <= $maxHorizontal; ++$h){
			for($y = $minY; $y <= $maxY; ++$y){
				$world->setBlockAt(
					$startX + $h * $stepX,
					$y,
					$startZ + $h * $stepZ,
					$portal
				);
			}
		}

		return true;
	}

	/**
	 * @phpstan-param \Closure(int, int) : Block $getBlock
	 */
	private static function findFrame(\Closure $getBlock, int $horizontal, int $y, int $direction, int $limit) : ?int{
		for($i = 1; $i <= $limit; ++$i){
			$h = $horizontal + $i * $direction;
			$block = $getBlock($h, $y);
			if(self::isObsidian($block)){
				return $h;
			}
			if(!self::canBePortalSpace($block)){
				return null;
			}
		}
		return null;
	}

	/**
	 * @phpstan-param \Closure(int, int) : Block $getBlock
	 */
	private static function findVerticalFrame(\Closure $getBlock, int $horizontal, int $y, int $direction, int $limit) : ?int{
		for($i = 1; $i <= $limit; ++$i){
			$yy = $y + $i * $direction;
			$block = $getBlock($horizontal, $yy);
			if(self::isObsidian($block)){
				return $yy;
			}
			if(!self::canBePortalSpace($block)){
				return null;
			}
		}
		return null;
	}

	private static function canBePortalSpace(Block $block) : bool{
		return $block->getTypeId() === BlockTypeIds::AIR || $block instanceof NetherPortal;
	}

	private static function isObsidian(Block $block) : bool{
		return $block->getTypeId() === BlockTypeIds::OBSIDIAN;
	}
}
