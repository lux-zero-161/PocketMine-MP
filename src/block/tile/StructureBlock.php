<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\block\utils\StructureBlockMode;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use function max;
use function min;

class StructureBlock extends Tile{
	private const TAG_NAME = "name";
	private const TAG_MODE = "mode";
	private const TAG_OFFSET_X = "offsetX";
	private const TAG_OFFSET_Y = "offsetY";
	private const TAG_OFFSET_Z = "offsetZ";
	private const TAG_SIZE_X = "sizeX";
	private const TAG_SIZE_Y = "sizeY";
	private const TAG_SIZE_Z = "sizeZ";
	private const TAG_ROTATION = "rotation";
	private const TAG_MIRROR = "mirror";
	private const TAG_INTEGRITY = "integrity";
	private const TAG_SEED = "seed";
	private const TAG_IGNORE_ENTITIES = "ignoreEntities";
	private const TAG_SHOW_BOUNDING_BOX = "showBoundingBox";
	private const TAG_INCLUDE_PLAYERS = "includePlayers";
	private const TAG_REMOVE_BLOCKS = "removeBlocks";
	private const TAG_POWERED = "powered";

	private string $structureName = "";
	private StructureBlockMode $mode = StructureBlockMode::DATA;
	private int $offsetX = 0;
	private int $offsetY = 0;
	private int $offsetZ = 0;
	private int $sizeX = 5;
	private int $sizeY = 5;
	private int $sizeZ = 5;
	private int $rotation = 0;
	private int $mirror = 0;
	private float $integrity = 1.0;
	private int $seed = 0;
	private bool $ignoreEntities = true;
	private bool $showBoundingBox = true;
	private bool $includePlayers = false;
	private bool $removeBlocks = false;
	private bool $powered = false;

	public function readSaveData(CompoundTag $nbt) : void{
		$this->structureName = $nbt->getString(self::TAG_NAME, "");
		$mode = $nbt->getInt(self::TAG_MODE, 0);
		$this->mode = match($mode){
			1 => StructureBlockMode::SAVE,
			2 => StructureBlockMode::LOAD,
			3 => StructureBlockMode::CORNER,
			5 => StructureBlockMode::EXPORT,
			4 => StructureBlockMode::INVALID,
			default => StructureBlockMode::DATA
		};
		$this->offsetX = $nbt->getInt(self::TAG_OFFSET_X, 0);
		$this->offsetY = $nbt->getInt(self::TAG_OFFSET_Y, 0);
		$this->offsetZ = $nbt->getInt(self::TAG_OFFSET_Z, 0);
		$this->sizeX = max(1, min(64, $nbt->getInt(self::TAG_SIZE_X, 5)));
		$this->sizeY = max(1, min(64, $nbt->getInt(self::TAG_SIZE_Y, 5)));
		$this->sizeZ = max(1, min(64, $nbt->getInt(self::TAG_SIZE_Z, 5)));
		$this->rotation = max(0, min(3, $nbt->getInt(self::TAG_ROTATION, 0)));
		$this->mirror = max(0, min(3, $nbt->getInt(self::TAG_MIRROR, 0)));
		$this->integrity = max(0.0, min(1.0, $nbt->getFloat(self::TAG_INTEGRITY, 1.0)));
		$this->seed = $nbt->getInt(self::TAG_SEED, 0);
		$this->ignoreEntities = $nbt->getByte(self::TAG_IGNORE_ENTITIES, 1) !== 0;
		$this->showBoundingBox = $nbt->getByte(self::TAG_SHOW_BOUNDING_BOX, 1) !== 0;
		$this->includePlayers = $nbt->getByte(self::TAG_INCLUDE_PLAYERS, 0) !== 0;
		$this->removeBlocks = $nbt->getByte(self::TAG_REMOVE_BLOCKS, 0) !== 0;
		$this->powered = $nbt->getByte(self::TAG_POWERED, 0) !== 0;
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setString(self::TAG_NAME, $this->structureName);
		$nbt->setInt(self::TAG_MODE, self::modeToInt($this->mode));
		$nbt->setInt(self::TAG_OFFSET_X, $this->offsetX);
		$nbt->setInt(self::TAG_OFFSET_Y, $this->offsetY);
		$nbt->setInt(self::TAG_OFFSET_Z, $this->offsetZ);
		$nbt->setInt(self::TAG_SIZE_X, $this->sizeX);
		$nbt->setInt(self::TAG_SIZE_Y, $this->sizeY);
		$nbt->setInt(self::TAG_SIZE_Z, $this->sizeZ);
		$nbt->setInt(self::TAG_ROTATION, $this->rotation);
		$nbt->setInt(self::TAG_MIRROR, $this->mirror);
		$nbt->setFloat(self::TAG_INTEGRITY, $this->integrity);
		$nbt->setInt(self::TAG_SEED, $this->seed);
		$nbt->setByte(self::TAG_IGNORE_ENTITIES, $this->ignoreEntities ? 1 : 0);
		$nbt->setByte(self::TAG_SHOW_BOUNDING_BOX, $this->showBoundingBox ? 1 : 0);
		$nbt->setByte(self::TAG_INCLUDE_PLAYERS, $this->includePlayers ? 1 : 0);
		$nbt->setByte(self::TAG_REMOVE_BLOCKS, $this->removeBlocks ? 1 : 0);
		$nbt->setByte(self::TAG_POWERED, $this->powered ? 1 : 0);
	}

