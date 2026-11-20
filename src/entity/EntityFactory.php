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

use DaveRandom\CallbackValidator\CallbackType;
use DaveRandom\CallbackValidator\ParameterType;
use DaveRandom\CallbackValidator\ReturnType;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper as Helper;
use pocketmine\entity\object\AreaEffectCloud;
use pocketmine\entity\object\ArmorStand;
use pocketmine\entity\object\EndCrystal;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\FallingBlock;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\object\Painting;
use pocketmine\entity\object\PaintingMotive;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\entity\projectile\ExperienceBottle;
use pocketmine\entity\projectile\IceBomb;
use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\entity\projectile\Trident;
use pocketmine\entity\projectile\WindCharge;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\NbtException;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use function count;
use function reset;

/**
 * This class manages the creation of entities loaded from disk.
 * You need to register your entity into this factory if you want to load/save your entity on disk (saving with chunks).
 */
final class EntityFactory{
	use SingletonTrait;

	public const TAG_IDENTIFIER = "identifier"; //TAG_String
	public const TAG_LEGACY_ID = "id"; //TAG_Int

	/**
	 * @var \Closure[] save ID => creator function
	 * @phpstan-var array<int|string, \Closure(World, CompoundTag) : Entity>
	 */
	private array $creationFuncs = [];
	/**
	 * @var string[]
	 * @phpstan-var array<class-string<Entity>, string>
	 */
	private array $saveNames = [];

