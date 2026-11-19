<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\CopperOxidation;
use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\CopperWaxApplySound;
use pocketmine\world\sound\CopperWaxRemoveSound;
use pocketmine\world\sound\ScrapeSound;

class CopperChest extends Chest{

	/**
	 * Maps each copper chest variant to its waxed state and oxidation level.
	 */
	private const VARIANTS = [
		BlockTypeIds::COPPER_CHEST => [false, CopperOxidation::NONE],
		BlockTypeIds::EXPOSED_COPPER_CHEST => [false, CopperOxidation::EXPOSED],
		BlockTypeIds::WEATHERED_COPPER_CHEST => [false, CopperOxidation::WEATHERED],
		BlockTypeIds::OXIDIZED_COPPER_CHEST => [false, CopperOxidation::OXIDIZED],
		BlockTypeIds::WAXED_COPPER_CHEST => [true, CopperOxidation::NONE],
		BlockTypeIds::WAXED_EXPOSED_COPPER_CHEST => [true, CopperOxidation::EXPOSED],
		BlockTypeIds::WAXED_WEATHERED_COPPER_CHEST => [true, CopperOxidation::WEATHERED],
		BlockTypeIds::WAXED_OXIDIZED_COPPER_CHEST => [true, CopperOxidation::OXIDIZED]
	];

	private function getVariant(bool $waxed, CopperOxidation $oxidation) : CopperChest{
		$block = match(true){
			$waxed => match($oxidation){
				CopperOxidation::NONE => VanillaBlocks::WAXED_COPPER_CHEST(),
				CopperOxidation::EXPOSED => VanillaBlocks::WAXED_EXPOSED_COPPER_CHEST(),
				CopperOxidation::WEATHERED => VanillaBlocks::WAXED_WEATHERED_COPPER_CHEST(),
				CopperOxidation::OXIDIZED => VanillaBlocks::WAXED_OXIDIZED_COPPER_CHEST()
			},
			default => match($oxidation){
				CopperOxidation::NONE => VanillaBlocks::COPPER_CHEST(),
				CopperOxidation::EXPOSED => VanillaBlocks::EXPOSED_COPPER_CHEST(),
				CopperOxidation::WEATHERED => VanillaBlocks::WEATHERED_COPPER_CHEST(),
				CopperOxidation::OXIDIZED => VanillaBlocks::OXIDIZED_COPPER_CHEST()
			}
		};
		return $block->setFacing($this->facing);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		[$waxed, $oxidation] = self::VARIANTS[$this->getTypeId()] ?? [false, CopperOxidation::NONE];

		if(!$waxed && $item->getTypeId() === ItemTypeIds::HONEYCOMB){
			$this->position->getWorld()->setBlock($this->position, $this->getVariant(true, $oxidation));
			$this->position->getWorld()->addSound($this->position, new CopperWaxApplySound());
			$item->pop();
			return true;
		}

		if($item instanceof Axe){
			if($waxed){
				$this->position->getWorld()->setBlock($this->position, $this->getVariant(false, $oxidation));
				$this->position->getWorld()->addSound($this->position, new CopperWaxRemoveSound());
				$item->applyDamage(1);
				return true;
			}

			$previous = $oxidation->getPrevious();
			if($previous !== null){
				$this->position->getWorld()->setBlock($this->position, $this->getVariant(false, $previous));
				$this->position->getWorld()->addSound($this->position, new ScrapeSound());
				$item->applyDamage(1);
				return true;
			}
		}

		return parent::onInteract($item, $face, $clickVector, $player, $returnedItems);
	}
}
