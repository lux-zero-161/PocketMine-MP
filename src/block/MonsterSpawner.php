<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\MonsterSpawner as TileMonsterSpawner;
use pocketmine\block\utils\SupportType;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use function mt_rand;

class MonsterSpawner extends Transparent{

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}

	protected function getXpDropAmount() : int{
		return mt_rand(15, 43);
	}

	public function onPostPlace() : void{
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 20);
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if(!$tile instanceof TileMonsterSpawner){
			$world->scheduleDelayedBlockUpdate($this->position, 20);
			return;
		}

		$nearby = $world->getNearbyEntities(AxisAlignedBB::one()
			->expand($tile->getRequiredPlayerRange(), $tile->getRequiredPlayerRange(), $tile->getRequiredPlayerRange())
			->offset($this->position->x, $this->position->y, $this->position->z));
		$hasPlayer = false;
		$living = 0;
		foreach($nearby as $entity){
			if($entity instanceof Player && $entity->isAlive() && !$entity->isSpectator()){
				$hasPlayer = true;
			}elseif($entity instanceof Living && $entity->isAlive()){
				$living++;
			}
		}

		if(!$hasPlayer || $living >= $tile->getMaxNearbyEntities()){
			$world->scheduleDelayedBlockUpdate($this->position, 20);
			return;
		}

		$delay = $tile->getSpawnDelay() - 1;
		if($delay <= 0){
			$this->spawnEntities($tile);
			$delay = mt_rand($tile->getMinSpawnDelay(), max($tile->getMinSpawnDelay(), $tile->getMaxSpawnDelay()));
		}
		$tile->setSpawnDelay($delay);

		$world->scheduleDelayedBlockUpdate($this->position, 20);
	}

	private function spawnEntities(TileMonsterSpawner $tile) : void{
		$world = $this->position->getWorld();
		$entityTypeId = $tile->getEntityTypeId();
		if($entityTypeId === ":" || $entityTypeId === ""){
			return;
		}

		$range = $tile->getSpawnRange();
		for($i = 0; $i < $tile->getSpawnPerAttempt(); ++$i){
			$x = $this->position->getFloorX() + mt_rand(-$range, $range);
			$z = $this->position->getFloorZ() + mt_rand(-$range, $range);
			$y = $this->position->getFloorY() + mt_rand(-1, 2);
			if($y < $world->getMinY() || $y >= $world->getMaxY()){
				continue;
			}
			$pos = new \pocketmine\math\Vector3($x + 0.5, $y, $z + 0.5);
			if(!$world->getBlock($pos)->canBeReplaced()){
				continue;
			}
			$entity = EntityFactory::getInstance()->createEntity(
				$world,
				$entityTypeId,
				Location::fromObject($pos, $world, mt_rand(0, 359), 0)
			);
			if($entity !== null){
				$entity->spawnToAll();
			}
		}
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}
}
