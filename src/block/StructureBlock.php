<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\StructureBlock as TileStructureBlock;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\block\utils\StructureBlockMode;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\StructureBlockUpdateClientboundPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;
use pocketmine\network\mcpe\protocol\types\StructureSettings;
use pocketmine\player\Player;
use pocketmine\world\structure\StructureManager;

class StructureBlock extends Opaque{
	protected StructureBlockMode $mode = StructureBlockMode::DATA;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->mode);
	}

	public function getMode() : StructureBlockMode{ return $this->mode; }

	/** @return $this */
	public function setMode(StructureBlockMode $mode) : self{
		$this->mode = $mode;
		return $this;
	}

	public function onNearbyBlockChange() : void{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileStructureBlock){
			return;
		}
		$powered = RedstonePowerHelper::isPowered($this);
		if($powered && !$tile->isPowered()){
			$tile->setPowered(true);
			$this->execute($tile);
		}elseif(!$powered && $tile->isPowered()){
			$tile->setPowered(false);
		}
	}

	public function execute(TileStructureBlock $tile) : bool{
		$world = $this->position->getWorld();
		$name = $tile->getStructureName();
		if($name === ""){
			return false;
		}

		$base = $this->position->addVector($tile->getOffset());
		$size = $tile->getSize();
		$max = $base->addVector($size)->subtract(1, 1, 1);

		switch($tile->getMode()){
			case StructureBlockMode::SAVE:
				return StructureManager::save($world, $name, $base, $max);
			case StructureBlockMode::LOAD:
				return StructureManager::load($world, $name, $base, $tile->getRotation());
			default:
				return false;
		}
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player === null || (!$player->isCreative() && !$player->hasPermission(\pocketmine\permission\DefaultPermissions::ROOT_OPERATOR))){
			return false;
		}
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileStructureBlock){
			return false;
		}

		$settings = new StructureSettings();
		$settings->paletteName = "default";
		$settings->ignoreEntities = $tile->isIgnoringEntities();
		$settings->ignoreBlocks = false;
		$settings->allowNonTickingChunks = false;
		$settings->dimensions = new BlockPosition($tile->getSize()->getFloorX(), $tile->getSize()->getFloorY(), $tile->getSize()->getFloorZ());
		$settings->offset = new BlockPosition($tile->getOffset()->getFloorX(), $tile->getOffset()->getFloorY(), $tile->getOffset()->getFloorZ());
		$settings->lastTouchedByPlayerID = $player->getId();
		$settings->rotation = $tile->getRotation();
		$settings->mirror = $tile->getMirror();
		$settings->animationMode = 0;
		$settings->animationSeconds = 0.0;
		$settings->integrityValue = $tile->getIntegrity();
		$settings->integritySeed = $tile->getSeed();
		$settings->pivot = new Vector3(0, 0, 0);

		$editorData = new StructureEditorData();
		$editorData->structureName = $tile->getStructureName();
		$editorData->filteredStructureName = $tile->getStructureName();
		$editorData->structureDataField = "";
		$editorData->includePlayers = $tile->isIncludingPlayers();
		$editorData->showBoundingBox = $tile->isShowingBoundingBox();
		$editorData->structureBlockType = TileStructureBlock::modeToInt($tile->getMode());
		$editorData->structureSettings = $settings;
		$editorData->structureRedstoneSaveMode = 0;

		$player->getNetworkSession()->sendDataPacket(StructureBlockUpdateClientboundPacket::create(
			new BlockPosition($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ()),
			$editorData,
			$tile->isPowered(),
			false
		));
		return true;
	}
}
