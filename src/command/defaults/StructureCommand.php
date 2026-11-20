<?php

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use pocketmine\world\structure\StructureManager;
use function count;
use function implode;
use function strtolower;

class StructureCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"structure",
			"Saves, loads, lists and deletes structures",
			"/structure save <name> <x1> <y1> <z1> <x2> <y2> <z2>\n" .
			"/structure load <name> <x> <y> <z> [rotation]\n" .
			"/structure delete <name>\n" .
			"/structure list"
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_STRUCTURE);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}

		$action = strtolower($args[0]);
		[$base, $world] = $this->resolveBase($sender);

		switch($action){
			case "save":
				if(count($args) !== 8){
					throw new InvalidCommandSyntaxException();
				}
				$name = $args[1];
				if(!StructureManager::isValidName($name)){
					$sender->sendMessage(TextFormat::RED . "Invalid structure name. Use letters, digits, _ - . (max 64 characters).");
					return true;
				}
				$first = new Vector3(
					$this->getRelativeDouble($base->x, $sender, $args[2]),
					$this->getRelativeDouble($base->y, $sender, $args[3], World::Y_MIN, World::Y_MAX),
					$this->getRelativeDouble($base->z, $sender, $args[4])
				);
				$second = new Vector3(
					$this->getRelativeDouble($base->x, $sender, $args[5]),
					$this->getRelativeDouble($base->y, $sender, $args[6], World::Y_MIN, World::Y_MAX),
					$this->getRelativeDouble($base->z, $sender, $args[7])
				);
				if(StructureManager::save($world, $name, $first, $second)){
					$sender->sendMessage(TextFormat::GREEN . "Structure '$name' saved.");
				}else{
					$sender->sendMessage(TextFormat::RED . "Could not save structure '$name' (invalid size or write error).");
				}
				return true;

			case "load":
				if(count($args) < 5 || count($args) > 6){
					throw new InvalidCommandSyntaxException();
				}
				$name = $args[1];
				$target = new Vector3(
					$this->getRelativeDouble($base->x, $sender, $args[2]),
					$this->getRelativeDouble($base->y, $sender, $args[3], World::Y_MIN, World::Y_MAX),
					$this->getRelativeDouble($base->z, $sender, $args[4])
				);
				$rotation = count($args) === 6 ? (int) $args[5] : 0;
				if(StructureManager::load($world, $name, $target, $rotation)){
					$sender->sendMessage(TextFormat::GREEN . "Structure '$name' loaded.");
				}else{
					$sender->sendMessage(TextFormat::RED . "Could not load structure '$name'.");
				}
				return true;

			case "delete":
				if(count($args) !== 2){
					throw new InvalidCommandSyntaxException();
				}
				if(StructureManager::delete($world, $args[1])){
					$sender->sendMessage(TextFormat::GREEN . "Structure '" . $args[1] . "' deleted.");
				}else{
					$sender->sendMessage(TextFormat::RED . "No such structure: " . $args[1]);
				}
				return true;

			case "list":
				$structures = StructureManager::list($world);
				if(count($structures) === 0){
					$sender->sendMessage(TextFormat::GRAY . "No structures saved in this world's data folder.");
				}else{
					$sender->sendMessage(TextFormat::GREEN . "Structures: " . implode(", ", $structures));
				}
				return true;
		}

		throw new InvalidCommandSyntaxException();
	}

	/**
	 * @return array{Vector3, World}
	 */
	private function resolveBase(CommandSender $sender) : array{
		if($sender instanceof Player){
			return [$sender->getPosition(), $sender->getWorld()];
		}
		$world = $sender->getServer()->getWorldManager()->getDefaultWorld();
		if($world === null){
			throw new \InvalidArgumentException("No default world loaded");
		}
		return [new Vector3(0, 0, 0), $world];
	}
}