	public function __construct(){
		//define legacy save IDs first - use them for saving for maximum compatibility with Minecraft PC
		//TODO: index them by version to allow proper multi-save compatibility

		$this->register(AreaEffectCloud::class, function(World $world, CompoundTag $nbt) : AreaEffectCloud{
			return new AreaEffectCloud(Helper::parseLocation($nbt, $world), $nbt);
		}, ['AreaEffectCloud', 'minecraft:area_effect_cloud']);

		$this->register(Arrow::class, function(World $world, CompoundTag $nbt) : Arrow{
			return new Arrow(Helper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
		}, ['Arrow', 'minecraft:arrow']);

		$this->register(Egg::class, function(World $world, CompoundTag $nbt) : Egg{
			return new Egg(Helper::parseLocation($nbt, $world), null, $nbt);
		}, ['Egg', 'minecraft:egg']);

		$this->register(EndCrystal::class, function(World $world, CompoundTag $nbt) : EndCrystal{
			return new EndCrystal(Helper::parseLocation($nbt, $world), $nbt);
		}, ['EnderCrystal', 'minecraft:ender_crystal']);

		$this->register(EnderPearl::class, function(World $world, CompoundTag $nbt) : EnderPearl{
			return new EnderPearl(Helper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownEnderpearl', 'minecraft:ender_pearl']);

		$this->register(ExperienceBottle::class, function(World $world, CompoundTag $nbt) : ExperienceBottle{
			return new ExperienceBottle(Helper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownExpBottle', 'minecraft:xp_bottle']);

		$this->register(ExperienceOrb::class, function(World $world, CompoundTag $nbt) : ExperienceOrb{
			$value = 1;
			if(($valuePcTag = $nbt->getTag(ExperienceOrb::TAG_VALUE_PC)) instanceof ShortTag){ //PC
				$value = $valuePcTag->getValue();
			}elseif(($valuePeTag = $nbt->getTag(ExperienceOrb::TAG_VALUE_PE)) instanceof IntTag){ //PE save format
				$value = $valuePeTag->getValue();
			}

			return new ExperienceOrb(Helper::parseLocation($nbt, $world), $value, $nbt);
		}, ['XPOrb', 'minecraft:xp_orb']);

		$this->register(FallingBlock::class, function(World $world, CompoundTag $nbt) : FallingBlock{
			return new FallingBlock(Helper::parseLocation($nbt, $world), FallingBlock::parseBlockNBT(RuntimeBlockStateRegistry::getInstance(), $nbt), $nbt);
		}, ['FallingSand', 'minecraft:falling_block']);

		$this->register(\pocketmine\entity\object\ChestMinecart::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\object\ChestMinecart{
			return new \pocketmine\entity\object\ChestMinecart(Helper::parseLocation($nbt, $world), $nbt);
		}, ['ChestMinecart', 'minecraft:chest_minecart']);

		$this->register(\pocketmine\entity\object\HopperMinecart::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\object\HopperMinecart{
			return new \pocketmine\entity\object\HopperMinecart(Helper::parseLocation($nbt, $world), $nbt);
		}, ['HopperMinecart', 'minecraft:hopper_minecart']);

		$this->register(\pocketmine\entity\object\TNTMinecart::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\object\TNTMinecart{
			return new \pocketmine\entity\object\TNTMinecart(Helper::parseLocation($nbt, $world), $nbt);
		}, ['TNTMinecart', 'minecraft:tnt_minecart']);

		$this->register(\pocketmine\entity\object\Minecart::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\object\Minecart{
			return new \pocketmine\entity\object\Minecart(Helper::parseLocation($nbt, $world), $nbt);
		}, ['Minecart', 'minecraft:minecart']);

		$this->register(\pocketmine\entity\object\Boat::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\object\Boat{
			return new \pocketmine\entity\object\Boat(Helper::parseLocation($nbt, $world), $nbt);
		}, ['Boat', 'minecraft:boat']);

		$this->register(\pocketmine\entity\object\ChestBoat::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\object\ChestBoat{
			return new \pocketmine\entity\object\ChestBoat(Helper::parseLocation($nbt, $world), $nbt);
		}, ['ChestBoat', 'minecraft:chest_boat']);

		$this->register(ArmorStand::class, function(World $world, CompoundTag $nbt) : ArmorStand{
			return new ArmorStand(Helper::parseLocation($nbt, $world), $nbt);
		}, ['ArmorStand', 'minecraft:armor_stand']);

		$this->register(IceBomb::class, function(World $world, CompoundTag $nbt) : IceBomb{
			return new IceBomb(Helper::parseLocation($nbt, $world), null, $nbt);
		}, ['minecraft:ice_bomb']);

		$this->register(WindCharge::class, function(World $world, CompoundTag $nbt) : WindCharge{
			return new WindCharge(Helper::parseLocation($nbt, $world), null, $nbt);
		}, ['minecraft:wind_charge_projectile']);

		$this->register(ItemEntity::class, function(World $world, CompoundTag $nbt) : ItemEntity{
			$itemTag = $nbt->getCompoundTag(ItemEntity::TAG_ITEM);
			if($itemTag === null){
				throw new SavedDataLoadingException("Expected \"" . ItemEntity::TAG_ITEM . "\" NBT tag not found");
			}

			$item = Item::nbtDeserialize($itemTag);
			if($item->isNull()){
				throw new SavedDataLoadingException("Item is invalid");
			}
			return new ItemEntity(Helper::parseLocation($nbt, $world), $item, $nbt);
		}, ['Item', 'minecraft:item']);

		$this->register(Painting::class, function(World $world, CompoundTag $nbt) : Painting{
			$motive = PaintingMotive::getMotiveByName($nbt->getString(Painting::TAG_MOTIVE));
			if($motive === null){
				throw new SavedDataLoadingException("Unknown painting motive");
			}
			$blockIn = new Vector3($nbt->getInt(Painting::TAG_TILE_X), $nbt->getInt(Painting::TAG_TILE_Y), $nbt->getInt(Painting::TAG_TILE_Z));
			if(($directionTag = $nbt->getTag(Painting::TAG_DIRECTION_BE)) instanceof ByteTag){
				$facing = Painting::DATA_TO_FACING[$directionTag->getValue()] ?? Facing::NORTH;
			}elseif(($facingTag = $nbt->getTag(Painting::TAG_FACING_JE)) instanceof ByteTag){
				$facing = Painting::DATA_TO_FACING[$facingTag->getValue()] ?? Facing::NORTH;
			}else{
				throw new SavedDataLoadingException("Missing facing info");
			}

			return new Painting(Helper::parseLocation($nbt, $world), $blockIn, $facing, $motive, $nbt);
		}, ['Painting', 'minecraft:painting']);

		$this->register(PrimedTNT::class, function(World $world, CompoundTag $nbt) : PrimedTNT{
			return new PrimedTNT(Helper::parseLocation($nbt, $world), $nbt);
		}, ['PrimedTnt', 'PrimedTNT', 'minecraft:tnt']);

		$this->register(Snowball::class, function(World $world, CompoundTag $nbt) : Snowball{
			return new Snowball(Helper::parseLocation($nbt, $world), null, $nbt);
		}, ['Snowball', 'minecraft:snowball']);

		$this->register(SplashPotion::class, function(World $world, CompoundTag $nbt) : SplashPotion{
			$potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort(SplashPotion::TAG_POTION_ID, PotionTypeIds::WATER));
			if($potionType === null){
				throw new SavedDataLoadingException("No such potion type");
			}
			return new SplashPotion(Helper::parseLocation($nbt, $world), null, $potionType, $nbt);
		}, ['ThrownPotion', 'minecraft:potion', 'thrownpotion']);

		$this->register(Trident::class, function(World $world, CompoundTag $nbt) : Trident{
			$itemTag = $nbt->getCompoundTag(Trident::TAG_ITEM);
			if($itemTag === null){
				throw new SavedDataLoadingException("Expected \"" . Trident::TAG_ITEM . "\" NBT tag not found");
			}

			$item = Item::nbtDeserialize($itemTag);
			if($item->isNull()){
				throw new SavedDataLoadingException("Trident item is invalid");
			}
			return new Trident(Helper::parseLocation($nbt, $world), $item, null, $nbt);
		}, [
			'minecraft:trident', //java
			'minecraft:thrown_trident', //bedrock
			'Trident', //backwards compat for people who used #4547 before it was merged, since it was sitting around for 4 years...
			'ThrownTrident' //as above
		]);

		$this->register(Squid::class, function(World $world, CompoundTag $nbt) : Squid{
			return new Squid(Helper::parseLocation($nbt, $world), $nbt);
		}, ['Squid', 'minecraft:squid']);

		$this->register(Villager::class, function(World $world, CompoundTag $nbt) : Villager{
			return new Villager(Helper::parseLocation($nbt, $world), $nbt);
		}, ['Villager', 'minecraft:villager']);

		$this->register(Zombie::class, function(World $world, CompoundTag $nbt) : Zombie{
			return new Zombie(Helper::parseLocation($nbt, $world), $nbt);
		}, ['Zombie', 'minecraft:zombie']);

		$this->register(Human::class, function(World $world, CompoundTag $nbt) : Human{
			return new Human(Helper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
		}, ['Human']);

		$this->registerSimpleMobs();
	}

	/**
	 * Registers an entity type into the index.
	 *
	 * @param string   $className Class that extends Entity
	 * @param string[] $saveNames An array of save names which this entity might be saved under.
	 * @phpstan-param class-string<Entity> $className
	 * @phpstan-param list<string> $saveNames
	 * @phpstan-param \Closure(World $world, CompoundTag $nbt) : Entity $creationFunc
	 *
	 * NOTE: The first save name in the $saveNames array will be used when saving the entity to disk.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function register(string $className, \Closure $creationFunc, array $saveNames) : void{
		if(count($saveNames) === 0){
			throw new \InvalidArgumentException("At least one save name must be provided");
		}
		Utils::testValidInstance($className, Entity::class);
		Utils::validateCallableSignature(new CallbackType(
			new ReturnType(Entity::class),
			new ParameterType("world", World::class),
			new ParameterType("nbt", CompoundTag::class)
		), $creationFunc);

		foreach($saveNames as $name){
			$this->creationFuncs[$name] = $creationFunc;
		}

		$this->saveNames[$className] = reset($saveNames);
	}

	private function registerSimpleMobs() : void{
		$this->register(\pocketmine\entity\mob\Allay::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Allay{
			return new \pocketmine\entity\mob\Allay(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:allay']);

		$this->register(\pocketmine\entity\mob\Armadillo::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Armadillo{
			return new \pocketmine\entity\mob\Armadillo(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:armadillo']);

		$this->register(\pocketmine\entity\mob\Axolotl::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Axolotl{
			return new \pocketmine\entity\mob\Axolotl(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:axolotl']);

		$this->register(\pocketmine\entity\mob\Bat::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Bat{
			return new \pocketmine\entity\mob\Bat(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:bat']);

		$this->register(\pocketmine\entity\mob\Bee::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Bee{
			return new \pocketmine\entity\mob\Bee(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:bee']);

		$this->register(\pocketmine\entity\mob\Blaze::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Blaze{
			return new \pocketmine\entity\mob\Blaze(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:blaze']);

		$this->register(\pocketmine\entity\mob\Bogged::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Bogged{
			return new \pocketmine\entity\mob\Bogged(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:bogged']);

		$this->register(\pocketmine\entity\mob\Breeze::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Breeze{
			return new \pocketmine\entity\mob\Breeze(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:breeze']);

		$this->register(\pocketmine\entity\mob\Camel::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Camel{
			return new \pocketmine\entity\mob\Camel(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:camel']);

		$this->register(\pocketmine\entity\mob\CamelHusk::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\CamelHusk{
			return new \pocketmine\entity\mob\CamelHusk(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:camel_husk']);

		$this->register(\pocketmine\entity\mob\Cat::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Cat{
			return new \pocketmine\entity\mob\Cat(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:cat']);

		$this->register(\pocketmine\entity\mob\CaveSpider::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\CaveSpider{
			return new \pocketmine\entity\mob\CaveSpider(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:cave_spider']);

		$this->register(\pocketmine\entity\mob\Chicken::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Chicken{
			return new \pocketmine\entity\mob\Chicken(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:chicken']);

		$this->register(\pocketmine\entity\mob\Cod::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Cod{
			return new \pocketmine\entity\mob\Cod(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:cod']);

		$this->register(\pocketmine\entity\mob\CopperGolem::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\CopperGolem{
			return new \pocketmine\entity\mob\CopperGolem(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:copper_golem']);

		$this->register(\pocketmine\entity\mob\Cow::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Cow{
			return new \pocketmine\entity\mob\Cow(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:cow']);

		$this->register(\pocketmine\entity\mob\Creaking::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Creaking{
			return new \pocketmine\entity\mob\Creaking(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:creaking']);

		$this->register(\pocketmine\entity\mob\Creeper::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Creeper{
			return new \pocketmine\entity\mob\Creeper(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:creeper']);

		$this->register(\pocketmine\entity\mob\Dolphin::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Dolphin{
			return new \pocketmine\entity\mob\Dolphin(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:dolphin']);

		$this->register(\pocketmine\entity\mob\Donkey::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Donkey{
			return new \pocketmine\entity\mob\Donkey(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:donkey']);

		$this->register(\pocketmine\entity\mob\Drowned::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Drowned{
			return new \pocketmine\entity\mob\Drowned(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:drowned']);

		$this->register(\pocketmine\entity\mob\ElderGuardian::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\ElderGuardian{
			return new \pocketmine\entity\mob\ElderGuardian(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:elder_guardian']);

		$this->register(\pocketmine\entity\mob\Enderman::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Enderman{
			return new \pocketmine\entity\mob\Enderman(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:enderman']);

		$this->register(\pocketmine\entity\mob\Endermite::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Endermite{
			return new \pocketmine\entity\mob\Endermite(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:endermite']);

		$this->register(\pocketmine\entity\mob\Evoker::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Evoker{
			return new \pocketmine\entity\mob\Evoker(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:evocation_illager']);

		$this->register(\pocketmine\entity\mob\Fox::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Fox{
			return new \pocketmine\entity\mob\Fox(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:fox']);

		$this->register(\pocketmine\entity\mob\Frog::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Frog{
			return new \pocketmine\entity\mob\Frog(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:frog']);

		$this->register(\pocketmine\entity\mob\Frostbite::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Frostbite{
			return new \pocketmine\entity\mob\Frostbite(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:frostbite']);

		$this->register(\pocketmine\entity\mob\Ghast::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Ghast{
			return new \pocketmine\entity\mob\Ghast(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:ghast']);

		$this->register(\pocketmine\entity\mob\GlowSquid::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\GlowSquid{
			return new \pocketmine\entity\mob\GlowSquid(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:glow_squid']);

		$this->register(\pocketmine\entity\mob\Goat::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Goat{
			return new \pocketmine\entity\mob\Goat(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:goat']);

		$this->register(\pocketmine\entity\mob\Guardian::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Guardian{
			return new \pocketmine\entity\mob\Guardian(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:guardian']);

		$this->register(\pocketmine\entity\mob\HappyGhast::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\HappyGhast{
			return new \pocketmine\entity\mob\HappyGhast(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:happy_ghast']);

		$this->register(\pocketmine\entity\mob\Hoglin::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Hoglin{
			return new \pocketmine\entity\mob\Hoglin(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:hoglin']);

		$this->register(\pocketmine\entity\mob\Horse::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Horse{
			return new \pocketmine\entity\mob\Horse(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:horse']);

		$this->register(\pocketmine\entity\mob\Husk::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Husk{
			return new \pocketmine\entity\mob\Husk(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:husk']);

		$this->register(\pocketmine\entity\mob\IronGolem::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\IronGolem{
			return new \pocketmine\entity\mob\IronGolem(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:iron_golem']);

		$this->register(\pocketmine\entity\mob\Llama::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Llama{
			return new \pocketmine\entity\mob\Llama(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:llama']);

		$this->register(\pocketmine\entity\mob\MagmaCube::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\MagmaCube{
			return new \pocketmine\entity\mob\MagmaCube(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:magma_cube']);

		$this->register(\pocketmine\entity\mob\Mooshroom::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Mooshroom{
			return new \pocketmine\entity\mob\Mooshroom(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:mooshroom']);

		$this->register(\pocketmine\entity\mob\Mule::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Mule{
			return new \pocketmine\entity\mob\Mule(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:mule']);

		$this->register(\pocketmine\entity\mob\Nautilus::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Nautilus{
			return new \pocketmine\entity\mob\Nautilus(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:nautilus']);

		$this->register(\pocketmine\entity\mob\Ocelot::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Ocelot{
			return new \pocketmine\entity\mob\Ocelot(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:ocelot']);

		$this->register(\pocketmine\entity\mob\Panda::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Panda{
			return new \pocketmine\entity\mob\Panda(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:panda']);

		$this->register(\pocketmine\entity\mob\Parched::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Parched{
			return new \pocketmine\entity\mob\Parched(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:parched']);

		$this->register(\pocketmine\entity\mob\Parrot::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Parrot{
			return new \pocketmine\entity\mob\Parrot(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:parrot']);

		$this->register(\pocketmine\entity\mob\Phantom::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Phantom{
			return new \pocketmine\entity\mob\Phantom(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:phantom']);

		$this->register(\pocketmine\entity\mob\Pig::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Pig{
			return new \pocketmine\entity\mob\Pig(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:pig']);

		$this->register(\pocketmine\entity\mob\Piglin::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Piglin{
			return new \pocketmine\entity\mob\Piglin(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:piglin']);

		$this->register(\pocketmine\entity\mob\PiglinBrute::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\PiglinBrute{
			return new \pocketmine\entity\mob\PiglinBrute(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:piglin_brute']);

		$this->register(\pocketmine\entity\mob\Pillager::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Pillager{
			return new \pocketmine\entity\mob\Pillager(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:pillager']);

		$this->register(\pocketmine\entity\mob\PolarBear::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\PolarBear{
			return new \pocketmine\entity\mob\PolarBear(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:polar_bear']);

		$this->register(\pocketmine\entity\mob\Pufferfish::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Pufferfish{
			return new \pocketmine\entity\mob\Pufferfish(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:pufferfish']);

		$this->register(\pocketmine\entity\mob\Rabbit::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Rabbit{
			return new \pocketmine\entity\mob\Rabbit(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:rabbit']);

		$this->register(\pocketmine\entity\mob\Ravager::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Ravager{
			return new \pocketmine\entity\mob\Ravager(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:ravager']);

		$this->register(\pocketmine\entity\mob\Salmon::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Salmon{
			return new \pocketmine\entity\mob\Salmon(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:salmon']);

		$this->register(\pocketmine\entity\mob\Sheep::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Sheep{
			return new \pocketmine\entity\mob\Sheep(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:sheep']);

		$this->register(\pocketmine\entity\mob\Shulker::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Shulker{
			return new \pocketmine\entity\mob\Shulker(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:shulker']);

		$this->register(\pocketmine\entity\mob\Silverfish::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Silverfish{
			return new \pocketmine\entity\mob\Silverfish(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:silverfish']);

		$this->register(\pocketmine\entity\mob\Skeleton::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Skeleton{
			return new \pocketmine\entity\mob\Skeleton(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:skeleton']);

		$this->register(\pocketmine\entity\mob\SkeletonHorse::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\SkeletonHorse{
			return new \pocketmine\entity\mob\SkeletonHorse(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:skeleton_horse']);

		$this->register(\pocketmine\entity\mob\Slime::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Slime{
			return new \pocketmine\entity\mob\Slime(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:slime']);

		$this->register(\pocketmine\entity\mob\Sniffer::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Sniffer{
			return new \pocketmine\entity\mob\Sniffer(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:sniffer']);

		$this->register(\pocketmine\entity\mob\SnowGolem::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\SnowGolem{
			return new \pocketmine\entity\mob\SnowGolem(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:snow_golem']);

		$this->register(\pocketmine\entity\mob\Spider::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Spider{
			return new \pocketmine\entity\mob\Spider(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:spider']);

		$this->register(\pocketmine\entity\mob\Stray::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Stray{
			return new \pocketmine\entity\mob\Stray(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:stray']);

		$this->register(\pocketmine\entity\mob\Strider::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Strider{
			return new \pocketmine\entity\mob\Strider(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:strider']);

		$this->register(\pocketmine\entity\mob\SulfurCube::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\SulfurCube{
			return new \pocketmine\entity\mob\SulfurCube(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:sulfur_cube']);

		$this->register(\pocketmine\entity\mob\Tadpole::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Tadpole{
			return new \pocketmine\entity\mob\Tadpole(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:tadpole']);

		$this->register(\pocketmine\entity\mob\TraderLlama::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\TraderLlama{
			return new \pocketmine\entity\mob\TraderLlama(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:trader_llama']);

		$this->register(\pocketmine\entity\mob\TropicalFish::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\TropicalFish{
			return new \pocketmine\entity\mob\TropicalFish(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:tropicalfish']);

		$this->register(\pocketmine\entity\mob\Turtle::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Turtle{
			return new \pocketmine\entity\mob\Turtle(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:turtle']);

		$this->register(\pocketmine\entity\mob\Vex::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Vex{
			return new \pocketmine\entity\mob\Vex(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:vex']);

		$this->register(\pocketmine\entity\mob\Vindicator::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Vindicator{
			return new \pocketmine\entity\mob\Vindicator(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:vindicator']);

		$this->register(\pocketmine\entity\mob\WanderingTrader::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\WanderingTrader{
			return new \pocketmine\entity\mob\WanderingTrader(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:wandering_trader']);

		$this->register(\pocketmine\entity\mob\Warden::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Warden{
			return new \pocketmine\entity\mob\Warden(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:warden']);

		$this->register(\pocketmine\entity\mob\Witch::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Witch{
			return new \pocketmine\entity\mob\Witch(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:witch']);

		$this->register(\pocketmine\entity\mob\WitherSkeleton::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\WitherSkeleton{
			return new \pocketmine\entity\mob\WitherSkeleton(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:wither_skeleton']);

		$this->register(\pocketmine\entity\mob\Wolf::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Wolf{
			return new \pocketmine\entity\mob\Wolf(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:wolf']);

		$this->register(\pocketmine\entity\mob\Zoglin::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Zoglin{
			return new \pocketmine\entity\mob\Zoglin(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:zoglin']);

		$this->register(\pocketmine\entity\mob\ZombieHorse::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\ZombieHorse{
			return new \pocketmine\entity\mob\ZombieHorse(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:zombie_horse']);

		$this->register(\pocketmine\entity\mob\ZombieNautilus::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\ZombieNautilus{
			return new \pocketmine\entity\mob\ZombieNautilus(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:zombie_nautilus']);

		$this->register(\pocketmine\entity\mob\ZombiePigman::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\ZombiePigman{
			return new \pocketmine\entity\mob\ZombiePigman(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:zombie_pigman']);

		$this->register(\pocketmine\entity\mob\EnderDragon::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\EnderDragon{
			return new \pocketmine\entity\mob\EnderDragon(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:ender_dragon', 'EnderDragon']);

		$this->register(\pocketmine\entity\mob\Wither::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\Wither{
			return new \pocketmine\entity\mob\Wither(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:wither', 'Wither']);

		$this->register(\pocketmine\entity\mob\ZombieVillager::class, function(World $world, CompoundTag $nbt) : \pocketmine\entity\mob\ZombieVillager{
			return new \pocketmine\entity\mob\ZombieVillager(Helper::parseLocation($nbt, $world), $nbt);
		}, ['minecraft:zombie_villager']);

	}

	/**
	 * Creates a fresh entity of the type with the given save name at the given location.
	 *
	 * @return Entity|null null if the save name is not registered
	 */
	public function createEntity(World $world, string $saveName, Location $location) : ?Entity{
		$func = $this->creationFuncs[$saveName] ?? null;
		if($func === null){
			return null;
		}

		$nbt = CompoundTag::create()
			->setTag(Entity::TAG_POS, new ListTag([
				new DoubleTag($location->x),
				new DoubleTag($location->y),
				new DoubleTag($location->z)
			]))
			->setTag(Entity::TAG_ROTATION, new ListTag([
				new FloatTag($location->yaw),
				new FloatTag($location->pitch)
			]));

		return $func($world, $nbt);
	}

	/**
	 * @phpstan-param class-string<Entity> $class
	 */
	public function isRegistered(string $class) : bool{
		return isset($this->saveNames[$class]);
	}

	/**
	 * Creates an entity from data stored on a chunk.
	 *
	 * @throws SavedDataLoadingException
	 * @internal
	 */
	public function createFromData(World $world, CompoundTag $nbt) : ?Entity{
		try{
			$saveId = $nbt->getTag(self::TAG_IDENTIFIER) ?? $nbt->getTag(self::TAG_LEGACY_ID);
			$func = null;
			if($saveId instanceof StringTag){
				$func = $this->creationFuncs[$saveId->getValue()] ?? null;
			}elseif($saveId instanceof IntTag){ //legacy MCPE format
				$stringId = LegacyEntityIdToStringIdMap::getInstance()->legacyToString($saveId->getValue() & 0xff);
				$func = $stringId !== null ? $this->creationFuncs[$stringId] ?? null : null;
			}
			if($func === null){
				return null;
			}
			/** @var Entity $entity */
			$entity = $func($world, $nbt);

			return $entity;
		}catch(NbtException $e){
			throw new SavedDataLoadingException($e->getMessage(), 0, $e);
		}
	}

	public function injectSaveId(string $class, CompoundTag $saveData) : void{
		if(isset($this->saveNames[$class])){
			$saveData->setTag(self::TAG_IDENTIFIER, new StringTag($this->saveNames[$class]));
		}else{
			throw new \InvalidArgumentException("Entity $class is not registered");
		}
	}

	/**
	 * @phpstan-param class-string<Entity> $class
	 */
	public function getSaveId(string $class) : string{
		if(isset($this->saveNames[$class])){
			return $this->saveNames[$class];
		}
		throw new \InvalidArgumentException("Entity $class is not registered");
	}
}
