<?php

declare(strict_types=1);

namespace pocketmine\item;

class Shield extends Durable{

	public function getMaxDurability() : int{
		return 336;
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}
