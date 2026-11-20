<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\TrialSpawner as TileTrialSpawner;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\Location;
use pocketmine\entity\mob\Husk;
use pocketmine\entity\Zombie;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use function array_rand;
use function count;
use function mt_rand;

class TrialSpawner extends Opaque{
	public const MAX_STATE = 5;
	private const MAX_WAVES = 3;

	protected bool $ominous = false;
	protected int $state = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->bool($this->ominous);
		$w->boundedIntAuto(0, self::MAX_STATE, $this->state);
	}

	public function isOminous() : bool{ return $this->ominous; }

	/** @return $this */
	public function setOminous(bool $ominous) : self{
		$this->ominous = $ominous;
		return $this;
	}

	public function getState() : int{ return $this->state; }

	/** @return $this */
	public function setState(int $state) : self{
		if($state < 0 || $state > self::MAX_STATE){
			throw new \InvalidArgumentException("State must be in range 0 ... " . self::MAX_STATE);
		}
		$this->state = $state;
		return $this;
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if(!$tile instanceof TileTrialSpawner){
			return;
		}

		$players = $world->getNearbyEntities(AxisAlignedBB::one()->expand(8, 8, 8)->offset($this->position->x, $this->position->y, $this->position->z));
		$hasPlayer = false;
		foreach($players as $entity){
			if($entity instanceof Player && $entity->isAlive()){
				$hasPlayer = true;
				break;
			}
		}

		if(!$hasPlayer){
			if($tile->isActive()){
				$tile->setActive(false);
				$tile->clearSpawnedEntities();
				$this->state = 0;
				$world->setBlock($this->position, $this);
			}
			$world->scheduleDelayedBlockUpdate($this->position, 20);
			return;
		}

		if(!$tile->isActive()){
			$tile->setActive(true);
			$tile->setWave(0);
			$this->spawnWave($tile);
			$this->state = 1;
			$world->setBlock($this->position, $this);
			$world->scheduleDelayedBlockUpdate($this->position, 20);
			return;
		}

		$alive = 0;
		foreach($tile->getSpawnedEntities() as $id){
			$entity = $world->getEntity($id);
			if($entity !== null && $entity->isAlive()){
				$alive++;
			}
		}

		if($alive === 0){
			$tile->clearSpawnedEntities();
			$tile->setWave($tile->getWave() + 1);
			if($tile->getWave() >= self::MAX_WAVES){
				$this->ejectLoot();
				$tile->setActive(false);
				$tile->setWave(0);
				$this->state = 0;
			}else{
				$this->spawnWave($tile);
				$this->state = 1;
			}
			$world->setBlock($this->position, $this);
		}

		$world->scheduleDelayedBlockUpdate($this->position, 20);
	}

	private function spawnWave(TileTrialSpawner $tile) : void{
		$world = $this->position->getWorld();
		$spawn = $this->ominous ? fn(Location $location) => new Husk($location) : fn(Location $location) => new Zombie($location);
		$count = 2 + $tile->getWave();
		for($i = 0; $i < $count; ++$i){
			$offsetX = mt_rand(-2, 2);
			$offsetZ = mt_rand(-2, 2);
			$pos = $this->position->add($offsetX + 0.5, 0, $offsetZ + 0.5);
			if($pos->getY() < $world->getMinY() || $pos->getY() >= $world->getMaxY()){
				continue;
			}
			if(!$world->getBlock($pos)->canBeReplaced()){
				continue;
			}
			$entity = $spawn(Location::fromObject($pos, $world, mt_rand(0, 360), 0));
			$entity->spawnToAll();
			$tile->addSpawnedEntity($entity->getId());
		}
	}

	private function ejectLoot() : void{
		$loot = $this->ominous ? [
			[VanillaItems::DIAMOND(), 2, 4],
			[VanillaItems::EMERALD(), 3, 6],
			[VanillaItems::GOLDEN_APPLE(), 1, 2]
		] : [
			[VanillaItems::EMERALD(), 1, 3],
			[VanillaItems::IRON_INGOT(), 2, 5],
			[VanillaItems::DIAMOND(), 1, 2]
		];
		$entry = $loot[array_rand($loot)];
		$item = (clone $entry[0])->setCount(mt_rand($entry[1], $entry[2]));
		$this->position->getWorld()->dropItem($this->position->add(0.5, 0.5, 0.5), $item);
	}
}
