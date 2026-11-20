<?php

declare(strict_types=1);

namespace pocketmine\world;

use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\entity\ai\PathFinder;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\network\mcpe\protocol\types\BossBarOverlay;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function cos;
use function count;
use function max;
use function min;
use function mt_rand;
use function sin;
use function spl_object_id;

/**
 * Simplified raid encounter: spawns configured waves of illagers around a centre position, tracks them with a
 * boss bar and rewards the nearby players when all waves are cleared.
 */
final class Raid{
	private const SPAWN_RADIUS = 8;
	private const PLAYER_RANGE = 64;

	/** @var int[] */
	private array $entityIds = [];
	private int $wave = 0;
	private bool $finished = false;

	public function __construct(
		private World $world,
		private Position $center,
		private int $maxWaves = 3
	){}

	public static function start(Player $player, int $waves = 3) : self{
		$raid = new self($player->getWorld(), $player->getPosition(), max(1, min(10, $waves)));
		$raid->world->addRaid($raid);
		$raid->spawnWave();
		$raid->broadcastBossBar();
		return $raid;
	}

	public function tick() : void{
		if($this->finished){
			return;
		}
		if(!$this->world->isLoaded() || !$this->center->isValid()){
			$this->finish(false);
			return;
		}

		$players = $this->getNearbyPlayers();
		if(count($players) === 0){
			$this->finish(false);
			return;
		}

		$alive = 0;
		foreach($this->entityIds as $id){
			$entity = $this->world->getEntity($id);
			if($entity !== null && $entity->isAlive()){
				$alive++;
			}
		}

		if($alive === 0){
			if($this->wave >= $this->maxWaves){
				$this->finish(true);
				return;
			}
			$this->spawnWave();
		}

		$this->broadcastBossBar();
	}

	private function spawnWave() : void{
		++$this->wave;
		$this->entityIds = [];

		$types = match($this->wave){
			1 => ["minecraft:pillager", "minecraft:pillager", "minecraft:vindicator"],
			2 => ["minecraft:pillager", "minecraft:vindicator", "minecraft:evoker"],
			default => ["minecraft:ravager", "minecraft:pillager", "minecraft:vindicator", "minecraft:evoker"]
		};

		foreach($types as $index => $type){
			$position = $this->findSpawnPosition($index);
			$entity = EntityFactory::getInstance()->createEntity(
				$this->world,
				$type,
				Location::fromObject($position, $this->world, mt_rand(0, 359), 0)
			);
			if($entity !== null){
				$entity->setNameTag("Raid " . TextFormat::AQUA . "Welle " . $this->wave);
				$entity->setNameTagAlwaysVisible(false);
				$entity->spawnToAll();
				$this->entityIds[] = $entity->getId();
			}
		}
	}

	private function findSpawnPosition(int $index) : Vector3{
		$angle = ($index / 4) * M_PI * 2 + mt_rand(0, 10) / 10;
		$x = $this->center->getFloorX() + (int) (cos($angle) * self::SPAWN_RADIUS);
		$z = $this->center->getFloorZ() + (int) (sin($angle) * self::SPAWN_RADIUS);
		for($attempt = 0; $attempt < 16; ++$attempt){
			$y = $this->center->getFloorY() + mt_rand(-2, 3);
			if(PathFinder::canStand($this->world, $x, $y, $z)){
				return new Vector3($x + 0.5, $y, $z + 0.5);
			}
			$x += mt_rand(-2, 2);
			$z += mt_rand(-2, 2);
		}
		return $this->center->asVector3();
	}

	/**
	 * @return Player[]
	 */
	private function getNearbyPlayers() : array{
		$box = \pocketmine\math\AxisAlignedBB::one()
			->expand(self::PLAYER_RANGE, self::PLAYER_RANGE, self::PLAYER_RANGE)
			->offset($this->center->x, $this->center->y, $this->center->z);
		$result = [];
		foreach($this->world->getNearbyEntities($box) as $entity){
			if($entity instanceof Player && $entity->isAlive() && !$entity->isSpectator()){
				$result[] = $entity;
			}
		}
		return $result;
	}

	private function broadcastBossBar() : void{
		$players = $this->getNearbyPlayers();
		if(count($players) === 0){
			return;
		}
		$alive = 0;
		foreach($this->entityIds as $id){
			$entity = $this->world->getEntity($id);
			if($entity !== null && $entity->isAlive()){
				$alive++;
			}
		}
		$total = max(1, count($this->entityIds));
		$percent = min(1.0, $alive / $total);
		$title = "Raid – Welle " . $this->wave . "/" . $this->maxWaves;

		foreach($players as $player){
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::show(
				$this->getBossId(),
				$title,
				$percent,
				false,
				BossBarColor::RED,
				BossBarOverlay::PROGRESS
			));
		}
	}

	private function finish(bool $success) : void{
		if($this->finished){
			return;
		}
		$this->finished = true;
		$this->world->removeRaid($this);

		foreach($this->getNearbyPlayers() as $player){
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::hide($this->getBossId()));
			$player->sendMessage($success
				? TextFormat::GREEN . "Der Raid wurde erfolgreich abgewehrt!"
				: TextFormat::GRAY . "Der Raid wurde beendet."
			);
		}

		if($success){
			for($i = 0; $i < 8; ++$i){
				$this->world->dropItem($this->center->add(0.5, 0.5, 0.5), \pocketmine\item\VanillaItems::EMERALD());
			}
		}
	}

	private function getBossId() : int{
		return 0x40000000 | (spl_object_id($this) & 0x3fffffff);
	}
}
