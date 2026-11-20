<?php

declare(strict_types=1);

namespace pocketmine\item;

class Bundle extends Item{

	public function getMaxStackSize() : int{
		return 1;
	}
}
