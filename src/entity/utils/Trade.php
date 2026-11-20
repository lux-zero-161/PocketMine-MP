<?php

declare(strict_types=1);

namespace pocketmine\entity\utils;

use pocketmine\item\Item;

/**
 * A simple villager trade: input items are consumed, output items are given.
 */
final class Trade{
	public function __construct(
		private Item $input,
		private Item $output
	){}

	public function getInput() : Item{ return clone $this->input; }

	public function getOutput() : Item{ return clone $this->output; }
}
