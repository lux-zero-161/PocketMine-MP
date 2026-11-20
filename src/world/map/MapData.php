<?php

declare(strict_types=1);

namespace pocketmine\world\map;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\color\Color;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\world\World;
use function array_values;
use function count;
use function max;
use function min;
use function str_contains;
use function strtolower;

final class MapData{
	public const SIZE = 128;

	/** @var int[] row-major RGB values, 0xRRGGBB */
	private array $colors = [];
	private int $lastRenderedTick = -1;

	public function __construct(
		private int $id,
		private int $dimensionId,
		private int $centerX,
		private int $centerZ,
		private int $scale = 0
	){
		$this->colors = array_fill(0, self::SIZE * self::SIZE, 0x808080);
	}

	public function getId() : int{ return $this->id; }

	public function getDimensionId() : int{ return $this->dimensionId; }

	public function getCenterX() : int{ return $this->centerX; }

	public function getCenterZ() : int{ return $this->centerZ; }

	public function getScale() : int{ return $this->scale; }

	/**
	 * Renders the map from the given world. Rendering is throttled to once every 20 ticks.
	 */
	public function render(World $world) : void{
		$tick = $world->getServer()->getTick();
		if($this->lastRenderedTick >= 0 && $tick - $this->lastRenderedTick < 20){
			return;
		}
		$this->lastRenderedTick = $tick;

		$step = 1 << $this->scale;
		$half = intdiv(self::SIZE, 2);
		$baseX = $this->centerX - $half * $step;
		$baseZ = $this->centerZ - $half * $step;

		for($pz = 0; $pz < self::SIZE; ++$pz){
			$worldZ = $baseZ + $pz * $step;
			for($px = 0; $px < self::SIZE; ++$px){
				$worldX = $baseX + $px * $step;
				$this->colors[$pz * self::SIZE + $px] = $this->samplePixel($world, $worldX, $worldZ);
			}
		}
	}

	private function samplePixel(World $world, int $x, int $z) : int{
		try{
			$y = $world->getHighestBlockAt($x, $z);
		}catch(\Throwable){
			return 0x808080;
		}
		if($y === null){
			return 0x808080;
		}
		$block = $world->getBlockAt($x, $y, $z);
		[$r, $g, $b] = self::getBlockColor($block);
		$shade = 1.0 + max(-0.25, min(0.25, ($y - 64) / 256));
		return (min(255, (int) ($r * $shade)) << 16) | (min(255, (int) ($g * $shade)) << 8) | min(255, (int) ($b * $shade));
	}

	/**
	 * @return array{int, int, int}
	 */
	private static function getBlockColor(Block $block) : array{
		$id = $block->getTypeId();
		if($id === BlockTypeIds::WATER){
			return [64, 96, 220];
		}
		if($id === BlockTypeIds::LAVA){
			return [235, 100, 20];
		}
		if($id === BlockTypeIds::AIR){
			return [128, 128, 128];
		}

		$name = strtolower($block->getName());
		if(str_contains($name, "water")){
			return [64, 96, 220];
		}
		if(str_contains($name, "lava") || str_contains($name, "magma")){
			return [235, 100, 20];
		}
		if(str_contains($name, "grass") || str_contains($name, "leaves") || str_contains($name, "moss") || str_contains($name, "vine")){
			return [95, 159, 53];
		}
		if(str_contains($name, "snow") || str_contains($name, "ice")){
			return [235, 245, 250];
		}
		if(str_contains($name, "sand")){
			return [219, 207, 163];
		}
		if(str_contains($name, "dirt") || str_contains($name, "mud") || str_contains($name, "podzol") || str_contains($name, "farmland") || str_contains($name, "path")){
			return [134, 96, 67];
		}
		if(str_contains($name, "log") || str_contains($name, "wood") || str_contains($name, "planks") || str_contains($name, "stem")){
			return [156, 127, 78];
		}
		if(str_contains($name, "deepslate")){
			return [70, 70, 75];
		}
		if(str_contains($name, "stone") || str_contains($name, "cobble") || str_contains($name, "andesite") || str_contains($name, "gravel") || str_contains($name, "ore")){
			return [128, 128, 128];
		}
		if(str_contains($name, "netherrack")){
			return [111, 54, 52];
		}
		if(str_contains($name, "end_stone")){
			return [219, 222, 158];
		}
		return [150, 150, 150];
	}

	public function toNbt() : CompoundTag{
		return CompoundTag::create()
			->setInt("id", $this->id)
			->setInt("dimension", $this->dimensionId)
			->setInt("centerX", $this->centerX)
			->setInt("centerZ", $this->centerZ)
			->setInt("scale", $this->scale)
			->setTag("colors", new IntArrayTag(array_values($this->colors)));
	}

	public static function fromNbt(CompoundTag $tag) : ?self{
		$id = $tag->getInt("id", -1);
		if($id <= 0){
			return null;
		}
		$colorsTag = $tag->getTag("colors");
		if(!$colorsTag instanceof IntArrayTag){
			return null;
		}
		$map = new self(
			$id,
			$tag->getInt("dimension", 0),
			$tag->getInt("centerX", 0),
			$tag->getInt("centerZ", 0),
			max(0, min(4, $tag->getInt("scale", 0)))
		);
		$colors = array_values($colorsTag->getValue());
		if(count($colors) === self::SIZE * self::SIZE){
			$map->colors = $colors;
		}
		return $map;
	}

	public function toImage() : MapImage{
		$rows = [];
		for($z = 0; $z < self::SIZE; ++$z){
			$row = [];
			for($x = 0; $x < self::SIZE; ++$x){
				$rgb = $this->colors[$z * self::SIZE + $x];
				$row[] = new Color(($rgb >> 16) & 0xff, ($rgb >> 8) & 0xff, $rgb & 0xff);
			}
			$rows[] = $row;
		}
		return new MapImage($rows);
	}
}
