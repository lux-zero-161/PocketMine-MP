<?php

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\entity\Entity;

/**
 * Implemented by inventories whose contents belong to an entity, such as chest minecarts and chest boats.
 */
interface EntityInventory extends Inventory{
	/**
	 * Returns the entity which holds this inventory.
	 */
	public function getHolder() : Entity;
}
