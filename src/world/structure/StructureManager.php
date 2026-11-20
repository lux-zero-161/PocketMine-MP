<?php

declare(strict_types=1);

namespace pocketmine\world\structure;

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\World;
use function count;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_dir;
use function mkdir;
use function strtolower;

/**
 * Saves and loads simple block structures as NBT files.
 *
 * Block states are stored as runtime state IDs, so files are only guaranteed to be compatible with the same
 * PocketMine version. Tiles are stored and restored as raw NBT.
 */
final class StructureManager{
	private const FORMAT_VERSION = 1;
	private const TAG_FORMAT = "Format";
	private const TAG_SIZE = "Size";
	private const TAG_PALETTE = "Palette";
	private const TAG_BLOCKS = "Blocks";
	private const TAG_TILES = "Tiles";
	private const TAG_NAME = "Name";
	private const TAG_STATE = "State";
	private const TAG_X = "X";
	private const TAG_Y = "Y";
	private const TAG_Z = "Z";
	private const TAG_PALETTE_STATE = "State";

	public const MAX_VOLUME = 64 * 64 * 64;

	private function __construct(){
		//NOOP
	}

	public static function getDirectory(World $world) : string{
		$path = $world->getServer()->getDataPath() . "structures";
		if(!is_dir($path)){
			@mkdir($path, 0777, true);
		}
		return $path;
	}

	public static function getFile(World $world, string $name) : string{
		return self::getDirectory($world) . "/" . strtolower($name) . ".nbt";
	}

	public static function isValidName(string $name) : bool{
		return preg_match('/^[A-Za-z0-9_\-.]{1,64}$/', $name) === 1;
	}

	public static function save(World $world, string $name, Vector3 $first, Vector3 $second) : bool{
		$minX = min($first->getFloorX(), $second->getFloorX());
		$minY = max($world->getMinY(), min($first->getFloorY(), $second->getFloorY()));
		$minZ = min($first->getFloorZ(), $second->getFloorZ());
		$maxX = max($first->getFloorX(), $second->getFloorX());
		$maxY = min($world->getMaxY() - 1, max($first->getFloorY(), $second->getFloorY()));
		$maxZ = max($first->getFloorZ(), $second->getFloorZ());

		$sizeX = $maxX - $minX + 1;
		$sizeY = $maxY - $minY + 1;
		$sizeZ = $maxZ - $minZ + 1;
		if($sizeX <= 0 || $sizeY <= 0 || $sizeZ <= 0 || $sizeX * $sizeY * $sizeZ > self::MAX_VOLUME){
			return false;
		}

		$palette = [];
		$paletteIndex = [];
		$blocks = [];
		$tiles = [];

		for($x = $minX; $x <= $maxX; ++$x){
			for($y = $minY; $y <= $maxY; ++$y){
				for($z = $minZ; $z <= $maxZ; ++$z){
					$block = $world->getBlockAt($x, $y, $z);
					$state = $block->getStateId();
					if(!isset($paletteIndex[$state])){
						$paletteIndex[$state] = count($palette);
						$palette[] = CompoundTag::create()
							->setString(self::TAG_NAME, $block->getName())
							->setInt(self::TAG_PALETTE_STATE, $state);
					}
					$blocks[] = CompoundTag::create()
						->setInt(self::TAG_X, $x - $minX)
						->setInt(self::TAG_Y, $y - $minY)
						->setInt(self::TAG_Z, $z - $minZ)
						->setInt(self::TAG_STATE, $state);

					$tile = $world->getTileAt($x, $y, $z);
					if($tile !== null){
						$tileTag = $tile->saveNBT();
						$tileTag->setInt("x", $x - $minX);
						$tileTag->setInt("y", $y - $minY);
						$tileTag->setInt("z", $z - $minZ);
						$tiles[] = $tileTag;
					}
				}
			}
		}

		$root = CompoundTag::create()
			->setInt(self::TAG_FORMAT, self::FORMAT_VERSION)
			->setTag(self::TAG_SIZE, new ListTag([
				new IntTag($sizeX),
				new IntTag($sizeY),
				new IntTag($sizeZ)
			], NBT::TAG_Int))
			->setTag(self::TAG_PALETTE, new ListTag($palette, NBT::TAG_Compound))
			->setTag(self::TAG_BLOCKS, new ListTag($blocks, NBT::TAG_Compound))
			->setTag(self::TAG_TILES, new ListTag($tiles, NBT::TAG_Compound));

		$serializer = new LittleEndianNbtSerializer();
		return file_put_contents(self::getFile($world, $name), $serializer->write(new TreeRoot($root))) !== false;
	}

