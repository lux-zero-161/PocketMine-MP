<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\NeverSavedWithChunkEntity;
use pocketmine\item\FishingRod;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\particle\BubbleParticle;
use pocketmine\world\particle\SplashParticle;
use function mt_rand;

class FishingHook extends Entity implements NeverSavedWithChunkEntity{
	/** @var \WeakMap<Player, FishingHook>|null */
	private static ?\WeakMap $hooks = null;

	private ?Player $owner;
	private bool $inWater = false;
	private bool $biting = false;
	private int $biteTimer = 0;

	public function __construct(Location $location, ?CompoundTag $nbt = null, ?Player $owner = null){
		$this->owner = $owner;
		parent::__construct($location, $nbt);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::FISHING_HOOK; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.25, 0.25);
	}

	protected function getInitialDragMultiplier() : float{ return 0.08; }

	protected function getInitialGravity() : float{ return 0.035; }

	public function getName() : string{ return "Fishing Hook"; }

	public static function cast(Player $player) : void{
		$hook = self::getHook($player);
		if($hook !== null){
			$hook->reel($player);
			return;
		}

		$hook = new self(
			Location::fromObject($player->getEyePos(), $player->getWorld(), $player->getLocation()->yaw, $player->getLocation()->pitch),
			null,
			$player
		);
		$hook->setMotion($player->getDirectionVector()->multiply(1.15));
		$hook->spawnToAll();

		self::$hooks ??= new \WeakMap();
		self::$hooks[$player] = $hook;
	}

	public static function getHook(Player $player) : ?FishingHook{
		if(self::$hooks === null || !isset(self::$hooks[$player])){
			return null;
		}
		$hook = self::$hooks[$player];
		return $hook->isClosed() ? null : $hook;
	}

	public function reel(Player $player) : void{
		$caught = $this->biting && $this->inWater;
		$this->removeFor($player);

		if(!$caught){
			return;
		}

		$loot = $this->pickLoot();
		$player->getWorld()->dropItem($player->getPosition()->add(0, 1, 0), $loot);

		$rod = $player->getInventory()->getItemInHand();
		if($rod instanceof FishingRod){
			$rod->applyDamage(1);
			$player->getInventory()->setItemInHand($rod);
		}
	}

	public function onUpdate(int $currentTick) : bool{
		$owner = $this->owner;
		if($owner === null || !$owner->isAlive() || $owner->isClosed() || $this->location->distanceSquared($owner->getLocation()) > 1600){
			$this->removeFor($owner);
			return false;
		}

		$block = $this->getWorld()->getBlock($this->location);
		if($block instanceof Water){
			if(!$this->inWater){
				$this->inWater = true;
				$this->biteTimer = mt_rand(100, 600);
				$this->getWorld()->addParticle($this->location, new SplashParticle());
			}
			$motion = $this->getMotion();
			$this->setMotion(new Vector3($motion->x * 0.85, $motion->y * 0.5 + 0.02, $motion->z * 0.85));
			if($currentTick % 20 === 0){
				$this->getWorld()->addParticle($this->location, new BubbleParticle());
			}
		}

		if($this->inWater && !$this->biting){
			if(--$this->biteTimer <= 0){
				$this->biting = true;
				$this->getWorld()->addParticle($this->location, new SplashParticle());
			}
		}

		return parent::onUpdate($currentTick);
	}

	protected function destroyCycles() : void{
		parent::destroyCycles();
		if(self::$hooks !== null && $this->owner !== null && (self::$hooks[$this->owner] ?? null) === $this){
			unset(self::$hooks[$this->owner]);
		}
		$this->owner = null;
	}

	private function removeFor(?Player $player) : void{
		if($player !== null && self::$hooks !== null && (self::$hooks[$player] ?? null) === $this){
			unset(self::$hooks[$player]);
		}
		$this->flagForDespawn();
	}

	private function pickLoot() : Item{
		$roll = mt_rand(1, 100);
		if($roll <= 85){
			return match(true){
				mt_rand(1, 100) <= 60 => VanillaItems::RAW_FISH(),
				mt_rand(1, 100) <= 25 => VanillaItems::RAW_SALMON(),
				mt_rand(1, 100) <= 2 => VanillaItems::CLOWNFISH(),
				default => VanillaItems::PUFFERFISH()
			};
		}
		if($roll <= 95){
			return match(mt_rand(1, 5)){
				1 => VanillaItems::LEATHER(),
				2 => VanillaItems::BONE(),
				3 => VanillaItems::STRING(),
				4 => VanillaItems::BOWL(),
				default => VanillaItems::STICK()
			};
		}
		return match(mt_rand(1, 5)){
			1 => VanillaItems::BOW(),
			2 => VanillaItems::FISHING_ROD(),
			3 => VanillaItems::NAME_TAG(),
			4 => VanillaItems::NAUTILUS_SHELL(),
			default => VanillaItems::SADDLE()
		};
	}
}
