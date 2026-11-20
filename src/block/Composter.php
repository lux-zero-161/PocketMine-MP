<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Apple;
use pocketmine\item\BakedPotato;
use pocketmine\item\Beetroot;
use pocketmine\item\BeetrootSeeds;
use pocketmine\item\Bread;
use pocketmine\item\Carrot;
use pocketmine\item\CocoaBeans;
use pocketmine\item\Cookie;
use pocketmine\item\DriedKelp as DriedKelpItem;
use pocketmine\item\GlowBerries;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\Melon as MelonItem;
use pocketmine\item\MelonSeeds;
use pocketmine\item\PitcherPod;
use pocketmine\item\Potato;
use pocketmine\item\PumpkinPie;
use pocketmine\item\PumpkinSeeds;
use pocketmine\item\SweetBerries;
use pocketmine\item\TorchflowerSeeds;
use pocketmine\item\VanillaItems;
use pocketmine\item\WheatSeeds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\HappyVillagerParticle;
use function mt_rand;

class Composter extends Transparent{
	public const MAX_FILL_LEVEL = 8;

	public const CHANCE_LOW = 30;
	public const CHANCE_LOW_MEDIUM = 50;
	public const CHANCE_MEDIUM = 65;
	public const CHANCE_MEDIUM_HIGH = 85;
	public const CHANCE_ALWAYS = 100;

