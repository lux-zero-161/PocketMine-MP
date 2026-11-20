<?php

declare(strict_types=1);

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * Small performance smoke test for registry initialisation and block/item (de)serialization.
 *
 * Usage:
 *   php build/audit/performance.php
 */

/**
 * @param Closure(int): void $func
 */
function bench(string $name, int $iterations, Closure $func) : float{
	$start = hrtime(true);
	for($i = 0; $i < $iterations; ++$i){
		$func($i);
	}
	$elapsed = (hrtime(true) - $start) / 1e9;
	printf("%-38s %8.3f s  (%10.0f ops/s)\n", $name, $elapsed, $iterations / max($elapsed, 1e-9));
	return $elapsed;
}

$start = hrtime(true);
$block = VanillaBlocks::STONE();
$item = VanillaItems::AIR();
$init = (hrtime(true) - $start) / 1e9;

printf("Registry cold start:                   %8.3f s\n", $init);
printf("Memory after registry init:            %8.1f MiB\n", memory_get_usage(true) / 1024 / 1024);
print("\n");

$registry = RuntimeBlockStateRegistry::getInstance();
$blockSerializer = GlobalBlockStateHandlers::getSerializer();
$blockDeserializer = GlobalBlockStateHandlers::getDeserializer();
$states = array_values($registry->getAllKnownStates());

bench("Block state serialize/deserialize", count($states), function(int $i) use ($states, $blockSerializer, $blockDeserializer) : void{
	$state = $states[$i];
	$data = $blockSerializer->serializeBlock($state);
	$blockDeserializer->deserializeBlock($data);
});

$itemSerializer = GlobalItemDataHandlers::getSerializer();
$itemDeserializer = GlobalItemDataHandlers::getDeserializer();
$items = array_values(iterator_to_array(Utils::stringifyKeys(VanillaItems::getAll())));

bench("Item NBT serialize/deserialize", count($items), function(int $i) use ($items, $itemSerializer, $itemDeserializer) : void{
	$item = $items[$i];
	try{
		$nbt = $itemSerializer->serializeStack($item);
		$itemDeserializer->deserializeStack($nbt);
	}catch(\Throwable){
		//non-serializable development items are not part of this benchmark
	}
});

bench("VanillaBlocks lookup", 20000, function(int $i) : void{
	VanillaBlocks::STONE();
	VanillaBlocks::DIRT();
	VanillaBlocks::GRASS();
	VanillaBlocks::OAK_PLANKS();
});

bench("VanillaItems lookup", 20000, function(int $i) : void{
	VanillaItems::STICK();
	VanillaItems::DIAMOND();
	VanillaItems::APPLE();
	VanillaItems::IRON_INGOT();
});

printf("\nPeak memory:                           %8.1f MiB\n", memory_get_peak_usage(true) / 1024 / 1024);
