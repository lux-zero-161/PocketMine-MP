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

namespace pocketmine\block;

use PHPUnit\Framework\TestCase;
use pocketmine\block\utils\CrackedState;
use pocketmine\block\utils\CrafterOrientation;
use pocketmine\block\utils\DripstoneThickness;
use pocketmine\block\utils\GrindstoneAttachment;
use pocketmine\block\utils\SaplingType;
use pocketmine\block\utils\StructureBlockMode;
use pocketmine\block\utils\TurtleEggCount;
use pocketmine\block\utils\VaultState;
use pocketmine\block\utils\WallConnectionType;
use pocketmine\math\Facing;
use pocketmine\world\generator\object\TreeType;

/**
 * Round-trip tests for block families added on top of the upstream block registry.
 */
final class BedrockBlockFamiliesTest extends TestCase{

	private static function roundTrip(Block $block) : Block{
		$serializer = \pocketmine\world\format\io\GlobalBlockStateHandlers::getSerializer();
		$deserializer = \pocketmine\world\format\io\GlobalBlockStateHandlers::getDeserializer();
		return $deserializer->deserializeBlock($serializer->serializeBlock($block));
	}

	public function testScaffolding() : void{
		$block = VanillaBlocks::SCAFFOLDING()->setStability(4)->setStabilityCheck(true);
		$new = self::roundTrip($block);
		self::assertInstanceOf(Scaffolding::class, $new);
		self::assertSame(4, $new->getStability());
		self::assertTrue($new->isStabilityCheck());
		self::assertTrue($new->canClimb());
	}

	public function testObserver() : void{
		$block = VanillaBlocks::OBSERVER()->setFacing(Facing::WEST)->setPowered(true);
		$new = self::roundTrip($block);
		self::assertInstanceOf(Observer::class, $new);
		self::assertSame(Facing::WEST, $new->getFacing());
		self::assertTrue($new->isPowered());
	}

	public function testKelpAge() : void{
		$block = VanillaBlocks::KELP()->setAge(Kelp::MAX_AGE);
		$new = self::roundTrip($block);
		self::assertInstanceOf(Kelp::class, $new);
		self::assertSame(Kelp::MAX_AGE, $new->getAge());
	}

	public function testSuspiciousBlock() : void{
		$block = VanillaBlocks::SUSPICIOUS_SAND()->setBrushedProgress(2)->setHanging(true);
		$new = self::roundTrip($block);
		self::assertInstanceOf(SuspiciousBlock::class, $new);
		self::assertSame(2, $new->getBrushedProgress());
		self::assertTrue($new->isHanging());
	}

	public function testPointedDripstone() : void{
		$block = VanillaBlocks::SULFUR_SPIKE()->setThickness(DripstoneThickness::BASE)->setHanging(true);
		$new = self::roundTrip($block);
		self::assertInstanceOf(SulfurSpike::class, $new);
		self::assertSame(DripstoneThickness::BASE, $new->getThickness());
		self::assertTrue($new->isHanging());
	}

	public function testTurtleEgg() : void{
		$block = VanillaBlocks::TURTLE_EGG()->setCount(TurtleEggCount::THREE)->setCrackedState(CrackedState::CRACKED);
		$new = self::roundTrip($block);
		self::assertInstanceOf(TurtleEgg::class, $new);
		self::assertSame(TurtleEggCount::THREE, $new->getCount());
		self::assertSame(CrackedState::CRACKED, $new->getCrackedState());
	}

	public function testPaleMossCarpet() : void{
		$block = VanillaBlocks::PALE_MOSS_CARPET()->setUpper(true)->setConnection(Facing::NORTH, WallConnectionType::TALL);
		$new = self::roundTrip($block);
		self::assertInstanceOf(PaleMossCarpet::class, $new);
		self::assertTrue($new->isUpper());
		self::assertSame(WallConnectionType::TALL, $new->getConnection(Facing::NORTH));
		self::assertNull($new->getConnection(Facing::SOUTH));
	}

	public function testStructureBlock() : void{
		$block = VanillaBlocks::STRUCTURE_BLOCK()->setMode(StructureBlockMode::SAVE);
		$new = self::roundTrip($block);
		self::assertInstanceOf(StructureBlock::class, $new);
		self::assertSame(StructureBlockMode::SAVE, $new->getMode());
	}

	public function testGrindstone() : void{
		$block = VanillaBlocks::GRINDSTONE()->setAttachment(GrindstoneAttachment::SIDE)->setDirection(2);
		$new = self::roundTrip($block);
		self::assertInstanceOf(Grindstone::class, $new);
		self::assertSame(GrindstoneAttachment::SIDE, $new->getAttachment());
		self::assertSame(2, $new->getDirection());
	}

	public function testVault() : void{
		$block = VanillaBlocks::VAULT()->setState(VaultState::UNLOCKING)->setOminous(true);
		$new = self::roundTrip($block);
		self::assertInstanceOf(Vault::class, $new);
		self::assertSame(VaultState::UNLOCKING, $new->getState());
		self::assertTrue($new->isOminous());
	}

	public function testCrafter() : void{
		$block = VanillaBlocks::CRAFTER()->setOrientation(CrafterOrientation::NORTH_UP)->setCrafting(true);
		$new = self::roundTrip($block);
		self::assertInstanceOf(Crafter::class, $new);
		self::assertSame(CrafterOrientation::NORTH_UP, $new->getOrientation());
		self::assertTrue($new->isCrafting());
	}

	public function testSaplingTrees() : void{
		self::assertSame(TreeType::CHERRY, SaplingType::CHERRY->getTreeType());
		self::assertSame(TreeType::PALE_OAK, SaplingType::PALE_OAK->getTreeType());
		self::assertSame(BlockTypeIds::CHERRY_SAPLING, VanillaBlocks::CHERRY_SAPLING()->asItem()->getBlock()->getTypeId());
	}
}
