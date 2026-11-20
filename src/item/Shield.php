<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\player\Player;

class Shield extends Durable implements Releasable{

	public function getMaxDurability() : int{
		return 336;
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function canStartUsingItem(Player $player) : bool{
		return true;
	}
}
