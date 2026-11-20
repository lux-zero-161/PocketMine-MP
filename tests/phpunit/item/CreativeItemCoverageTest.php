<?php

declare(strict_types=1);

namespace pocketmine\item;

use PHPUnit\Framework\TestCase;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function array_key_exists;
use function array_keys;
use function glob;
use function is_array;
use function is_string;
use function json_decode;
use function sort;

class CreativeItemCoverageTest extends TestCase{

	/**
	 * Every item in the current Bedrock creative inventory must map to a registered block or item.
	 */
	public function testAllCreativeItemsAreRepresentable() : void{
		$blockNames = [];
		$serializer = GlobalBlockStateHandlers::getSerializer();
		foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $block){
			$blockNames[$serializer->serializeBlock($block)->getName()] = true;
		}

		$deserializer = GlobalItemDataHandlers::getDeserializer();
		$missing = [];

		$files = glob(BedrockDataFiles::CREATIVE . '/*.json');
		self::assertIsArray($files);
		foreach($files as $file){
			$groups = json_decode(Filesystem::fileGetContents($file), true, 512, JSON_THROW_ON_ERROR);
			if(!is_array($groups)){
				continue;
			}
			foreach($groups as $group){
				if(!is_array($group) || !isset($group["items"]) || !is_array($group["items"])){
					continue;
				}
				foreach($group["items"] as $entry){
					if(is_string($entry)){
						$id = $entry;
					}elseif(is_array($entry) && isset($entry["name"]) && is_string($entry["name"])){
						$id = $entry["name"];
					}else{
						continue;
					}

					if(!array_key_exists($id, $blockNames) && $deserializer->getDeserializerForId($id) === null){
						$missing[$id] = true;
					}
				}
			}
		}

		$missing = array_keys($missing);
		sort($missing);
		self::assertSame([], $missing, "Creative inventory items without a block/item registration:\n" . implode("\n", $missing));
	}
}
