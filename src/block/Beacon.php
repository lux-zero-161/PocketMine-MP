<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Beacon as TileBeacon;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function max;

final class Beacon extends Transparent{
	public const EFFECT_DURATION = 200;
	public const REFRESH_INTERVAL = 80;

	public function getLightLevel() : int{
		return 15;
	}

	public function onPostPlace() : void{
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, self::REFRESH_INTERVAL);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player === null || !$this->isPaymentItem($item)){
			return false;
		}
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileBeacon){
			return false;
		}

		$effectId = $this->getEffectIdForItem($item);
		if($effectId === null){
			return false;
		}

		if($tile->getPrimaryEffect() === 0){
			$tile->setPrimaryEffect($effectId);
		}elseif($tile->getSecondaryEffect() === 0){
			$tile->setSecondaryEffect($effectId);
		}else{
			return false;
		}

		$item->pop();
		$player->getInventory()->setItemInHand($item);
		$this->applyEffects($tile);
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, self::REFRESH_INTERVAL);
		return true;
	}

	public function onScheduledUpdate() : void{
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileBeacon){
			$this->applyEffects($tile);
		}
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, self::REFRESH_INTERVAL);
	}

	private function applyEffects(TileBeacon $tile) : void{
		$level = $this->getPyramidLevel();
		if($level <= 0){
			return;
		}

		$range = 10 + $level * 10;
		$world = $this->position->getWorld();
		$box = AxisAlignedBB::one()
			->expand($range, $range, $range)
			->offset($this->position->x, $this->position->y, $this->position->z);

		$primary = EffectIdMap::getInstance()->fromId($tile->getPrimaryEffect());
		$secondary = EffectIdMap::getInstance()->fromId($tile->getSecondaryEffect());
		if($primary === null && $secondary === null){
			return;
		}

		foreach($world->getNearbyEntities($box) as $entity){
			if(!$entity instanceof Player || !$entity->isAlive() || $entity->isSpectator()){
				continue;
			}
			if($primary !== null){
				$entity->getEffects()->add(new EffectInstance($primary, self::EFFECT_DURATION, $level >= 3 ? 1 : 0, true, true));
			}
			if($secondary !== null){
				$entity->getEffects()->add(new EffectInstance($secondary, self::EFFECT_DURATION, $level >= 4 ? 1 : 0, true, true));
			}elseif($level >= 4 && $primary !== null){
				$entity->getEffects()->add(new EffectInstance($primary, self::EFFECT_DURATION, 1, true, true));
			}
		}
	}

	/**
	 * Returns the number of complete pyramid layers below the beacon (0-4).
	 */
	public function getPyramidLevel() : int{
		$world = $this->position->getWorld();
		$level = 0;
		for($layer = 0; $layer < 4; ++$layer){
			$radius = $layer + 1;
			$y = $this->position->getFloorY() - 1 - $layer;
			for($x = -$radius; $x <= $radius; ++$x){
				for($z = -$radius; $z <= $radius; ++$z){
					if(!$this->isPyramidBlock($world->getBlock($this->position->add($x, $y - $this->position->getFloorY(), $z)))){
						return $level;
					}
				}
			}
			$level++;
		}
		return $level;
	}

	private function isPyramidBlock(Block $block) : bool{
		return match($block->getTypeId()){
			BlockTypeIds::IRON,
			BlockTypeIds::GOLD,
			BlockTypeIds::DIAMOND,
			BlockTypeIds::EMERALD,
			BlockTypeIds::NETHERITE => true,
			default => false
		};
	}

	private function isPaymentItem(Item $item) : bool{
		return match($item->getTypeId()){
			ItemTypeIds::IRON_INGOT,
			ItemTypeIds::GOLD_INGOT,
			ItemTypeIds::DIAMOND,
			ItemTypeIds::EMERALD,
			ItemTypeIds::NETHERITE_INGOT => true,
			default => false
		};
	}

	private function getEffectIdForItem(Item $item) : ?int{
		return match($item->getTypeId()){
			ItemTypeIds::IRON_INGOT => EffectIdMap::getInstance()->toId(VanillaEffects::HASTE()),
			ItemTypeIds::GOLD_INGOT => EffectIdMap::getInstance()->toId(VanillaEffects::SPEED()),
			ItemTypeIds::DIAMOND => EffectIdMap::getInstance()->toId(VanillaEffects::RESISTANCE()),
			ItemTypeIds::EMERALD => EffectIdMap::getInstance()->toId(VanillaEffects::JUMP_BOOST()),
			ItemTypeIds::NETHERITE_INGOT => EffectIdMap::getInstance()->toId(VanillaEffects::STRENGTH()),
			default => null
		};
	}
}
