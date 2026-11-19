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
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * Reports how much of the canonical Bedrock block palette the server can represent.
 *
 * Usage:
 *   php build/audit/block-coverage.php [--missing]
 */

$dictionary = BlockStateDictionary::loadFromString(
	Filesystem::fileGetContents(BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT),
	Filesystem::fileGetContents(BedrockDataFiles::BLOCK_STATE_META_MAP_JSON)
);

$canonicalStates = [];
for($id = 0; ($data = $dictionary->generateDataFromStateId($id)) !== null; $id++){
	$canonicalStates[] = $data;
}

$canonicalStateCounts = [];
foreach($canonicalStates as $data){
	$canonicalStateCounts[$data->getName()] = ($canonicalStateCounts[$data->getName()] ?? 0) + 1;
}

$registry = RuntimeBlockStateRegistry::getInstance();
$serializer = GlobalBlockStateHandlers::getSerializer();

$registeredStateCounts = [];
foreach($registry->getAllKnownStates() as $state){
	$name = $serializer->serializeBlock($state)->getName();
	$registeredStateCounts[$name] = ($registeredStateCounts[$name] ?? 0) + 1;
}

$totalNames = count($canonicalStateCounts);
$coveredNames = count(array_intersect_key($registeredStateCounts, $canonicalStateCounts));
$totalStates = count($canonicalStates);
$registeredStates = 0;
foreach(Utils::stringifyKeys($registeredStateCounts) as $name => $count){
	if(isset($canonicalStateCounts[$name])){
		$registeredStates += $count;
	}
}

printf("Canonical block type names: %d\n", $totalNames);
printf("Registered block type names: %d\n", $coveredNames);
printf("Canonical blockstates:      %d\n", $totalStates);
printf("Registered blockstates:     %d (of canonical names)\n", $registeredStates);
printf("Coverage:                   %.1f%% of names\n", $coveredNames / $totalNames * 100);

$missing = array_diff_key($canonicalStateCounts, $registeredStateCounts);
if(count($missing) > 0){
	printf("\nMissing block type names (%d):\n", count($missing));
	ksort($missing);
	foreach(Utils::stringifyKeys($missing) as $name => $count){
		printf("  %-45s %d state%s\n", $name, $count, $count === 1 ? "" : "s");
	}
}else{
	print("\nAll canonical block type names are registered.\n");
}

$extra = array_diff_key($registeredStateCounts, $canonicalStateCounts);
if(count($extra) > 0){
	printf("\nRegistered names not present in the canonical palette (%d):\n", count($extra));
	ksort($extra);
	foreach(Utils::stringifyKeys($extra) as $name => $count){
		printf("  %-45s %d state%s\n", $name, $count, $count === 1 ? "" : "s");
	}
}
