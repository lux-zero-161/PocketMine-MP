<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\PoweredByRedstone;
use pocketmine\block\utils\RailPoweredByRedstoneTrait;
use pocketmine\block\utils\RedstonePowerHelper;

class PoweredRail extends StraightOnlyRail implements PoweredByRedstone{
	use RailPoweredByRedstoneTrait;

	public function onNearbyBlockChange() : void{
		parent::onNearbyBlockChange();
		$this->updatePoweredState();
	}

	public function onPostPlace() : void{
		parent::onPostPlace();
		$this->updatePoweredState();
	}

	protected function updatePoweredState() : void{
		$world = $this->position->getWorld();
		$current = $world->getBlock($this->position);
		if(!$current instanceof self || $current->getTypeId() !== $this->getTypeId()){
			return;
		}
		$powered = RedstonePowerHelper::isPowered($current);
		if($powered !== $current->isPowered()){
			$current->setPowered($powered);
			$world->setBlock($this->position, $current);
		}
	}
}
