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

use pocketmine\block\tile\Container;
use pocketmine\block\tile\Hopper as TileHopper;
use pocketmine\block\tile\Jukebox as TileJukebox;
use pocketmine\block\utils\PoweredByRedstone;
use pocketmine\block\utils\PoweredByRedstoneTrait;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\Record;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use function count;
use function min;

class Hopper extends Transparent implements PoweredByRedstone{
	use PoweredByRedstoneTrait;

	private int $facing = Facing::DOWN;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facingExcept($this->facing, Facing::UP);
		$w->bool($this->powered);
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		if($facing === Facing::UP){
			throw new \InvalidArgumentException("Hopper may not face upward");
		}
		$this->facing = $facing;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		$result = [
			AxisAlignedBB::one()->trim(Facing::UP, 6 / 16) //the empty area around the bottom is currently considered solid
		];

		foreach(Facing::HORIZONTAL as $f){ //add the frame parts around the bowl
			$result[] = AxisAlignedBB::one()->trim($f, 14 / 16);
		}
		return $result;
	}

	public function getSupportType(int $facing) : SupportType{
		return match($facing){
			Facing::UP => SupportType::FULL,
			Facing::DOWN => $this->facing === Facing::DOWN ? SupportType::CENTER : SupportType::NONE,
			default => SupportType::NONE
		};
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$this->facing = $face === Facing::DOWN ? Facing::DOWN : Facing::opposite($face);

		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player !== null){
			$tile = $this->position->getWorld()->getTile($this->position);
			if($tile instanceof TileHopper){ //TODO: find a way to have inventories open on click without this boilerplate in every block
				$player->setCurrentWindow($tile->getInventory());
			}
			return true;
		}
		return false;
	}

	public function onNearbyBlockChange() : void{
		$powered = RedstonePowerHelper::isPowered($this);
		$world = $this->position->getWorld();
		if($powered !== $this->powered){
			$this->powered = $powered;
			$world->setBlock($this->position, $this);
		}
		//check again soon so the hopper resumes immediately after being unlocked
		$world->scheduleDelayedBlockUpdate($this->position, 1);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if(!$tile instanceof TileHopper){
			return;
		}

		if(!$this->powered){
			$this->tryTransferItems($tile);
		}
		$world->scheduleDelayedBlockUpdate($this->position, 8);
	}

	/**
	 * Moves one itemstack from the container above into this hopper, then from this hopper into the container it faces.
	 */
	private function tryTransferItems(TileHopper $tile) : bool{
		$world = $this->position->getWorld();
		$transferred = false;

		$target = $world->getTile($this->getSide($this->facing)->getPosition());
		if($target instanceof Container){
			$source = $tile->getInventory();
			$targetInventory = $target->getRealInventory();
			foreach($source->getContents() as $slot => $item){
				$amount = min($item->getCount(), $targetInventory->getAddableItemQuantity($item));
				if($amount <= 0){
					continue;
				}
				$leftover = $targetInventory->addItem((clone $item)->setCount($amount));
				if(count($leftover) > 0){
					continue;
				}
				if($amount >= $item->getCount()){
					$source->clear($slot);
				}else{
					$source->setItem($slot, (clone $item)->setCount($item->getCount() - $amount));
				}
				$transferred = true;
				break;
			}
		}

		if($this->facing === Facing::DOWN){
			$composter = $world->getBlock($this->getSide(Facing::DOWN)->getPosition());
			if($composter instanceof Composter && $composter->getFillLevel() < Composter::MAX_FILL_LEVEL){
				$source = $tile->getInventory();
				foreach($source->getContents() as $slot => $item){
					$returned = [];
					$test = clone $item;
					if($composter->onInteract($test, Facing::UP, new Vector3(0.5, 0.5, 0.5), null, $returned)){
						$amount = $item->getCount() - $test->getCount();
						if($amount > 0){
							if($item->getCount() <= $amount){
								$source->clear($slot);
							}else{
								$source->setItem($slot, (clone $item)->setCount($item->getCount() - $amount));
							}
							$transferred = true;
						}
						break;
					}
				}
			}
		}

		if($this->facing === Facing::DOWN){
			$jukebox = $world->getBlock($this->getSide(Facing::DOWN)->getPosition());
			if($jukebox instanceof Jukebox){
				$jukeboxTile = $world->getTile($jukebox->getPosition());
				if($jukeboxTile instanceof TileJukebox && $jukeboxTile->getRecord() === null){
					$source = $tile->getInventory();
					foreach($source->getContents() as $slot => $item){
						if($item instanceof Record){
							$jukebox->insertRecord(clone $item);
							$world->setBlock($jukebox->getPosition(), $jukebox);
							$item->pop();
							if($item->isNull()){
								$source->clear($slot);
							}else{
								$source->setItem($slot, $item);
							}
							$transferred = true;
							break;
						}
					}
				}
			}
		}

		$above = $world->getTile($this->getSide(Facing::UP)->getPosition());
		if($above instanceof Container){
			$source = $above->getRealInventory();
			$targetInventory = $tile->getInventory();
			foreach($source->getContents() as $slot => $item){
				$amount = min($item->getCount(), $targetInventory->getAddableItemQuantity($item));
				if($amount <= 0){
					continue;
				}
				$targetInventory->addItem((clone $item)->setCount($amount));
				if($amount >= $item->getCount()){
					$source->clear($slot);
				}else{
					$source->setItem($slot, (clone $item)->setCount($item->getCount() - $amount));
				}
				$transferred = true;
				break;
			}
		}

		$jukebox = $world->getBlock($this->getSide(Facing::UP)->getPosition());
		if($jukebox instanceof Jukebox){
			$jukeboxTile = $world->getTile($jukebox->getPosition());
			if($jukeboxTile instanceof TileJukebox && ($record = $jukeboxTile->getRecord()) !== null){
				$targetInventory = $tile->getInventory();
				if($targetInventory->canAddItem($record)){
					$jukebox->removeRecord();
					$world->setBlock($jukebox->getPosition(), $jukebox);
					$targetInventory->addItem($record);
					$transferred = true;
				}
			}
		}

		$composter = $world->getBlock($this->getSide(Facing::UP)->getPosition());
		if($composter instanceof Composter && $composter->getFillLevel() >= Composter::MAX_FILL_LEVEL){
			$targetInventory = $tile->getInventory();
			$boneMeal = VanillaItems::BONE_MEAL();
			if($targetInventory->canAddItem($boneMeal)){
				$returned = [];
				$composter->onInteract(VanillaItems::AIR(), Facing::UP, new Vector3(0.5, 0.5, 0.5), null, $returned);
				foreach($returned as $result){
					$targetInventory->addItem($result);
				}
				$transferred = true;
			}
		}

		return $transferred;
	}
}
