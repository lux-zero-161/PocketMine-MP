<?php

declare(strict_types=1);

namespace pocketmine\block;

use PHPUnit\Framework\TestCase;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function count;
use function implode;
use function ksort;

/**
 * Guards the canonical Bedrock block palette coverage.
 *
 * Every block type name shipped in the current bedrock-data palette must have at least one registered state.
 * Some Bedrock states are intentionally collapsed internally (double slabs, wall sign directions, pressure plate
 * directions), so this asserts type name coverage, not a one-to-one state count.
 */
final class BlockCoverageTest extends TestCase{

	public function testAllCanonicalBlockTypeNamesRegistered() : void{
		$dictionary = BlockStateDictionary::loadFromString(
			Filesystem::fileGetContents(BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT),
			Filesystem::fileGetContents(BedrockDataFiles::BLOCK_STATE_META_MAP_JSON)
		);

		$canonicalNames = [];
		for($id = 0; ($data = $dictionary->generateDataFromStateId($id)) !== null; $id++){
			$canonicalNames[$data->getName()] = true;
		}

		$serializer = GlobalBlockStateHandlers::getSerializer();
		$registeredNames = [];
		foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state){
			$registeredNames[$serializer->serializeBlock($state)->getName()] = true;
		}

		$missing = [];
		foreach($canonicalNames as $name => $unused){
			if(!isset($registeredNames[$name])){
				$missing[] = $name;
			}
		}
		ksort($missing);

		self::assertGreaterThan(0, count($canonicalNames), "Canonical palette should not be empty");
		self::assertSame(
			[],
			$missing,
			count($missing) . " canonical block type names are not registered:\n" . implode("\n", $missing)
		);
	}
}
