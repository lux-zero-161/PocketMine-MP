<?php

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function count;
use function str_contains;
use function strtolower;

class SummonCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"summon",
			"Summons an entity",
			"/summon <entity> [x] [y] [z]"
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_SUMMON);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) < 1 || count($args) === 2 || count($args) > 4){
			throw new InvalidCommandSyntaxException();
		}

		if($sender instanceof Player){
			$base = $sender->getPosition();
			$world = $sender->getWorld();
			$yaw = $sender->getLocation()->yaw;
			$pitch = $sender->getLocation()->pitch;
		}else{
			$world = $sender->getServer()->getWorldManager()->getDefaultWorld();
			if($world === null){
				$sender->sendMessage(TextFormat::RED . "No default world loaded.");
				return true;
			}
			$base = $world->getSpawnLocation();
			$yaw = 0.0;
			$pitch = 0.0;
		}

		$name = strtolower($args[0]);
		if(!str_contains($name, ":")){
			$name = "minecraft:" . $name;
		}

		if(count($args) === 4){
			$x = $this->getRelativeDouble($base->x, $sender, $args[1]);
			$y = $this->getRelativeDouble($base->y, $sender, $args[2], World::Y_MIN, World::Y_MAX);
			$z = $this->getRelativeDouble($base->z, $sender, $args[3]);
		}else{
			$x = $base->x;
			$y = $base->y;
			$z = $base->z;
		}

		$location = Location::fromObject(new Vector3($x, $y, $z), $world, $yaw, $pitch);
		$entity = EntityFactory::getInstance()->createEntity($world, $name, $location);
		if($entity === null){
			$sender->sendMessage(TextFormat::RED . "Unknown or unsupported entity: " . $args[0]);
			return true;
		}

		$entity->spawnToAll();
		$sender->sendMessage(TextFormat::GREEN . "Summoned " . $args[0] . ".");
		return true;
	}
}
