<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use function array_pop;
use function ceil;
use function count;
use function intdiv;
use function max;
use function min;

class Bundle extends Item{
	public const TAG_ITEMS = "Items";
	public const CAPACITY = 64;

	public function getMaxStackSize() : int{
		return 1;
	}

	/**
	 * @return Item[]
	 * @phpstan-return list<Item>
	 */
	public function getContents() : array{
		$list = $this->getNamedTag()->getListTag(self::TAG_ITEMS, CompoundTag::class);
		if($list === null){
			return [];
		}
		$result = [];
		foreach($list->getValue() as $tag){
			$item = Item::safeNbtDeserialize($tag, "Bundle item");
			if(!$item->isNull()){
				$result[] = $item;
			}
		}
		return $result;
	}

	/**
	 * @param Item[] $items
	 * @phpstan-param list<Item> $items
	 */
	public function setContents(array $items) : void{
		$tags = [];
		foreach($items as $item){
			if(!$item->isNull()){
				$tags[] = $item->nbtSerialize();
			}
		}
		$tag = $this->getNamedTag();
		$tag->setTag(self::TAG_ITEMS, new ListTag($tags, NBT::TAG_Compound));
		$this->setNamedTag($tag);
	}

	public function getUsedCapacity() : int{
		$used = 0;
		foreach($this->getContents() as $item){
			$used += self::getItemWeight($item);
		}
		return $used;
	}

	public function canAddItem(Item $item) : bool{
		return $this->getUsedCapacity() + self::getItemWeight($item) <= self::CAPACITY;
	}

	/**
	 * Adds as much of the given item as possible and returns the leftover.
	 */
	public function addItem(Item $item) : ?Item{
		$contents = $this->getContents();
		$remaining = clone $item;
		foreach($contents as $index => $existing){
			if($existing->equals($remaining)){
				$space = $existing->getMaxStackSize() - $existing->getCount();
				$amount = min($space, $remaining->getCount());
				if($amount > 0){
					$contents[$index] = $existing->setCount($existing->getCount() + $amount);
					$remaining->setCount($remaining->getCount() - $amount);
					if($remaining->isNull()){
						$this->setContents($contents);
						return null;
					}
				}
			}
		}

		$weightPerItem = intdiv(64, max(1, $remaining->getMaxStackSize()));
		$amount = min($remaining->getCount(), intdiv(self::CAPACITY - $this->getUsedCapacity(), $weightPerItem));
		if($amount > 0){
			$contents[] = (clone $remaining)->setCount($amount);
			$this->setContents($contents);
			$remaining->setCount($remaining->getCount() - $amount);
		}
		return $remaining->isNull() ? null : $remaining;
	}

	/**
	 * Removes and returns the last item stack in the bundle.
	 */
	public function popItem() : ?Item{
		$contents = $this->getContents();
		if(count($contents) === 0){
			return null;
		}
		$item = clone $contents[count($contents) - 1];
		array_pop($contents);
		$this->setContents($contents);
		return $item;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		$item = $this->popItem();
		if($item === null){
			return ItemUseResult::NONE;
		}
		$returnedItems[] = $item;
		return ItemUseResult::SUCCESS;
	}

	private static function getItemWeight(Item $item) : int{
		return $item->getCount() * (int) max(1, ceil(64 / $item->getMaxStackSize()));
	}
}