	public static function modeToInt(StructureBlockMode $mode) : int{
		return match($mode){
			StructureBlockMode::SAVE => 1,
			StructureBlockMode::LOAD => 2,
			StructureBlockMode::CORNER => 3,
			StructureBlockMode::INVALID => 4,
			StructureBlockMode::EXPORT => 5,
			default => 0
		};
	}

	public static function modeFromInt(int $mode) : StructureBlockMode{
		return match($mode){
			1 => StructureBlockMode::SAVE,
			2 => StructureBlockMode::LOAD,
			3 => StructureBlockMode::CORNER,
			5 => StructureBlockMode::EXPORT,
			4 => StructureBlockMode::INVALID,
			default => StructureBlockMode::DATA
		};
	}

	public function getStructureName() : string{ return $this->structureName; }
	public function setStructureName(string $name) : void{ $this->structureName = $name; }

	public function getMode() : StructureBlockMode{ return $this->mode; }
	public function setMode(StructureBlockMode $mode) : void{ $this->mode = $mode; }

	public function getOffset() : Vector3{ return new Vector3($this->offsetX, $this->offsetY, $this->offsetZ); }

	public function setOffset(Vector3 $offset) : void{
		$this->offsetX = $offset->getFloorX();
		$this->offsetY = $offset->getFloorY();
		$this->offsetZ = $offset->getFloorZ();
	}

	public function getSize() : Vector3{ return new Vector3($this->sizeX, $this->sizeY, $this->sizeZ); }

	public function setSize(Vector3 $size) : void{
		$this->sizeX = max(1, min(64, $size->getFloorX()));
		$this->sizeY = max(1, min(64, $size->getFloorY()));
		$this->sizeZ = max(1, min(64, $size->getFloorZ()));
	}

	public function getRotation() : int{ return $this->rotation; }
	public function setRotation(int $rotation) : void{ $this->rotation = max(0, min(3, $rotation)); }

	public function getMirror() : int{ return $this->mirror; }
	public function setMirror(int $mirror) : void{ $this->mirror = max(0, min(3, $mirror)); }

	public function getIntegrity() : float{ return $this->integrity; }
	public function setIntegrity(float $integrity) : void{ $this->integrity = max(0.0, min(1.0, $integrity)); }

	public function getSeed() : int{ return $this->seed; }
	public function setSeed(int $seed) : void{ $this->seed = $seed; }

	public function isIgnoringEntities() : bool{ return $this->ignoreEntities; }
	public function setIgnoreEntities(bool $ignoreEntities) : void{ $this->ignoreEntities = $ignoreEntities; }

	public function isShowingBoundingBox() : bool{ return $this->showBoundingBox; }
	public function setShowBoundingBox(bool $showBoundingBox) : void{ $this->showBoundingBox = $showBoundingBox; }

	public function isIncludingPlayers() : bool{ return $this->includePlayers; }
	public function setIncludePlayers(bool $includePlayers) : void{ $this->includePlayers = $includePlayers; }

	public function isRemovingBlocks() : bool{ return $this->removeBlocks; }
	public function setRemoveBlocks(bool $removeBlocks) : void{ $this->removeBlocks = $removeBlocks; }

	public function isPowered() : bool{ return $this->powered; }
	public function setPowered(bool $powered) : void{ $this->powered = $powered; }
}
