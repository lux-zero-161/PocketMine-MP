<?php

declare(strict_types=1);

namespace pocketmine\crafting;

final class CrafterCraftingGrid extends CraftingGrid{

	public function __construct(){
		parent::__construct(CraftingGrid::SIZE_BIG);
	}
}
