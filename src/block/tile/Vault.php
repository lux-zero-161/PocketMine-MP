<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\World;
use function in_array;

class Vault extends Tile{
	private const TAG_USED_PLAYERS = "UsedPlayers";

	/** @var string[] raw UUIDs */
	private array $usedPlayers = [];

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
	}

	public function hasPlayerUsed(string $rawUuid) : bool{
		return in_array($rawUuid, $this->usedPlayers, true);
	}

	public function markPlayerUsed(string $rawUuid) : void{
		if(!$this->hasPlayerUsed($rawUuid)){
			$this->usedPlayers[] = $rawUuid;
		}
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->usedPlayers = [];
		$list = $nbt->getListTag(self::TAG_USED_PLAYERS, StringTag::class);
		if($list !== null){
			foreach($list->getValue() as $tag){
				$this->usedPlayers[] = $tag->getValue();
			}
		}
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$values = [];
		foreach($this->usedPlayers as $uuid){
			$values[] = new StringTag($uuid);
		}
		$nbt->setTag(self::TAG_USED_PLAYERS, new ListTag($values, \pocketmine\nbt\NBT::TAG_String));
	}
}
