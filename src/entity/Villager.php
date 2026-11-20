<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\entity;

use pocketmine\entity\utils\Trade;
use pocketmine\form\SimpleForm;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function mt_rand;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class Villager extends Living implements Ageable{
	public const PROFESSION_FARMER = 0;
	public const PROFESSION_LIBRARIAN = 1;
	public const PROFESSION_PRIEST = 2;
	public const PROFESSION_BLACKSMITH = 3;
	public const PROFESSION_BUTCHER = 4;

	private const TAG_PROFESSION = "Profession"; //TAG_Int

	public static function getNetworkTypeId() : string{ return EntityIds::VILLAGER; }

	private bool $baby = false;
	private int $profession = self::PROFESSION_FARMER;
	/** @var Trade[] */
	private array $trades = [];

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.9, 0.6); //TODO: eye height??
	}

	public function getName() : string{
		return "Villager";
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		/** @var int $profession */
		$profession = $nbt->getInt(self::TAG_PROFESSION, self::PROFESSION_FARMER);

		if($profession > 4 || $profession < 0){
			$profession = self::PROFESSION_FARMER;
		}

		$this->setProfession($profession);
	}

	/**
	 * @return Trade[]
	 */
	public function getTrades() : array{ return $this->trades; }

	public function addTrade(Trade $trade) : void{ $this->trades[] = $trade; }

	private function rebuildTrades() : void{
		$this->trades = [];
		switch($this->profession){
			case self::PROFESSION_FARMER:
				$this->trades[] = new Trade(VanillaItems::WHEAT()->setCount(20), VanillaItems::EMERALD());
				$this->trades[] = new Trade(VanillaItems::EMERALD(), VanillaItems::BREAD()->setCount(6));
				break;
			case self::PROFESSION_LIBRARIAN:
				$this->trades[] = new Trade(VanillaItems::PAPER()->setCount(24), VanillaItems::EMERALD());
				$this->trades[] = new Trade(VanillaItems::EMERALD(), VanillaBlocks::BOOKSHELF()->asItem());
				break;
			case self::PROFESSION_PRIEST:
				$this->trades[] = new Trade(VanillaItems::ROTTEN_FLESH()->setCount(32), VanillaItems::EMERALD());
				$this->trades[] = new Trade(VanillaItems::EMERALD(), VanillaBlocks::REDSTONE()->asItem()->setCount(4));
				break;
			case self::PROFESSION_BLACKSMITH:
				$this->trades[] = new Trade(VanillaItems::COAL()->setCount(15), VanillaItems::EMERALD());
				$this->trades[] = new Trade(VanillaItems::EMERALD(), VanillaItems::IRON_INGOT()->setCount(3));
				break;
			case self::PROFESSION_BUTCHER:
				$this->trades[] = new Trade(VanillaItems::RAW_BEEF()->setCount(10), VanillaItems::EMERALD());
				$this->trades[] = new Trade(VanillaItems::EMERALD(), VanillaItems::COOKED_PORKCHOP()->setCount(3));
				break;
		}
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		$item = $player->getInventory()->getItemInHand();
		if(!$item->isNull()){
			foreach($this->trades as $trade){
				$input = $trade->getInput();
				if($item->getTypeId() === $input->getTypeId() && $this->tryTrade($player, $trade)){
					return true;
				}
			}
		}

		$form = new SimpleForm(
			$this->getProfessionName() . " Villager",
			"Wähle einen Handel:",
			function(Player $player, ?int $index) : void{
				if($index === null || !isset($this->trades[$index])){
					return;
				}
				if(!$this->tryTrade($player, $this->trades[$index])){
					$player->sendMessage(TextFormat::RED . "Du hast nicht genügend Items für diesen Handel.");
				}
			}
		);
		foreach($this->trades as $trade){
			$input = $trade->getInput();
			$output = $trade->getOutput();
			$form->addButton($input->getCount() . "x " . $input->getName() . " → " . $output->getCount() . "x " . $output->getName());
		}
		$player->sendForm($form);
		return true;
	}

	private function tryTrade(Player $player, Trade $trade) : bool{
		$input = $trade->getInput();
		if(!$player->getInventory()->contains($input)){
			return false;
		}
		$player->getInventory()->removeItem($input);
		$player->getInventory()->addItem($trade->getOutput());
		return true;
	}

	private function getProfessionName() : string{
		return match($this->profession){
			self::PROFESSION_LIBRARIAN => "Librarian",
			self::PROFESSION_PRIEST => "Cleric",
			self::PROFESSION_BLACKSMITH => "Blacksmith",
			self::PROFESSION_BUTCHER => "Butcher",
			default => "Farmer"
		};
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->isAlive() && $currentTick % 60 === 0 && mt_rand(0, 2) === 0 && $this->isOnGround()){
			$this->setMotion(new Vector3(mt_rand(-10, 10) / 100, 0.2, mt_rand(-10, 10) / 100));
		}
		return parent::onUpdate($currentTick);
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setInt(self::TAG_PROFESSION, $this->getProfession());

		return $nbt;
	}

	/**
	 * Sets the villager profession
	 */
	public function setProfession(int $profession) : void{
		$this->profession = $profession; //TODO: validation
		$this->networkPropertiesDirty = true;
		$this->rebuildTrades();
	}

	public function getProfession() : int{
		return $this->profession;
	}

	public function isBaby() : bool{
		return $this->baby;
	}

	public function getPickedItem() : ?Item{
		return VanillaItems::VILLAGER_SPAWN_EGG();
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);
		$properties->setGenericFlag(EntityMetadataFlags::BABY, $this->baby);

		$properties->setInt(EntityMetadataProperties::VARIANT, $this->profession);
	}
}
