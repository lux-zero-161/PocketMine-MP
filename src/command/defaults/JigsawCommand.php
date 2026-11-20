<?php

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\block\Jigsaw;
use pocketmine\block\tile\Jigsaw as TileJigsaw;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function count;
use function strtolower;

class JigsawCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"jigsaw",
			"Configures and triggers jigsaw blocks",
			"/jigsaw set <x> <y> <z> <pool> [target]\n/jigsaw generate <x> <y> <z>"
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_JIGSAW);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) < 5){
			throw new InvalidCommandSyntaxException();
		}
		$action = strtolower($args[0]);
		[$base, $world] = $this->resolveBase($sender);
		$x = (int) $this->getRelativeDouble($base->x, $sender, $args[1]);
		$y = (int) $this->getRelativeDouble($base->y, $sender, $args[2], World::Y_MIN, World::Y_MAX);
		$z = (int) $this->getRelativeDouble($base->z, $sender, $args[3]);
		$block = $world->getBlockAt($x, $y, $z);
		$tile = $world->getTileAt($x, $y, $z);
		if(!$block instanceof Jigsaw || !$tile instanceof TileJigsaw){
			$sender->sendMessage(TextFormat::RED . "No jigsaw block at $x $y $z.");
			return true;
		}

		switch($action){
			case "set":
				if(count($args) > 6){
					throw new InvalidCommandSyntaxException();
				}
				$tile->setPool($args[4]);
				if(count($args) === 6){
					$tile->setTarget($args[5]);
				}
				$sender->sendMessage(TextFormat::GREEN . "Jigsaw pool set to '" . $tile->getPool() . "'.");
				return true;

			case "generate":
				$count = 0;
				$block->generate($tile, 0, $count);
				$sender->sendMessage(TextFormat::GREEN . "Generated $count jigsaw piece(s).");
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
