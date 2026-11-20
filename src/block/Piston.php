<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\PistonArm as TilePistonArm;
use pocketmine\block\utils\AnyFacing;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use function count;

class Piston extends Opaque implements AnyFacing{
	protected int $facing = Facing::UP;

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		Facing::validate($facing);
		$this->facing = $facing;
		return $this;
	}

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
	}

	public function onNearbyBlockChange() : void{
		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if(!$tile instanceof TilePistonArm){
			return;
		}

		$powered = RedstonePowerHelper::isPowered($this);
		$extended = $this->isExtended();
		if($powered && !$extended){
			$this->extend($tile);
		}elseif(!$powered && $extended){
			$this->retract($tile);
		}
	}

	public function onBreak(Item $item, ?Player $player = null, array &$returnedItems = []) : bool{
		$front = $this->getSide($this->facing);
		if($front instanceof PistonArmCollision && $front->getFacing() === $this->facing){
			$this->position->getWorld()->setBlock($front->getPosition(), VanillaBlocks::AIR());
		}
		return parent::onBreak($item, $player, $returnedItems);
	}

	public function isExtended() : bool{
		$front = $this->getSide($this->facing);
		return $front instanceof PistonArmCollision && $front->getFacing() === $this->facing;
	}

	/**
	 * @return Block[]|null null when the move is blocked
	 */
	private function getBlocksToMove() : ?array{
		$blocks = [];
		for($i = 1; $i <= 12; ++$i){
			$block = $this->getSide($this->facing, $i);
			if($block->canBeReplaced()){
				return $blocks;
			}
			if(!$this->canMove($block)){
				return null;
			}
			$blocks[] = $block;
		}
		return null;
	}

	private function canMove(Block $block) : bool{
		if($block instanceof Piston || $block instanceof PistonArmCollision || $block instanceof MovingBlock){
			return false;
		}
		if($block->getIdInfo()->getTileClass() !== null){
			return false;
		}
		$hardness = $block->getBreakInfo()->getHardness();
		return $hardness >= 0 && $hardness < 50 && $block->getTypeId() !== BlockTypeIds::OBSIDIAN;
	}

	private function extend(TilePistonArm $tile) : void{
		$blocks = $this->getBlocksToMove();
		if($blocks === null){
			return;
		}

		$world = $this->position->getWorld();
		for($i = count($blocks) - 1; $i >= 0; --$i){
			$block = $blocks[$i];
			$world->setBlock($block->getSide($this->facing)->getPosition(), $block);
		}

		$arm = ($this instanceof StickyPiston ? VanillaBlocks::STICKY_PISTON_ARM_COLLISION() : VanillaBlocks::PISTON_ARM_COLLISION())
			->setFacing($this->facing);
		$world->setBlock($this->getSide($this->facing)->getPosition(), $arm);

		$tile->setState(2);
		$tile->setNewState(2);
		$tile->setProgress(1.0);
		$tile->setLastProgress(1.0);
		$tile->setSticky($this instanceof StickyPiston);
		$world->setBlock($this->position, $this);
	}

	private function retract(TilePistonArm $tile) : void{
		$front = $this->getSide($this->facing);
		if(!$front instanceof PistonArmCollision){
			return;
		}

		$world = $this->position->getWorld();
		$world->setBlock($front->getPosition(), VanillaBlocks::AIR());

		if($this instanceof StickyPiston){
			$pulled = $front->getSide($this->facing);
			if(!$pulled->canBeReplaced() && $this->canMove($pulled)){
				$world->setBlock($front->getPosition(), $pulled);
				$world->setBlock($pulled->getPosition(), VanillaBlocks::AIR());
			}
		}

		$tile->setState(0);
		$tile->setNewState(0);
		$tile->setProgress(0.0);
		$tile->setLastProgress(0.0);
		$tile->setSticky($this instanceof StickyPiston);
		$world->setBlock($this->position, $this);
	}
}
