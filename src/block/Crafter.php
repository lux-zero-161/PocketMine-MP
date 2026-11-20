<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\inventory\CrafterInventory;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Crafter as TileCrafter;
use pocketmine\block\utils\CrafterOrientation;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\crafting\CrafterCraftingGrid;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function count;

class Crafter extends Opaque{
	protected CrafterOrientation $orientation = CrafterOrientation::DOWN_EAST;
	protected bool $triggered = false;
	protected bool $crafting = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->orientation);
		$w->bool($this->triggered);
		$w->bool($this->crafting);
	}

	public function getOrientation() : CrafterOrientation{ return $this->orientation; }

	/** @return $this */
	public function setOrientation(CrafterOrientation $orientation) : self{
		$this->orientation = $orientation;
		return $this;
	}

	public function isTriggered() : bool{ return $this->triggered; }

	/** @return $this */
	public function setTriggered(bool $triggered) : self{
		$this->triggered = $triggered;
		return $this;
	}

	public function isCrafting() : bool{ return $this->crafting; }

	/** @return $this */
	public function setCrafting(bool $crafting) : self{
		$this->crafting = $crafting;
		return $this;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player !== null){
			$tile = $this->position->getWorld()->getTile($this->position);
			if($tile instanceof TileCrafter){
				$player->setCurrentWindow($tile->getInventory());
			}
		}
		return true;
	}

	public function onNearbyBlockChange() : void{
		$powered = RedstonePowerHelper::isPowered($this);
		if($powered && !$this->triggered){
			$this->triggered = true;
			$world = $this->position->getWorld();
			$world->setBlock($this->position, $this);
			$world->scheduleDelayedBlockUpdate($this->position, 1);
		}elseif(!$powered && $this->triggered){
			$this->triggered = false;
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function onScheduledUpdate() : void{
		if(!$this->triggered){
			return;
		}

		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if($tile instanceof TileCrafter){
			$this->crafting = true;
			$world->setBlock($this->position, $this);
			$this->craft($tile->getInventory());
		}

		$this->triggered = false;
		$this->crafting = false;
		$world->setBlock($this->position, $this);
	}

	private function craft(CrafterInventory $inventory) : void{
		$grid = new CrafterCraftingGrid();
		for($i = 0; $i < 9; ++$i){
			$grid->setItem($i, $inventory->getItem($i));
		}

		$recipe = null;
		foreach($this->position->getWorld()->getServer()->getCraftingManager()->getCraftingRecipeIndex() as $candidate){
			if($candidate->matchesCraftingGrid($grid)){
				$recipe = $candidate;
				break;
			}
		}
		if($recipe === null){
			return;
		}

		$results = $recipe->getResultsFor($grid);
		if(count($results) === 0){
			return;
		}

		for($i = 0; $i < 9; ++$i){
			$item = $inventory->getItem($i);
			if(!$item->isNull()){
				$item->pop();
				$inventory->setItem($i, $item);
			}
		}

		$front = $this->getFrontFace();
		$target = $this->position->getWorld()->getTile($this->getSide($front)->getPosition());
		$targetInventory = $target instanceof Container ? $target->getRealInventory() : null;
		foreach($results as $result){
			if($targetInventory !== null && $targetInventory->canAddItem($result)){
				$targetInventory->addItem($result);
			}else{
				$this->position->getWorld()->dropItem($this->getSide($front)->getPosition()->add(0.5, 0.5, 0.5), $result);
			}
		}
	}

	private function getFrontFace() : int{
		return match($this->orientation){
			CrafterOrientation::DOWN_EAST, CrafterOrientation::DOWN_NORTH, CrafterOrientation::DOWN_SOUTH, CrafterOrientation::DOWN_WEST => Facing::DOWN,
			CrafterOrientation::UP_EAST, CrafterOrientation::UP_NORTH, CrafterOrientation::UP_SOUTH, CrafterOrientation::UP_WEST => Facing::UP,
			CrafterOrientation::EAST_UP => Facing::EAST,
			CrafterOrientation::WEST_UP => Facing::WEST,
			CrafterOrientation::NORTH_UP => Facing::NORTH,
			CrafterOrientation::SOUTH_UP => Facing::SOUTH,
		};
	}
}
