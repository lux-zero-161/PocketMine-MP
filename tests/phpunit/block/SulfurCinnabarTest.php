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
use pocketmine\block\utils\PotentSulfurState;
use pocketmine\block\utils\SlabType;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function array_fill_keys;
use function implode;

/**
 * Covers the sulfur and cinnabar block families added for Bedrock 1.26.
 */
final class SulfurCinnabarTest extends TestCase{
	private const FAMILY_NAMES = [
		BlockTypeNames::SULFUR,
		BlockTypeNames::SULFUR_SLAB,
		BlockTypeNames::SULFUR_DOUBLE_SLAB,
		BlockTypeNames::SULFUR_STAIRS,
		BlockTypeNames::SULFUR_WALL,
		BlockTypeNames::SULFUR_BRICKS,
		BlockTypeNames::SULFUR_BRICK_SLAB,
		BlockTypeNames::SULFUR_BRICK_DOUBLE_SLAB,
		BlockTypeNames::SULFUR_BRICK_STAIRS,
		BlockTypeNames::SULFUR_BRICK_WALL,
		BlockTypeNames::CHISELED_SULFUR,
		BlockTypeNames::POTENT_SULFUR,
		BlockTypeNames::POLISHED_SULFUR,
		BlockTypeNames::POLISHED_SULFUR_SLAB,
		BlockTypeNames::POLISHED_SULFUR_DOUBLE_SLAB,
		BlockTypeNames::POLISHED_SULFUR_STAIRS,
		BlockTypeNames::POLISHED_SULFUR_WALL,
		BlockTypeNames::CINNABAR,
		BlockTypeNames::CINNABAR_SLAB,
		BlockTypeNames::CINNABAR_DOUBLE_SLAB,
		BlockTypeNames::CINNABAR_STAIRS,
		BlockTypeNames::CINNABAR_WALL,
		BlockTypeNames::CINNABAR_BRICKS,
		BlockTypeNames::CINNABAR_BRICK_SLAB,
		BlockTypeNames::CINNABAR_BRICK_DOUBLE_SLAB,
		BlockTypeNames::CINNABAR_BRICK_STAIRS,
		BlockTypeNames::CINNABAR_BRICK_WALL,
		BlockTypeNames::CHISELED_CINNABAR,
		BlockTypeNames::POLISHED_CINNABAR,
		BlockTypeNames::POLISHED_CINNABAR_SLAB,
		BlockTypeNames::POLISHED_CINNABAR_DOUBLE_SLAB,
		BlockTypeNames::POLISHED_CINNABAR_STAIRS,
		BlockTypeNames::POLISHED_CINNABAR_WALL,
	];

	/**
	 * @return BlockStateData[]
	 * @phpstan-return list<BlockStateData>
	 */
	private static function getCanonicalStates() : array{
		$dictionary = BlockStateDictionary::loadFromString(
			Filesystem::fileGetContents(BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT),
			Filesystem::fileGetContents(BedrockDataFiles::BLOCK_STATE_META_MAP_JSON)
		);
		$states = [];
		for($id = 0; ($data = $dictionary->generateDataFromStateId($id)) !== null; $id++){
			$states[] = $data;
		}
		return $states;
	}

	public function testEveryVanillaBlockStateLoads() : void{
		$familyNames = array_fill_keys(self::FAMILY_NAMES, true);
		$deserializer = GlobalBlockStateHandlers::getDeserializer();
		$serializer = GlobalBlockStateHandlers::getSerializer();

		$tested = 0;
		foreach(self::getCanonicalStates() as $stateData){
			if(!isset($familyNames[$stateData->getName()])){
				continue;
			}
			$block = $deserializer->deserializeBlock($stateData);
			self::assertSame($stateData->getName(), $serializer->serializeBlock($block)->getName(), "Blockstate does not round-trip: " . $stateData->toNbt());
			$tested++;
		}
		self::assertGreaterThan(0, $tested, "No sulfur/cinnabar blockstates were tested");
	}

	public function testNoFamilyBlockTypeMissing() : void{
		$familyNames = array_fill_keys(self::FAMILY_NAMES, true);
		$serializer = GlobalBlockStateHandlers::getSerializer();

		$found = [];
		foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state){
			$name = $serializer->serializeBlock($state)->getName();
			if(isset($familyNames[$name])){
				$found[$name] = true;
			}
		}

		$missing = [];
		foreach(self::FAMILY_NAMES as $name){
			if(!isset($found[$name])){
				$missing[] = $name;
			}
		}
		self::assertSame([], $missing, "The following sulfur/cinnabar block types are not registered: " . implode(", ", $missing));
	}

	public function testPotentSulfurStates() : void{
		$block = VanillaBlocks::POTENT_SULFUR();
		self::assertSame(PotentSulfurState::DRY, $block->getState());

		$serializer = GlobalBlockStateHandlers::getSerializer();
		$deserializer = GlobalBlockStateHandlers::getDeserializer();

		foreach(PotentSulfurState::cases() as $state){
			$block->setState($state);
			$data = $serializer->serializeBlock($block);
			self::assertSame(BlockTypeNames::POTENT_SULFUR, $data->getName());
			$newBlock = $deserializer->deserializeBlock($data);
			self::assertInstanceOf(PotentSulfur::class, $newBlock);
			self::assertSame($state, $newBlock->getState());
		}
	}

	public function testDoubleSlab() : void{
		$slab = VanillaBlocks::CINNABAR_SLAB()->setSlabType(SlabType::DOUBLE);
		$data = GlobalBlockStateHandlers::getSerializer()->serializeBlock($slab);
		self::assertSame(BlockTypeNames::CINNABAR_DOUBLE_SLAB, $data->getName());

		$newBlock = GlobalBlockStateHandlers::getDeserializer()->deserializeBlock($data);
		self::assertInstanceOf(Slab::class, $newBlock);
		self::assertSame(SlabType::DOUBLE, $newBlock->getSlabType());

		$drops = $slab->getDropsForCompatibleTool(VanillaItems::DIAMOND_PICKAXE());
		self::assertCount(1, $drops);
		self::assertSame(2, $drops[0]->getCount());
	}
}
