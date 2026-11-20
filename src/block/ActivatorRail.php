<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\PoweredByRedstone;
use pocketmine\block\utils\RailPoweredByRedstoneTrait;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\entity\Living;
use pocketmine\entity\object\Minecart;
use pocketmine\entity\object\TNTMinecart;
use pocketmine\math\AxisAlignedBB;

class ActivatorRail extends StraightOnlyRail implements PoweredByRedstone{
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
		if($powered){
			$this->activateRiders();
		}
	}

	/**
	 * Dismounts riders from minecarts standing on this rail.
	 */
	private function activateRiders() : void{
		$box = AxisAlignedBB::one()
			->offset($this->position->x, $this->position->y, $this->position->z)
			->expand(0.1, 1.0, 0.1);
		foreach($this->position->getWorld()->getNearbyEntities($box) as $entity){
			if($entity instanceof TNTMinecart){
				$entity->activate();
			}elseif($entity instanceof Minecart){
				$entity->dismountPassengers();
			}elseif($entity instanceof Living && $entity->getRidingEntity() !== null){
				$entity->dismount();
			}
		}
	}
}
