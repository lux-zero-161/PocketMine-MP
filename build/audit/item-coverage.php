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

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * Reports how many of the items in the current Bedrock creative inventory the server can represent.
 *
 * Items are considered supported when they map to a registered block (block items) or have an item deserializer.
 *
 * Usage:
 *   php build/audit/item-coverage.php [--missing]
 */

$index = [];
$creativeFiles = glob(BedrockDataFiles::CREATIVE . '/*.json');
if($creativeFiles === false){
	throw new \RuntimeException("Failed to scan the creative inventory directory");
}
foreach($creativeFiles as $file){
	$groups = json_decode(Filesystem::fileGetContents($file), true, 512, JSON_THROW_ON_ERROR);
	if(!is_array($groups)){
		throw new \RuntimeException("Creative inventory file $file must contain an array");
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
			$index[$id] = true;
		}
	}
}

$blockNames = [];
$serializer = GlobalBlockStateHandlers::getSerializer();
foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $block){
	$blockNames[$serializer->serializeBlock($block)->getName()] = true;
}

$deserializer = GlobalItemDataHandlers::getDeserializer();
$missing = [];
foreach(array_keys($index) as $id){
	if(array_key_exists($id, $blockNames) || $deserializer->getDeserializerForId($id) !== null){
		continue;
	}
	$missing[] = $id;
}
sort($missing);

$total = count($index);
$missingCount = count($missing);

printf("Creative item entries: %d\n", $total);
printf("Supported:             %d\n", $total - $missingCount);
printf("Missing:               %d (%.1f%% supported)\n", $missingCount, ($total - $missingCount) / $total * 100);

if(in_array("--missing", $argv, true)){
	print("\nMissing items:\n");
	foreach($missing as $id){
		print($id . "\n");
	}
}elseif($missingCount > 0){
	print("\nRun with --missing to list unsupported items.\n");
}
