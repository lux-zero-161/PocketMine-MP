<?php

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Raid;
use function count;

class RaidCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"raid",
			"Starts a simplified raid at your position",
			"/raid [waves]"
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_RAID);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) > 1 || !$sender instanceof Player){
			throw new InvalidCommandSyntaxException();
		}
		$waves = count($args) === 1 ? (int) $args[0] : 3;
		Raid::start($sender, $waves);
		$sender->sendMessage(TextFormat::GREEN . "Raid gestartet ($waves Wellen).");
		return true;
	}
}
