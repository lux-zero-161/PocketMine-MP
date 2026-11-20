<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExtinguishEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class PowderSnow extends Transparent{
	private const FREEZE_START_TICKS = 140;
	private const FREEZE_DAMAGE_INTERVAL = 40;

	/**
	 * Tracks how long each entity has been inside powder snow.
	 *
	 * @var \WeakMap<Entity, array{int, int}>|null
	 */
	private static ?\WeakMap $freezeState = null;

	protected function recalculateCollisionBoxes() : array{
		//entities sink slightly into the block
		return [AxisAlignedBB::one()->trim(Facing::UP, 1 / 16)];
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function onEntityInside(Entity $entity) : bool{
		if(!$entity instanceof Living){
			return true;
		}

		$boots = $entity->getArmorInventory()->getBoots();
		if($boots->getTypeId() === ItemTypeIds::LEATHER_BOOTS){
			return true;
		}

		$motion = $entity->getMotion();
		$entity->setMotion(new Vector3($motion->x * 0.4, $motion->y, $motion->z * 0.4));
		$entity->resetFallDistance();
		if($entity->isOnFire()){
			$entity->extinguish(EntityExtinguishEvent::CAUSE_WATER);
		}

		$world = $entity->getWorld();
		$now = $world->getServer()->getTick();
		$map = self::$freezeState ??= new \WeakMap();
		$state = $map[$entity] ?? [0, $now];
		if($now - $state[1] > 1){
			//the entity left the powder snow since the last check
			$state = [0, $now];
		}
		$state[0]++;
		$state[1] = $now;
		$map[$entity] = $state;

		if($state[0] >= self::FREEZE_START_TICKS && ($state[0] - self::FREEZE_START_TICKS) % self::FREEZE_DAMAGE_INTERVAL === 0){
			$entity->attack(new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_CONTACT, 1));
		}

		return true;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($item->getTypeId() === ItemTypeIds::BUCKET){
			$item->pop();
			$returnedItems[] = VanillaItems::POWDER_SNOW_BUCKET();
			$this->position->getWorld()->setBlock($this->position, VanillaBlocks::AIR());
			return true;
		}

		return false;
	}
}
