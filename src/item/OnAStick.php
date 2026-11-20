<?php

declare(strict_types=1);

namespace pocketmine\item;

class OnAStick extends Durable{

	public function __construct(ItemIdentifier $identifier, string $name, private int $maxDurability){
		parent::__construct($identifier, $name);
	}

	public function getMaxDurability() : int{
		return $this->maxDurability;
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}
