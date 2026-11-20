<?php

declare(strict_types=1);

namespace pocketmine\world\map;

use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function max;

final class MapManager{
	/** @var MapData[] */
	private static array $maps = [];
	private static int $nextId = 1;
	private static ?Server $server = null;

	private function __construct(){
		//NOOP
	}

	public static function init(Server $server) : void{
		self::$server = $server;
		self::load();
	}

	public static function save() : void{
		if(self::$server === null){
			return;
		}
		$tags = [];
		foreach(self::$maps as $map){
			$tags[] = $map->toNbt();
		}
		$root = CompoundTag::create()->setTag("Maps", new ListTag($tags, \pocketmine\nbt\NBT::TAG_Compound));
		file_put_contents(self::getFile(), (new LittleEndianNbtSerializer())->write(new TreeRoot($root)));
	}

	private static function load() : void{
		$file = self::getFile();
		if(!file_exists($file)){
			return;
		}
		try{
			$contents = file_get_contents($file);
			if($contents === false){
				return;
			}
			$root = (new LittleEndianNbtSerializer())->read($contents)->mustGetCompoundTag();
			$maps = $root->getListTag("Maps", CompoundTag::class);
			if($maps === null){
				return;
			}
			self::$maps = [];
			self::$nextId = 1;
			foreach($maps->getValue() as $tag){
				$map = MapData::fromNbt($tag);
				if($map !== null){
					self::$maps[$map->getId()] = $map;
					self::$nextId = max(self::$nextId, $map->getId() + 1);
				}
			}
		}catch(\Throwable){
			//ignore corrupt map data
		}
	}

	private static function getFile() : string{
		return (self::$server?->getDataPath() ?? "") . "maps.nbt";
	}

	public static function createMap(World $world, Vector3 $center, int $scale = 0) : MapData{
		$map = new MapData(self::$nextId++, $world->getDimensionId(), $center->getFloorX(), $center->getFloorZ(), $scale);
		self::$maps[$map->getId()] = $map;
		return $map;
	}

	public static function getMap(int $id) : ?MapData{
		return self::$maps[$id] ?? null;
	}

	public static function sendMap(Player $player, int $mapId) : void{
		$map = self::$maps[$mapId] ?? null;
		if($map === null){
			return;
		}
		$map->render($player->getWorld());

		$packet = new ClientboundMapItemDataPacket();
		$packet->mapId = $map->getId();
		$packet->dimensionId = $map->getDimensionId();
		$packet->isLocked = true;
		$packet->origin = new BlockPosition($map->getCenterX(), 64, $map->getCenterZ());
		$packet->scale = $map->getScale();
		$packet->colors = $map->toImage();

		$player->getNetworkSession()->sendDataPacket($packet);
		self::save();
	}
}
