<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\sound\IgniteSound;

class TNTMinecart extends Minecart{
	public static function getNetworkTypeId() : string{ return EntityIds::TNT_MINECART; }

	public function getName() : string{ return "TNT Minecart"; }

	public function getDrops() : array{
		$drop = $this->getDropItem();
		return [$drop ?? VanillaItems::TNT_MINECART()];
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		return $this->getPassenger() === null && $this->addPassenger($player);
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);
		if(!$source->isCancelled() && $this->isAlive()){
			$this->activate();
		}
	}

	/**
	 * Replaces this minecart with a primed TNT entity.
	 */
	public function activate(int $fuse = 80) : void{
		if($this->isClosed()){
			return;
		}
		$world = $this->getWorld();
		$tnt = new PrimedTNT(Location::fromObject(
			$this->location->add(0, $this->size->getHeight() / 2, 0),
			$world,
			$this->location->yaw,
			0
		));
		$tnt->setFuse($fuse);
		$tnt->setMotion($this->getMotion());
		$tnt->spawnToAll();
		$tnt->broadcastSound(new IgniteSound());
		$this->flagForDespawn();
	}
}
