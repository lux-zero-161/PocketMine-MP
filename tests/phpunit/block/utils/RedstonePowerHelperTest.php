<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_|  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\block\utils;

use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;

class RedstonePowerHelperTest extends TestCase{

	public function testRepeaterOnlyPowersTheFacingDirection() : void{
		$repeater = VanillaBlocks::REDSTONE_REPEATER()->setFacing(Facing::NORTH);
		self::assertSame(0, RedstonePowerHelper::getEmittedSignalStrength($repeater));

		$repeater->setPowered(true);
		self::assertTrue(RedstonePowerHelper::providesPowerTo($repeater, Facing::NORTH));
		self::assertFalse(RedstonePowerHelper::providesPowerTo($repeater, Facing::SOUTH));
		self::assertFalse(RedstonePowerHelper::providesPowerTo($repeater, Facing::EAST));
	}

	public function testComparatorOnlyPowersTheFacingDirection() : void{
		$comparator = VanillaBlocks::REDSTONE_COMPARATOR()->setFacing(Facing::WEST);
		self::assertFalse(RedstonePowerHelper::providesPowerTo($comparator, Facing::WEST));

		$comparator->setPowered(true)->setOutputSignalStrength(15);
		self::assertTrue(RedstonePowerHelper::providesPowerTo($comparator, Facing::WEST));
		self::assertFalse(RedstonePowerHelper::providesPowerTo($comparator, Facing::EAST));
	}

	public function testRedstoneTorchPowersEveryDirection() : void{
		$torch = VanillaBlocks::REDSTONE_TORCH();
		self::assertTrue($torch->isLit());
		self::assertSame(15, RedstonePowerHelper::getEmittedSignalStrength($torch));

		foreach(Facing::ALL as $face){
			self::assertTrue(RedstonePowerHelper::providesPowerTo($torch, $face));
		}
	}
}
