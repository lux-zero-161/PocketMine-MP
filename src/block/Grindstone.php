<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_|  | | | | | |  __/_____| |  | |  __/
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

namespace pocketmine\block;

use pocketmine\block\utils\GrindstoneAttachment;
use pocketmine\form\SimpleForm;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\data\runtime\RuntimeDataDescriber;

class Grindstone extends Transparent{
	private const REPAIR_BONUS = 0.12;
	public const MAX_DIRECTION = 3;

	protected GrindstoneAttachment $attachment = GrindstoneAttachment::STANDING;
	protected int $direction = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->attachment);
		$w->boundedIntAuto(0, self::MAX_DIRECTION, $this->direction);
	}

	public function getAttachment() : GrindstoneAttachment{ return $this->attachment; }

	/** @return $this */
	public function setAttachment(GrindstoneAttachment $attachment) : self{
		$this->attachment = $attachment;
		return $this;
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

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player === null){
			return false;
		}
		$held = $player->getInventory()->getItemInHand();
		if($held->isNull()){
			$player->sendMessage(TextFormat::GRAY . "Halte ein Item, um es am Schleifstein zu bearbeiten.");
			return true;
		}

		$form = new SimpleForm(
			"Schleifstein",
			"Wähle eine Aktion für " . $held->getName() . ":",
			function(Player $player, ?int $index) use ($held) : void{
				if($index !== 0){
					return;
				}
				$result = clone $held;
				$result->removeEnchantments();

				$offHand = $player->getOffHandInventory()->getItem(0);
				if($result instanceof Durable && $result->getDamage() > 0 && $offHand instanceof Durable && $offHand->getTypeId() === $result->getTypeId()){
					$damage = $result->getDamage();
					$damage -= $offHand->getMaxDurability() - $offHand->getDamage();
					$damage -= (int) ($result->getMaxDurability() * self::REPAIR_BONUS);
					$result->setDamage(max(0, $damage));
					$player->getOffHandInventory()->clear(0);
				}

				$handItem = $player->getInventory()->getItemInHand();
				$handItem->pop();
				if($handItem->isNull()){
					$player->getInventory()->setItemInHand($result);
				}else{
					$player->getInventory()->setItemInHand($handItem);
					$player->getInventory()->addItem($result);
				}
				$player->sendMessage(TextFormat::GREEN . "Item geschliffen.");
			}
		);
		$form->addButton("Verzauberungen entfernen / reparieren");
		$form->addButton("Abbrechen");
		$player->sendForm($form);
		return true;
	}
}
