<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\CopperOxidation;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\CopperWaxApplySound;
use pocketmine\world\sound\CopperWaxRemoveSound;
use pocketmine\world\sound\ScrapeSound;

class CopperGolemStatue extends Opaque implements HorizontalFacing{
	use HorizontalFacingTrait;

	/**
	 * Maps each copper golem statue variant to its waxed state and oxidation level.
	 */
	private const VARIANTS = [
		BlockTypeIds::COPPER_GOLEM_STATUE => [false, CopperOxidation::NONE],
		BlockTypeIds::EXPOSED_COPPER_GOLEM_STATUE => [false, CopperOxidation::EXPOSED],
		BlockTypeIds::WEATHERED_COPPER_GOLEM_STATUE => [false, CopperOxidation::WEATHERED],
		BlockTypeIds::OXIDIZED_COPPER_GOLEM_STATUE => [false, CopperOxidation::OXIDIZED],
		BlockTypeIds::WAXED_COPPER_GOLEM_STATUE => [true, CopperOxidation::NONE],
		BlockTypeIds::WAXED_EXPOSED_COPPER_GOLEM_STATUE => [true, CopperOxidation::EXPOSED],
		BlockTypeIds::WAXED_WEATHERED_COPPER_GOLEM_STATUE => [true, CopperOxidation::WEATHERED],
		BlockTypeIds::WAXED_OXIDIZED_COPPER_GOLEM_STATUE => [true, CopperOxidation::OXIDIZED]
	];

	private function getVariant(bool $waxed, CopperOxidation $oxidation) : CopperGolemStatue{
		$block = match(true){
			$waxed => match($oxidation){
				CopperOxidation::NONE => VanillaBlocks::WAXED_COPPER_GOLEM_STATUE(),
				CopperOxidation::EXPOSED => VanillaBlocks::WAXED_EXPOSED_COPPER_GOLEM_STATUE(),
				CopperOxidation::WEATHERED => VanillaBlocks::WAXED_WEATHERED_COPPER_GOLEM_STATUE(),
				CopperOxidation::OXIDIZED => VanillaBlocks::WAXED_OXIDIZED_COPPER_GOLEM_STATUE()
			},
			default => match($oxidation){
				CopperOxidation::NONE => VanillaBlocks::COPPER_GOLEM_STATUE(),
				CopperOxidation::EXPOSED => VanillaBlocks::EXPOSED_COPPER_GOLEM_STATUE(),
				CopperOxidation::WEATHERED => VanillaBlocks::WEATHERED_COPPER_GOLEM_STATUE(),
				CopperOxidation::OXIDIZED => VanillaBlocks::OXIDIZED_COPPER_GOLEM_STATUE()
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

		return false;
	}
}
