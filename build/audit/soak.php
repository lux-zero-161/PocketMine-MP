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
 * Repeated serialization soak test for registry and NBT stability.
 *
 * Usage:
 *   php build/audit/soak.php [--iterations=5]
 */

$iterations = 5;
foreach($argv as $arg){
	if(str_starts_with($arg, "--iterations=")){
		$iterations = max(1, (int) substr($arg, 13));
	}
}

VanillaBlocks::STONE();
VanillaItems::AIR();

$registry = RuntimeBlockStateRegistry::getInstance();
$blockSerializer = GlobalBlockStateHandlers::getSerializer();
$blockDeserializer = GlobalBlockStateHandlers::getDeserializer();
$states = array_values($registry->getAllKnownStates());

$itemSerializer = GlobalItemDataHandlers::getSerializer();
$itemDeserializer = GlobalItemDataHandlers::getDeserializer();
$items = array_values(iterator_to_array(Utils::stringifyKeys(VanillaItems::getAll())));

$initialMemory = memory_get_usage(true);
printf("Initial memory: %.1f MiB\n", $initialMemory / 1024 / 1024);

for($iteration = 1; $iteration <= $iterations; ++$iteration){
	$start = hrtime(true);
	$errors = 0;

	foreach($states as $state){
		try{
			$data = $blockSerializer->serializeBlock($state);
			$blockDeserializer->deserializeBlock($data);
		}catch(\Throwable){
			$errors++;
		}
	}
	foreach($items as $item){
		if($item->isNull()){
			continue;
		}
		try{
			$itemDeserializer->deserializeStack($itemSerializer->serializeStack($item));
		}catch(\Throwable){
			$errors++;
		}
	}

	gc_collect_cycles();
	$elapsed = (hrtime(true) - $start) / 1e9;
	$memory = memory_get_usage(true);
	printf(
		"Iteration %d/%d: %6.3f s, %.1f MiB (delta %+.1f MiB), errors: %d\n",
		$iteration,
		$iterations,
		$elapsed,
		$memory / 1024 / 1024,
		($memory - $initialMemory) / 1024 / 1024,
		$errors
	);

	if($errors > 0){
		fwrite(STDERR, "Serialization errors detected\n");
		exit(1);
	}
}

$finalMemory = memory_get_usage(true);
$growth = $finalMemory - $initialMemory;
if($growth > 64 * 1024 * 1024){
	fwrite(STDERR, "Memory growth exceeded 64 MiB\n");
	exit(1);
}

printf("Peak memory: %.1f MiB\n", memory_get_peak_usage(true) / 1024 / 1024);
print("Soak test passed.\n");
