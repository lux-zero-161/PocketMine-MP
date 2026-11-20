<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\SuspiciousBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function array_rand;
use function count;

class Brush extends Durable{

	public function getMaxDurability() : int{
		return 64;
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		if(!$blockClicked instanceof SuspiciousBlock){
			return ItemUseResult::NONE;
		}

		$progress = $blockClicked->getBrushedProgress() + 1;
		if($progress < SuspiciousBlock::MAX_BRUSHED_PROGRESS){
			$blockClicked->setBrushedProgress($progress);
			$player->getWorld()->setBlock($blockClicked->getPosition(), $blockClicked);
			$this->applyDamage(1);
			return ItemUseResult::SUCCESS;
		}

		$loot = $blockClicked->getTypeId() === \pocketmine\block\BlockTypeIds::SUSPICIOUS_SAND ? [
			VanillaItems::ANGLER_POTTERY_SHERD(),
			VanillaItems::ARCHER_POTTERY_SHERD(),
			VanillaItems::ARMS_UP_POTTERY_SHERD(),
			VanillaItems::EMERALD(),
			VanillaItems::DIAMOND()
		] : [
			VanillaItems::IRON_INGOT(),
			VanillaItems::EMERALD(),
			VanillaItems::SKULL_POTTERY_SHERD(),
			VanillaItems::PRIZE_POTTERY_SHERD()
		];
		$drop = $loot[array_rand($loot)];
		$player->getWorld()->dropItem($blockClicked->getPosition()->add(0.5, 0.5, 0.5), $drop);
		$player->getWorld()->setBlock($blockClicked->getPosition(), VanillaBlocks::AIR());
		$this->applyDamage(1);
		return ItemUseResult::SUCCESS;
	}
}
