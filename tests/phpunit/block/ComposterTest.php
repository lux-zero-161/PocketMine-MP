<?php

declare(strict_types=1);

namespace pocketmine\block;

use PHPUnit\Framework\TestCase;
use pocketmine\item\VanillaItems;

class ComposterTest extends TestCase{

	public function testCompostChances() : void{
		self::assertSame(Composter::CHANCE_LOW, Composter::getCompostChance(VanillaItems::WHEAT_SEEDS()));
		self::assertSame(Composter::CHANCE_LOW, Composter::getCompostChance(VanillaBlocks::OAK_LEAVES()->asItem()));
		self::assertSame(Composter::CHANCE_LOW_MEDIUM, Composter::getCompostChance(VanillaBlocks::CACTUS()->asItem()));
		self::assertSame(Composter::CHANCE_MEDIUM, Composter::getCompostChance(VanillaItems::APPLE()));
		self::assertSame(Composter::CHANCE_MEDIUM, Composter::getCompostChance(VanillaBlocks::POPPY()->asItem()));
		self::assertSame(Composter::CHANCE_MEDIUM_HIGH, Composter::getCompostChance(VanillaItems::BAKED_POTATO()));
		self::assertSame(Composter::CHANCE_MEDIUM_HIGH, Composter::getCompostChance(VanillaBlocks::HAY_BALE()->asItem()));
		self::assertSame(Composter::CHANCE_ALWAYS, Composter::getCompostChance(VanillaItems::PUMPKIN_PIE()));
		self::assertNull(Composter::getCompostChance(VanillaBlocks::STONE()->asItem()));
	}
}
