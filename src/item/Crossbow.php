<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;

class Crossbow extends Durable implements Releasable{

	public function getMaxDurability() : int{
		return 465;
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function canStartUsingItem(Player $player) : bool{
		$arrow = VanillaItems::ARROW();
		return !$player->hasFiniteResources() || $player->getOffHandInventory()->contains($arrow) || $player->getInventory()->contains($arrow);
	}

	public function onReleaseUsing(Player $player, array &$returnedItems) : ItemUseResult{
		$arrow = VanillaItems::ARROW();
		$inventory = match(true){
			$player->getOffHandInventory()->contains($arrow) => $player->getOffHandInventory(),
			$player->getInventory()->contains($arrow) => $player->getInventory(),
			default => null
		};

		if($player->hasFiniteResources() && $inventory === null){
			return ItemUseResult::FAIL;
		}
		if($player->getItemUseDuration() < 25 || $player->isSpectator()){
			return ItemUseResult::FAIL;
		}

		$location = $player->getLocation();
		$entity = new ArrowEntity(Location::fromObject(
			$player->getEyePos(),
			$player->getWorld(),
			($location->yaw > 180 ? 360 : 0) - $location->yaw,
			-$location->pitch
		), $player, true);
		$entity->setMotion($player->getDirectionVector()->multiply(3.15));

		$ev = new ProjectileLaunchEvent($entity);
		$ev->call();
		if($ev->isCancelled()){
			return ItemUseResult::FAIL;
		}

		$entity->spawnToAll();
		$location->getWorld()->addSound($location, new BowShootSound());

		if($player->hasFiniteResources()){
			$inventory?->removeItem($arrow);
			$this->applyDamage(1);
		}

		return ItemUseResult::SUCCESS;
	}
}
