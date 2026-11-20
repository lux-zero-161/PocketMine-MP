<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\CommandBlock as TileCommandBlock;
use pocketmine\block\utils\AnyFacing;
use pocketmine\block\utils\RedstonePowerHelper;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CommandBlockUpdateClientboundPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\player\Player;
use function max;

class CommandBlock extends Opaque implements AnyFacing{
	public const MODE_IMPULSE = 0;
	public const MODE_REPEAT = 1;
	public const MODE_CHAIN = 2;

	protected int $facing = Facing::UP;
	protected bool $conditional = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
		$w->bool($this->conditional);
	}

	public function getFacing() : int{ return $this->facing; }

	/** @return $this */
	public function setFacing(int $facing) : self{
		Facing::validate($facing);
		$this->facing = $facing;
		return $this;
	}

	public function isConditional() : bool{ return $this->conditional; }

	/** @return $this */
	public function setConditional(bool $conditional) : self{
		$this->conditional = $conditional;
		return $this;
	}

	public function onNearbyBlockChange() : void{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileCommandBlock){
			return;
		}

		$powered = RedstonePowerHelper::isPowered($this);
		if($tile->isPowered() === $powered){
			return;
		}
		$tile->setPowered($powered);

		if($powered){
			$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, max(0, $tile->getTickDelay()));
		}
	}

	public function onScheduledUpdate() : void{
		$world = $this->position->getWorld();
		$tile = $world->getTile($this->position);
		if(!$tile instanceof TileCommandBlock){
			return;
		}

		$auto = !$tile->isRedstoneMode();
		if(!$auto && !$tile->isPowered() && $tile->getMode() !== self::MODE_CHAIN){
			return;
		}

		$this->execute($tile);

		if($tile->getMode() === self::MODE_REPEAT && ($auto || $tile->isPowered())){
			$world->scheduleDelayedBlockUpdate($this->position, max(1, $tile->getTickDelay()));
		}
	}

	/**
	 * Executes the command stored in the given command block tile.
	 *
	 * @param bool $fromNeighbour whether this was triggered by a neighbouring chain command block
	 */
	public function execute(TileCommandBlock $tile, bool $fromNeighbour = false) : bool{
		if($this->conditional){
			$previous = $this->getSide(Facing::opposite($this->facing));
			$previousTile = $previous->getPosition()->getWorld()->getTile($previous->getPosition());
			if(!$previousTile instanceof TileCommandBlock || $previousTile->getSuccessCount() <= 0){
				$tile->setConditionMet(false);
				$tile->setSuccessCount(0);
				return false;
			}
		}
		$tile->setConditionMet(true);

		$command = trim($tile->getCommand());
		if($command === ""){
			$tile->setSuccessCount(0);
			return false;
		}
		if($command[0] === "/"){
			$command = substr($command, 1);
		}

		$server = $this->position->getWorld()->getServer();
		$sender = new ConsoleCommandSender($server, $server->getLanguage());
		$success = $server->dispatchCommand($sender, $command);
		$tile->setSuccessCount($success ? 1 : 0);
		if($tile->isTrackingOutput()){
			$tile->setLastOutput($success ? "Command executed successfully" : "Command failed");
		}

		if($success){
			$this->triggerNext();
		}
		return $success;
	}

	/**
	 * Propagates execution to the chain command block in front of this one.
	 */
	private function triggerNext() : void{
		$next = $this->getSide($this->facing);
		if(!$next instanceof CommandBlock){
			return;
		}
		$tile = $next->getPosition()->getWorld()->getTile($next->getPosition());
		if($tile instanceof TileCommandBlock && $tile->getMode() === self::MODE_CHAIN){
			$next->execute($tile, true);
		}
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($player === null || (!$player->isCreative() && !$player->hasPermission(\pocketmine\permission\DefaultPermissions::ROOT_OPERATOR))){
			return false;
		}
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof TileCommandBlock){
			return false;
		}

		$packet = CommandBlockUpdateClientboundPacket::create(
			new BlockPosition($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ()),
			$tile->getMode(),
			$tile->isRedstoneMode(),
			$tile->isConditional(),
			$tile->getCommand(),
			$tile->getLastOutput(),
			$tile->getName(),
			$tile->isTrackingOutput(),
			$tile->getTickDelay(),
			$tile->isExecuteOnFirstTick()
		);
		$player->getNetworkSession()->sendDataPacket($packet);
		return true;
	}
}
