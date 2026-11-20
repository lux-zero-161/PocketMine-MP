<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\world\World;

class TrialSpawner extends Tile{
	private const TAG_WAVE = "Wave";
	private const TAG_ACTIVE = "Active";
	private const TAG_SPAWNED_ENTITIES = "SpawnedEntities";

	private int $wave = 0;
	private bool $active = false;
	/** @var int[] */
	private array $spawnedEntities = [];

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
	}

	public function getWave() : int{ return $this->wave; }
	public function setWave(int $wave) : void{ $this->wave = $wave; }

	public function isActive() : bool{ return $this->active; }
	public function setActive(bool $active) : void{ $this->active = $active; }

	/** @return int[] */
	public function getSpawnedEntities() : array{ return $this->spawnedEntities; }
	public function clearSpawnedEntities() : void{ $this->spawnedEntities = []; }
	public function addSpawnedEntity(int $id) : void{ $this->spawnedEntities[] = $id; }

	public function readSaveData(CompoundTag $nbt) : void{
		$this->wave = $nbt->getInt(self::TAG_WAVE, 0);
		$this->active = $nbt->getByte(self::TAG_ACTIVE, 0) !== 0;
		$this->spawnedEntities = [];
		$list = $nbt->getListTag(self::TAG_SPAWNED_ENTITIES, IntTag::class);
		if($list !== null){
			foreach($list->getValue() as $tag){
				$this->spawnedEntities[] = $tag->getValue();
			}
		}
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setInt(self::TAG_WAVE, $this->wave);
		$nbt->setByte(self::TAG_ACTIVE, $this->active ? 1 : 0);
		$values = [];
		foreach($this->spawnedEntities as $id){
			$values[] = new IntTag($id);
		}
		$nbt->setTag(self::TAG_SPAWNED_ENTITIES, new ListTag($values, NBT::TAG_Int));
	}
}
