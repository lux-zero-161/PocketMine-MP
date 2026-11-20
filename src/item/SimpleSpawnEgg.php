<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use function assert;

class SimpleSpawnEgg extends SpawnEgg{

	/**
	 * @param \Closure(Location) : Entity $factory
	 */
	public function __construct(ItemIdentifier $identifier, string $name, private \Closure $factory){
		parent::__construct($identifier, $name);
	}

	protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
		return ($this->factory)(Location::fromObject($pos, $world, $yaw, $pitch));
	}
}
