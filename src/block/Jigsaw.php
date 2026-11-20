<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Jigsaw as TileJigsaw;
use pocketmine\block\utils\AnyFacing;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;
use pocketmine\world\structure\StructureManager;
use function max;

class Jigsaw extends Opaque implements AnyFacing{
	public const MAX_ROTATION = 3;

	protected int $facing = Facing::DOWN;
	protected int $rotation = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
		$w->boundedIntAuto(0, self::MAX_ROTATION, $this->rotation);
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		Facing::validate($facing);
		$this->facing = $facing;
		return $this;
	}

	public function getRotation() : int{ return $this->rotation; }

	/** @return $this */
	public function setRotation(int $rotation) : self{
		if($rotation < 0 || $rotation > self::MAX_ROTATION){
			throw new \InvalidArgumentException("Rotation must be in range 0 ... " . self::MAX_ROTATION);
		}
		$this->rotation = $rotation;
		return $this;
	}

	public function onNearbyBlockChange() : void{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileJigsaw){
			return;
		}
		$powered = RedstonePowerHelper::isPowered($this);
		if($powered && !$tile->isPowered()){
			$tile->setPowered(true);
			$count = 0;
			$this->generate($tile, 0, $count);
		}elseif(!$powered && $tile->isPowered()){
			$tile->setPowered(false);
		}
	}

	/**
	 * Loads the structure named by the tile's pool and recursively expands jigsaw connectors in the placed piece.
	 */
	public function generate(TileJigsaw $tile, int $depth = 0, int &$count = 0) : void{
		if($depth > 4 || $count >= 16){
			return;
		}
		$world = $this->position->getWorld();
		$pool = $tile->getPool();
		if($pool === ""){
			return;
		}
		$size = StructureManager::getSize($world, $pool);
		if($size === null){
			return;
		}
		if(!StructureManager::load($world, $pool, $this->position, $this->rotation)){
			return;
		}
		$count++;

		$radiusX = (int) max($size->getFloorX(), $size->getFloorZ()) + 1;
		$radiusY = $size->getFloorY() + 1;
		$baseX = $this->position->getFloorX();
		$baseY = $this->position->getFloorY();
		$baseZ = $this->position->getFloorZ();

		for($dx = 0; $dx <= $radiusX; ++$dx){
			for($dy = 0; $dy <= $radiusY; ++$dy){
				for($dz = 0; $dz <= $radiusX; ++$dz){
					$block = $world->getBlockAt($baseX + $dx, $baseY + $dy, $baseZ + $dz);
					if(!$block instanceof self || $block === $this){
						continue;
					}
					$child = $world->getTileAt($baseX + $dx, $baseY + $dy, $baseZ + $dz);
					if($child instanceof TileJigsaw && $child !== $tile && $child->getPool() !== ""){
						$block->generate($child, $depth + 1, $count);
					}
				}
			}
		}
	}
}
