<?php

declare(strict_types=1);

namespace pocketmine\item;

class Crossbow extends Durable{

	public function getMaxDurability() : int{
		return 465;
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}
