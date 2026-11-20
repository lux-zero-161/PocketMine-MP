<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Vault as TileVault;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\VaultState;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function array_rand;
use function mt_rand;

class Vault extends Opaque implements HorizontalFacing{
	use HorizontalFacingTrait;

	protected bool $ominous = false;
	protected VaultState $state = VaultState::INACTIVE;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->horizontalFacing($this->facing);
		$w->bool($this->ominous);
		$w->enum($this->state);
	}

	public function isOminous() : bool{ return $this->ominous; }

	/** @return $this */
	public function setOminous(bool $ominous) : self{
		$this->ominous = $ominous;
		return $this;
	}

	public function getState() : VaultState{ return $this->state; }

	/** @return $this */
	public function setState(VaultState $state) : self{
		$this->state = $state;
		return $this;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player === null || $this->state !== VaultState::INACTIVE){
			return true;
		}

		$expectedKey = $this->ominous ? ItemTypeIds::OMINOUS_TRIAL_KEY : ItemTypeIds::TRIAL_KEY;
		if($item->getTypeId() !== $expectedKey){
			return true;
		}

		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileVault){
			$rawUuid = $player->getUniqueId()->getBytes();
			if($tile->hasPlayerUsed($rawUuid)){
				return true;
			}
			$tile->markPlayerUsed($rawUuid);
		}

		$item->pop();
		$this->state = VaultState::ACTIVE;
		$world = $this->position->getWorld();
		$world->setBlock($this->position, $this);
		$world->scheduleDelayedBlockUpdate($this->position, 20);
		return true;
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		match($this->state){
			VaultState::ACTIVE => $this->state = VaultState::UNLOCKING,
			VaultState::UNLOCKING => $this->state = VaultState::EJECTING,
			VaultState::EJECTING => $this->state = VaultState::INACTIVE,
			default => null
		};

		if($this->state === VaultState::EJECTING){
			$this->ejectLoot();
		}

		$world->setBlock($this->position, $this);
		if($this->state !== VaultState::INACTIVE){
			$world->scheduleDelayedBlockUpdate($this->position, 10);
		}
	}

	private function ejectLoot() : void{
		$loot = $this->ominous ? [
			[VanillaItems::DIAMOND(), 2, 5],
			[VanillaItems::EMERALD(), 3, 7],
			[VanillaItems::GOLDEN_APPLE(), 1, 2],
			[VanillaItems::ENCHANTED_GOLDEN_APPLE(), 1, 1]
		] : [
			[VanillaItems::EMERALD(), 1, 4],
			[VanillaItems::IRON_INGOT(), 1, 4],
			[VanillaItems::DIAMOND(), 1, 2],
			[VanillaItems::GOLDEN_APPLE(), 1, 1],
			[VanillaItems::BREAD(), 1, 3]
		];

		$entry = $loot[array_rand($loot)];
		$item = (clone $entry[0])->setCount(mt_rand($entry[1], $entry[2]));
		$this->position->getWorld()->dropItem($this->getSide($this->facing)->getPosition()->add(0.5, 0.5, 0.5), $item);
	}
}