	public static function load(World $world, string $name, Vector3 $target, int $rotation = 0) : bool{
		$file = self::getFile($world, $name);
		if(!is_file($file)){
			return false;
		}
		$contents = file_get_contents($file);
		if($contents === false){
			return false;
		}
		try{
			$root = (new LittleEndianNbtSerializer())->read($contents)->mustGetCompoundTag();
		}catch(\Throwable){
			return false;
		}

		$size = $root->getListTag(self::TAG_SIZE, IntTag::class);
		$paletteTag = $root->getListTag(self::TAG_PALETTE, CompoundTag::class);
		$blockTags = $root->getListTag(self::TAG_BLOCKS, CompoundTag::class);
		if($size === null || $paletteTag === null || $blockTags === null){
			return false;
		}
		$sizeValues = $size->getValue();
		if(count($sizeValues) !== 3){
			return false;
		}
		$sizeX = $sizeValues[0]->getValue();
		$sizeY = $sizeValues[1]->getValue();
		$sizeZ = $sizeValues[2]->getValue();
		if($sizeX <= 0 || $sizeY <= 0 || $sizeZ <= 0 || $sizeX * $sizeY * $sizeZ > self::MAX_VOLUME){
			return false;
		}

		$registry = RuntimeBlockStateRegistry::getInstance();
		$rotation = (($rotation % 4) + 4) % 4;
		$baseX = $target->getFloorX();
		$baseY = $target->getFloorY();
		$baseZ = $target->getFloorZ();

		foreach($blockTags->getValue() as $blockTag){
			$stateId = $blockTag->getInt(self::TAG_STATE, -1);
			if($stateId < 0){
				continue;
			}
			$x = $blockTag->getInt(self::TAG_X);
			$y = $blockTag->getInt(self::TAG_Y);
			$z = $blockTag->getInt(self::TAG_Z);
			[$rx, $rz] = match($rotation){
				1 => [$z, $sizeX - 1 - $x],
				2 => [$sizeX - 1 - $x, $sizeZ - 1 - $z],
				3 => [$sizeZ - 1 - $z, $x],
				default => [$x, $z]
			};
			$block = $registry->fromStateId($stateId);
			$world->setBlockAt($baseX + $rx, $baseY + $y, $baseZ + $rz, $block, false);
		}

		$tileTags = $root->getListTag(self::TAG_TILES, CompoundTag::class);
		if($tileTags !== null){
			foreach($tileTags->getValue() as $tileTag){
				$x = $tileTag->getInt("x");
				$y = $tileTag->getInt("y");
				$z = $tileTag->getInt("z");
				[$rx, $rz] = match($rotation){
					1 => [$z, $sizeX - 1 - $x],
					2 => [$sizeX - 1 - $x, $sizeZ - 1 - $z],
					3 => [$sizeZ - 1 - $z, $x],
					default => [$x, $z]
				};
				$tile = $world->getTileAt($baseX + $rx, $baseY + $y, $baseZ + $rz);
				if($tile !== null){
					try{
						$tile->readSaveData($tileTag);
					}catch(\Throwable){
						//ignore corrupt tile data
					}
				}
			}
		}

		return true;
	}

	public static function delete(World $world, string $name) : bool{
		$file = self::getFile($world, $name);
		return is_file($file) && @unlink($file);
	}

	/**
	 * @return string[]
	 */
	public static function list(World $world) : array{
		$dir = self::getDirectory($world);
		$result = [];
		$files = scandir($dir);
		if($files === false){
			return [];
		}
		foreach($files as $file){
			if(str_ends_with($file, ".nbt")){
				$result[] = substr($file, 0, -4);
			}
		}
		return $result;
	}
}