	protected int $fillLevel = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_FILL_LEVEL, $this->fillLevel);
	}

	public function getFillLevel() : int{ return $this->fillLevel; }

	/** @return $this */
	public function setFillLevel(int $fillLevel) : self{
		if($fillLevel < 0 || $fillLevel > self::MAX_FILL_LEVEL){
			throw new \InvalidArgumentException("Fill level must be in range 0 ... " . self::MAX_FILL_LEVEL);
		}
		$this->fillLevel = $fillLevel;
		return $this;
	}

	/**
	 * Returns the composting chance in percent, or null if the item cannot be composted.
	 */
	public static function getCompostChance(Item $item) : ?int{
		if($item->isNull()){
			return null;
		}

		$blockId = ItemTypeIds::toBlockTypeId($item->getTypeId());
		if($blockId !== null){
			if($item instanceof ItemBlock){
				$block = $item->getBlock();
				if($block instanceof Leaves || $block instanceof Sapling || $block instanceof MangrovePropagule){
					return self::CHANCE_LOW;
				}
				if($blockId === BlockTypeIds::TORCHFLOWER){
					return self::CHANCE_MEDIUM_HIGH;
				}
				if($block instanceof Flower){
					return self::CHANCE_MEDIUM;
				}

			}

			return match($blockId){
				BlockTypeIds::BUSH, BlockTypeIds::CACTUS_FLOWER, BlockTypeIds::FIREFLY_BUSH, BlockTypeIds::GRASS,
				BlockTypeIds::HANGING_ROOTS, BlockTypeIds::KELP, BlockTypeIds::LEAF_LITTER, BlockTypeIds::MANGROVE_ROOTS,
				BlockTypeIds::MOSS_CARPET, BlockTypeIds::PALE_HANGING_MOSS, BlockTypeIds::PALE_MOSS_CARPET,
				BlockTypeIds::PINK_PETALS, BlockTypeIds::RED_SHRUB, BlockTypeIds::SEAGRASS, BlockTypeIds::SHORT_DRY_GRASS,
				BlockTypeIds::TALL_DRY_GRASS, BlockTypeIds::TALL_GRASS, BlockTypeIds::WILDFLOWERS => self::CHANCE_LOW,
				BlockTypeIds::CACTUS, BlockTypeIds::DRIED_KELP, BlockTypeIds::FLOWERING_AZALEA_LEAVES,
				BlockTypeIds::GLOW_LICHEN, BlockTypeIds::MELON, BlockTypeIds::NETHER_SPROUTS, BlockTypeIds::SUGARCANE,
				BlockTypeIds::TWISTING_VINES, BlockTypeIds::VINES, BlockTypeIds::WEEPING_VINES => self::CHANCE_LOW_MEDIUM,
				BlockTypeIds::AZALEA, BlockTypeIds::BIG_DRIPLEAF_HEAD, BlockTypeIds::BIG_DRIPLEAF_STEM,
				BlockTypeIds::BROWN_MUSHROOM, BlockTypeIds::CARVED_PUMPKIN, BlockTypeIds::CRIMSON_FUNGUS,
				BlockTypeIds::CRIMSON_ROOTS, BlockTypeIds::FERN, BlockTypeIds::LARGE_FERN, BlockTypeIds::LILY_PAD,
				BlockTypeIds::MOSS_BLOCK, BlockTypeIds::MUSHROOM_STEM, BlockTypeIds::NETHER_WART,
				BlockTypeIds::PALE_MOSS_BLOCK, BlockTypeIds::PUMPKIN, BlockTypeIds::RED_MUSHROOM, BlockTypeIds::SEA_PICKLE,
				BlockTypeIds::SHELF_MUSHROOM, BlockTypeIds::SHROOMLIGHT, BlockTypeIds::SPORE_BLOSSOM,
				BlockTypeIds::STRAW_BED, BlockTypeIds::WARPED_FUNGUS, BlockTypeIds::WARPED_ROOTS, BlockTypeIds::WITHER_ROSE
					=> self::CHANCE_MEDIUM,
				BlockTypeIds::FLOWERING_AZALEA, BlockTypeIds::HAY_BALE, BlockTypeIds::BROWN_MUSHROOM_BLOCK,
				BlockTypeIds::NETHER_WART_BLOCK, BlockTypeIds::PITCHER_PLANT, BlockTypeIds::RED_MUSHROOM_BLOCK,
				BlockTypeIds::TORCHFLOWER, BlockTypeIds::WARPED_WART_BLOCK => self::CHANCE_MEDIUM_HIGH,
				BlockTypeIds::CAKE => self::CHANCE_ALWAYS,
				default => null
			};
		}

		return match(true){
			$item instanceof BeetrootSeeds, $item instanceof MelonSeeds, $item instanceof PumpkinSeeds,
			$item instanceof TorchflowerSeeds, $item instanceof WheatSeeds, $item instanceof PitcherPod, $item instanceof GlowBerries,
			$item instanceof SweetBerries, $item instanceof DriedKelpItem => self::CHANCE_LOW,
			$item instanceof MelonItem => self::CHANCE_LOW_MEDIUM,
			$item instanceof Apple, $item instanceof Beetroot, $item instanceof Carrot, $item instanceof CocoaBeans,
			$item instanceof Potato, $item->getTypeId() === ItemTypeIds::WHEAT => self::CHANCE_MEDIUM,
			$item instanceof BakedPotato, $item instanceof Bread, $item instanceof Cookie => self::CHANCE_MEDIUM_HIGH,
			$item instanceof PumpkinPie => self::CHANCE_ALWAYS,
			default => null
		};
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($this->fillLevel >= self::MAX_FILL_LEVEL){
			if($item->isNull()){
				$this->fillLevel = 0;
				$this->position->getWorld()->setBlock($this->position, $this);
				$returnedItems[] = VanillaItems::BONE_MEAL();
				return true;
			}
			return true;
		}

		$chance = self::getCompostChance($item);
		if($chance === null){
			return false;
		}

		$world = $this->position->getWorld();
		$item->pop();

		//the first layer is always added regardless of the chance
		if($this->fillLevel === 0 || mt_rand(1, 100) <= $chance){
			$this->fillLevel++;
			if($this->fillLevel === self::MAX_FILL_LEVEL - 1){
				//the last layer takes one second to become ready
				$world->scheduleDelayedBlockUpdate($this->position, 20);
			}
			$world->setBlock($this->position, $this);
		}

		$world->addParticle($this->position->add(0.5, 1, 0.5), new HappyVillagerParticle());
		return true;
	}

	public function onScheduledUpdate() : void{
		if($this->fillLevel === self::MAX_FILL_LEVEL - 1){
			$this->fillLevel = self::MAX_FILL_LEVEL;
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	public function getFuelTime() : int{
		return 300; //1.5 items
	}
}
