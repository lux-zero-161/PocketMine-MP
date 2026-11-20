<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\nbt\tag\CompoundTag;

class FilledMap extends Item{
	private const TAG_MAP_UUID = "map_uuid"; //TAG_Long
	private const TAG_MAP_NAME_INDEX = "map_name_index"; //TAG_Int

	private int $mapId = 0;

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getMapId() : int{
		if($this->mapId !== 0){
			return $this->mapId;
		}
		return $this->getNamedTag()->getLong(self::TAG_MAP_UUID, 0);
	}

	/** @return $this */
	public function setMapId(int $mapId) : self{
		$this->mapId = $mapId;
		$tag = $this->getNamedTag();
		$tag->setLong(self::TAG_MAP_UUID, $mapId);
		$tag->setInt(self::TAG_MAP_NAME_INDEX, $mapId);
		$this->setNamedTag($tag);
		return $this;
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);
		$this->mapId = $tag->getLong(self::TAG_MAP_UUID, 0);
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);
		if($this->mapId !== 0){
			$tag->setLong(self::TAG_MAP_UUID, $this->mapId);
			$tag->setInt(self::TAG_MAP_NAME_INDEX, $this->mapId);
		}
	}
}
