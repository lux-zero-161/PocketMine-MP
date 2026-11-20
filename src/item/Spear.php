<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\item\enchantment\ItemEnchantmentTags as EnchantmentTags;

class Spear extends Sword{

	public function __construct(ItemIdentifier $identifier, string $name, ToolTier $tier){
		parent::__construct($identifier, $name, $tier, [EnchantmentTags::SWORD]);
	}
}
