<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Beehive as TileBeehive;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\GlassBottle;
use pocketmine\entity\mob\Bee;
use pocketmine\item\Item;
use pocketmine\item\Shears;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function mt_rand;

class Beehive extends Opaque{
	public const MAX_HONEY_LEVEL = 5;
	public const MAX_DIRECTION = 3;

	protected int $honeyLevel = 0;
	protected int $direction = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_DIRECTION, $this->direction);
		$w->boundedIntAuto(0, self::MAX_HONEY_LEVEL, $this->honeyLevel);
	}

	public function getHoneyLevel() : int{ return $this->honeyLevel; }

	/** @return $this */
	public function setHoneyLevel(int $level) : self{
		if($level < 0 || $level > self::MAX_HONEY_LEVEL){
			throw new \InvalidArgumentException("Honey level must be in range 0 ... " . self::MAX_HONEY_LEVEL);
		}
		$this->honeyLevel = $level;
		return $this;
	}

	public function ticksRandomly() : bool{
		return $this->honeyLevel < self::MAX_HONEY_LEVEL;
	}

	public function onRandomTick() : void{
		if(mt_rand(0, 19) !== 0){
			return;
		}
		$box = AxisAlignedBB::one()->expand(8, 8, 8)->offset($this->position->x, $this->position->y, $this->position->z);
		foreach($this->position->getWorld()->getNearbyEntities($box) as $entity){
			if($entity instanceof Bee){
				$this->position->getWorld()->setBlock($this->position, $this->setHoneyLevel($this->honeyLevel + 1));
				return;
			}
		}
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($this->honeyLevel >= self::MAX_HONEY_LEVEL){
			if($item instanceof Shears){
				$item->applyDamage(1);
				for($i = 0; $i < 3; $i++){
					$returnedItems[] = VanillaItems::HONEYCOMB();
				}
				$this->position->getWorld()->setBlock($this->position, $this->setHoneyLevel(0));
				return true;
			}
			if($item instanceof GlassBottle){
				$item->pop();
				$returnedItems[] = VanillaItems::HONEY_BOTTLE();
				$this->position->getWorld()->setBlock($this->position, $this->setHoneyLevel(0));
				return true;
			}
		}
		return false;
	}

	public function getDirection() : int{ return $this->direction; }

	/** @return $this */
	public function setDirection(int $direction) : self{
		if($direction < 0 || $direction > self::MAX_DIRECTION){
			throw new \InvalidArgumentException("Direction must be in range 0 ... " . self::MAX_DIRECTION);
		}
		$this->direction = $direction;
		return $this;
	}
}
