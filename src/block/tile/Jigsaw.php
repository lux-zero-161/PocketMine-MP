<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\nbt\tag\CompoundTag;

class Jigsaw extends Tile{
	private const TAG_NAME = "name";
	private const TAG_TARGET = "target";
	private const TAG_POOL = "pool";
	private const TAG_JOINT = "joint";
	private const TAG_FINAL_STATE = "final_state";
	private const TAG_SELECTION_PRIORITY = "selection_priority";
	private const TAG_PLACEMENT_PRIORITY = "placement_priority";
	private const TAG_POWERED = "powered";

	private string $name = "minecraft:empty";
	private string $target = "minecraft:empty";
	private string $pool = "";
	private string $joint = "rollable";
	private string $finalState = "minecraft:air";
	private int $selectionPriority = 0;
	private int $placementPriority = 0;
	private bool $powered = false;

	public function readSaveData(CompoundTag $nbt) : void{
		$this->name = $nbt->getString(self::TAG_NAME, "minecraft:empty");
		$this->target = $nbt->getString(self::TAG_TARGET, "minecraft:empty");
		$this->pool = $nbt->getString(self::TAG_POOL, "");
		$this->joint = $nbt->getString(self::TAG_JOINT, "rollable");
		$this->finalState = $nbt->getString(self::TAG_FINAL_STATE, "minecraft:air");
		$this->selectionPriority = $nbt->getInt(self::TAG_SELECTION_PRIORITY, 0);
		$this->placementPriority = $nbt->getInt(self::TAG_PLACEMENT_PRIORITY, 0);
		$this->powered = $nbt->getByte(self::TAG_POWERED, 0) !== 0;
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setString(self::TAG_NAME, $this->name);
		$nbt->setString(self::TAG_TARGET, $this->target);
		$nbt->setString(self::TAG_POOL, $this->pool);
		$nbt->setString(self::TAG_JOINT, $this->joint);
		$nbt->setString(self::TAG_FINAL_STATE, $this->finalState);
		$nbt->setInt(self::TAG_SELECTION_PRIORITY, $this->selectionPriority);
		$nbt->setInt(self::TAG_PLACEMENT_PRIORITY, $this->placementPriority);
		$nbt->setByte(self::TAG_POWERED, $this->powered ? 1 : 0);
	}

	public function getName() : string{ return $this->name; }
	public function setName(string $name) : void{ $this->name = $name; }

	public function getTarget() : string{ return $this->target; }
	public function setTarget(string $target) : void{ $this->target = $target; }

	public function getPool() : string{ return $this->pool; }
	public function setPool(string $pool) : void{ $this->pool = $pool; }

	public function getJoint() : string{ return $this->joint; }
	public function setJoint(string $joint) : void{ $this->joint = $joint; }

	public function getFinalState() : string{ return $this->finalState; }
	public function setFinalState(string $finalState) : void{ $this->finalState = $finalState; }

	public function getSelectionPriority() : int{ return $this->selectionPriority; }
	public function setSelectionPriority(int $selectionPriority) : void{ $this->selectionPriority = $selectionPriority; }

	public function getPlacementPriority() : int{ return $this->placementPriority; }
	public function setPlacementPriority(int $placementPriority) : void{ $this->placementPriority = $placementPriority; }

	public function isPowered() : bool{ return $this->powered; }
	public function setPowered(bool $powered) : void{ $this->powered = $powered; }
}
