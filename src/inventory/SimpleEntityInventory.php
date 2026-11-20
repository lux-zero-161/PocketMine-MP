<?php

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\entity\Entity;

class SimpleEntityInventory extends SimpleInventory implements EntityInventory{
	public function __construct(private Entity $holder, int $size){
		parent::__construct($size);
	}

	public function getHolder() : Entity{
		return $this->holder;
	}
}
