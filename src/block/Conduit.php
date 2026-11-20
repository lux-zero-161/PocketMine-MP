<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\SupportType;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use function abs;

class Conduit extends Transparent{
	public const MIN_FRAME_BLOCKS = 16;
	public const MAX_FRAME_BLOCKS = 42;

	public function getLightLevel() : int{
		return 15;
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}

	public function onPostPlace() : void{
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 40);
	}

	public function onNearbyBlockChange() : void{
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 40);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$frameBlocks = $this->countFrameBlocks();
		if($frameBlocks >= self::MIN_FRAME_BLOCKS){
			$level = $frameBlocks >= self::MAX_FRAME_BLOCKS ? 2 : 1;
			$this->applyEffects($level);
		}
		$world->scheduleDelayedBlockUpdate($this->position, 40);
	}

	private function countFrameBlocks() : int{
		$world = $this->position->getWorld();
		$count = 0;
		for($x = -3; $x <= 3; ++$x){
			for($y = -3; $y <= 3; ++$y){
				for($z = -3; $z <= 3; ++$z){
					if(abs($x) !== 3 && abs($y) !== 3 && abs($z) !== 3){
						continue; //only the outer shell matters
					}
					$block = $world->getBlockAt(
						$this->position->getFloorX() + $x,
						$this->position->getFloorY() + $y,
						$this->position->getFloorZ() + $z
					);
					if(self::isFrameBlock($block)){
						$count++;
					}
				}
			}
		}
		return $count;
	}

	private static function isFrameBlock(Block $block) : bool{
		return match($block->getTypeId()){
			BlockTypeIds::PRISMARINE,
			BlockTypeIds::DARK_PRISMARINE,
			BlockTypeIds::PRISMARINE_BRICKS,
			BlockTypeIds::SEA_LANTERN => true,
			default => false
		};
	}

	private function applyEffects(int $level) : void{
		$range = 32 + $level * 16;
		$world = $this->position->getWorld();
		$box = AxisAlignedBB::one()
			->expand($range, $range, $range)
			->offset($this->position->x, $this->position->y, $this->position->z);

		foreach($world->getNearbyEntities($box) as $entity){
			if($entity instanceof Player && $entity->isAlive() && $entity->isUnderwater()){
				$entity->getEffects()->add(new EffectInstance(VanillaEffects::CONDUIT_POWER(), 260, 0, true, true));
				$entity->getEffects()->add(new EffectInstance(VanillaEffects::WATER_BREATHING(), 260, 0, true, true));
			}elseif($entity instanceof Living && $entity->isUnderwater() && !$entity instanceof Player){
				$entity->attack(new \pocketmine\event\entity\EntityDamageEvent($entity, \pocketmine\event\entity\EntityDamageEvent::CAUSE_MAGIC, 2));
			}
		}
	}
}
