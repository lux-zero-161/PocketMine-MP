<?php

declare(strict_types=1);

namespace pocketmine\world\map;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\player\Player;
use pocketmine\world\World;

final class MapManager{
	/** @var MapData[] */
	private static array $maps = [];
	private static int $nextId = 1;

	private function __construct(){
		//NOOP
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
	}
}
