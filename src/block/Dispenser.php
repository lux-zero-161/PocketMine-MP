<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\AnyFacing;
use pocketmine\block\tile\Dispenser as TileDispenser;
use pocketmine\entity\Location;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\entity\projectile\Egg as EggEntity;
use pocketmine\entity\projectile\Snowball as SnowballEntity;
use pocketmine\entity\projectile\SplashPotion as SplashPotionEntity;
use pocketmine\item\Arrow as ArrowItem;
use pocketmine\item\Egg as EggItem;
use pocketmine\item\Fertilizer;
use pocketmine\item\FireCharge as FireChargeItem;
use pocketmine\item\FlintSteel;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\LiquidBucket;
use pocketmine\item\VanillaItems;
use pocketmine\block\Liquid;
use pocketmine\item\Snowball as SnowballItem;
use pocketmine\item\SplashPotion as SplashPotionItem;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\math\Vector3;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\IgniteSound;

class Dispenser extends Opaque implements AnyFacing{
	protected int $facing = Facing::DOWN;
	protected bool $triggered = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
		$w->bool($this->triggered);
	}

	public function onNearbyBlockChange() : void{
		$powered = RedstonePowerHelper::isPowered($this);
		if($powered && !$this->triggered){
			$this->triggered = true;
			$world = $this->position->getWorld();
			$world->setBlock($this->position, $this);
			$world->scheduleDelayedBlockUpdate($this->position, 2);
		}elseif(!$powered && $this->triggered){
			$this->triggered = false;
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function onScheduledUpdate() : void{
		if($this->triggered){
			$world = $this->position->getWorld();
			$tile = $world->getTile($this->position);
			if($tile instanceof TileDispenser){
				$inventory = $tile->getInventory();
				for($slot = 0; $slot < 9; $slot++){
					$item = $inventory->getItem($slot);
					if(!$item->isNull()){
						if($item instanceof LiquidBucket){
							$target = $this->getSide($this->facing);
							if($target->canBeReplaced()){
								$world->setBlock($target->getPosition(), $item->getLiquid()->getFlowingForm());
								$inventory->setItem($slot, VanillaItems::BUCKET());
								break;
							}
						}
						if($item->getTypeId() === ItemTypeIds::BUCKET){
							$target = $this->getSide($this->facing);
							if($target instanceof Liquid && $target->isSource()){
								$filled = match($target->getTypeId()){
									BlockTypeIds::WATER => VanillaItems::WATER_BUCKET(),
									BlockTypeIds::LAVA => VanillaItems::LAVA_BUCKET(),
									default => null
								};
								if($filled !== null){
									$world->setBlock($target->getPosition(), VanillaBlocks::AIR());
									$inventory->setItem($slot, $filled);
									break;
								}
							}
						}
						if($item instanceof Fertilizer){
							$returned = [];
							$target = $this->getSide($this->facing);
							if($target->onInteract(clone $item, Facing::opposite($this->facing), new Vector3(0.5, 0.5, 0.5), null, $returned)){
								$inventory->clear($slot);
								foreach($returned as $result){
									$world->dropItem($this->position->add(0.5, 0.5, 0.5), $result);
								}
								break;
							}
						}
						if($item instanceof FireChargeItem || $item instanceof FlintSteel){
							$target = $this->getSide($this->facing);
							if($target instanceof TNT){
								$target->ignite();
							}elseif($target->getTypeId() === BlockTypeIds::AIR){
								$world->setBlock($target->getPosition(), VanillaBlocks::FIRE());
								$world->addSound($target->getPosition()->add(0.5, 0.5, 0.5), new BlazeShootSound());
							}else{
								continue;
							}
							if($item instanceof FireChargeItem){
								$inventory->clear($slot);
							}else{
								$item->applyDamage(1);
								$inventory->setItem($slot, $item);
							}
							break;
						}
						if(ItemTypeIds::toBlockTypeId($item->getTypeId()) === BlockTypeIds::TNT){
							$v = $this->facingVector();
							$spawn = $this->position->add(0.5 + $v->x, 0.5 + $v->y, 0.5 + $v->z);
							$tnt = new PrimedTNT(Location::fromObject($spawn, $world));
							$tnt->setFuse(80);
							$tnt->setMotion($v->multiply(0.5));
							$tnt->spawnToAll();
							$tnt->broadcastSound(new IgniteSound());
							$inventory->clear($slot);
							break;
						}
						$inventory->clear($slot);
						$v = $this->facingVector();
						$spawn = $this->position->add(0.5 + $v->x, 0.5 + $v->y, 0.5 + $v->z);
						$location = Location::fromObject($spawn, $world);
						$projectile = match(true){
							$item instanceof ArrowItem => new ArrowEntity($location, null, false),
							$item instanceof EggItem => new EggEntity($location, null),
							$item instanceof SnowballItem => new SnowballEntity($location, null),
							$item instanceof SplashPotionItem => new SplashPotionEntity($location, null, $item->getType()),
							default => null
						};
						if($projectile !== null){
							$projectile->setMotion($v->multiply($item instanceof SplashPotionItem ? 0.5 : 1.5));
							$projectile->spawnToAll();
						}else{
							$world->dropItem($spawn, $item);
						}
						break;
					}
				}
			}
			$this->triggered = false;
			$world->setBlock($this->position, $this);
		}
	}

	private function facingVector() : Vector3{
		return match($this->facing){
			Facing::DOWN => new Vector3(0, -1, 0),
			Facing::UP => new Vector3(0, 1, 0),
			Facing::NORTH => new Vector3(0, 0, -1),
			Facing::SOUTH => new Vector3(0, 0, 1),
			Facing::WEST => new Vector3(-1, 0, 0),
			Facing::EAST => new Vector3(1, 0, 0),
			default => throw new \InvalidArgumentException("Invalid facing")
		};
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		Facing::validate($facing);
		$this->facing = $facing;
		return $this;
	}

	public function isTriggered() : bool{ return $this->triggered; }

	/** @return $this */
	public function setTriggered(bool $triggered) : self{
		$this->triggered = $triggered;
		return $this;
	}
}
