<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\block\CommandBlock as BlockCommandBlock;
use pocketmine\nbt\tag\CompoundTag;
use function max;
use function mb_substr;

/**
 * Stores the data of a command block, including the command, its mode and the last output.
 */
class CommandBlock extends Tile{
	private const TAG_COMMAND = "Command";
	private const TAG_LAST_OUTPUT = "LastOutput";
	private const TAG_NAME = "CustomName";
	private const TAG_MODE = "LPCommandMode";
	private const TAG_CONDITIONAL = "LPCondionalMode";
	private const TAG_REDSTONE_MODE = "LPRedstoneMode";
	private const TAG_TRACK_OUTPUT = "LPTrackOutput";
	private const TAG_TICK_DELAY = "LPTickDelay";
	private const TAG_EXECUTE_ON_FIRST_TICK = "ExecuteOnFirstTick";
	private const TAG_POWERED = "powered";
	private const TAG_CONDITION_MET = "conditionMet";
	private const TAG_SUCCESS_COUNT = "successCount";

	private string $command = "";
	private string $lastOutput = "";
	private string $name = "";
	private int $mode = BlockCommandBlock::MODE_IMPULSE;
	private bool $conditional = false;
	private bool $redstoneMode = true;
	private bool $trackOutput = true;
	private int $tickDelay = 0;
	private bool $executeOnFirstTick = true;
	private bool $powered = false;
	private bool $conditionMet = false;
	private int $successCount = 0;

	public function readSaveData(CompoundTag $nbt) : void{
		$this->command = $nbt->getString(self::TAG_COMMAND, "");
		$this->lastOutput = $nbt->getString(self::TAG_LAST_OUTPUT, "");
		$this->name = $nbt->getString(self::TAG_NAME, "");
		$this->mode = $nbt->getInt(self::TAG_MODE, BlockCommandBlock::MODE_IMPULSE);
		$this->conditional = $nbt->getByte(self::TAG_CONDITIONAL, 0) !== 0;
		$this->redstoneMode = $nbt->getByte(self::TAG_REDSTONE_MODE, 1) !== 0;
		$this->trackOutput = $nbt->getByte(self::TAG_TRACK_OUTPUT, 1) !== 0;
		$this->tickDelay = $nbt->getInt(self::TAG_TICK_DELAY, 0);
		$this->executeOnFirstTick = $nbt->getByte(self::TAG_EXECUTE_ON_FIRST_TICK, 1) !== 0;
		$this->powered = $nbt->getByte(self::TAG_POWERED, 0) !== 0;
		$this->conditionMet = $nbt->getByte(self::TAG_CONDITION_MET, 0) !== 0;
		$this->successCount = $nbt->getInt(self::TAG_SUCCESS_COUNT, 0);

		if($this->mode < BlockCommandBlock::MODE_IMPULSE || $this->mode > BlockCommandBlock::MODE_CHAIN){
			$this->mode = BlockCommandBlock::MODE_IMPULSE;
		}
		$this->tickDelay = max(0, $this->tickDelay);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setString(self::TAG_COMMAND, $this->command);
		$nbt->setString(self::TAG_LAST_OUTPUT, $this->lastOutput);
		$nbt->setString(self::TAG_NAME, $this->name);
		$nbt->setInt(self::TAG_MODE, $this->mode);
		$nbt->setByte(self::TAG_CONDITIONAL, $this->conditional ? 1 : 0);
		$nbt->setByte(self::TAG_REDSTONE_MODE, $this->redstoneMode ? 1 : 0);
		$nbt->setByte(self::TAG_TRACK_OUTPUT, $this->trackOutput ? 1 : 0);
		$nbt->setInt(self::TAG_TICK_DELAY, $this->tickDelay);
		$nbt->setByte(self::TAG_EXECUTE_ON_FIRST_TICK, $this->executeOnFirstTick ? 1 : 0);
		$nbt->setByte(self::TAG_POWERED, $this->powered ? 1 : 0);
		$nbt->setByte(self::TAG_CONDITION_MET, $this->conditionMet ? 1 : 0);
		$nbt->setInt(self::TAG_SUCCESS_COUNT, $this->successCount);
	}

	public function getCommand() : string{ return $this->command; }

	public function setCommand(string $command) : void{
		$this->command = self::limitString($command);
	}

	public function getLastOutput() : string{ return $this->lastOutput; }

	public function setLastOutput(string $lastOutput) : void{
		$this->lastOutput = self::limitString($lastOutput);
	}

	public function getName() : string{ return $this->name; }

	public function setName(string $name) : void{ $this->name = self::limitString($name); }

	public function getMode() : int{ return $this->mode; }

	public function setMode(int $mode) : void{
		if($mode < BlockCommandBlock::MODE_IMPULSE || $mode > BlockCommandBlock::MODE_CHAIN){
			throw new \InvalidArgumentException("Invalid command block mode: $mode");
		}
		$this->mode = $mode;
	}

	public function isConditional() : bool{ return $this->conditional; }

	public function setConditional(bool $conditional) : void{ $this->conditional = $conditional; }

	public function isRedstoneMode() : bool{ return $this->redstoneMode; }

	public function setRedstoneMode(bool $redstoneMode) : void{ $this->redstoneMode = $redstoneMode; }

	public function isTrackingOutput() : bool{ return $this->trackOutput; }

	public function setTrackOutput(bool $trackOutput) : void{ $this->trackOutput = $trackOutput; }

	public function getTickDelay() : int{ return $this->tickDelay; }

	public function setTickDelay(int $tickDelay) : void{ $this->tickDelay = max(0, $tickDelay); }

	public function isExecuteOnFirstTick() : bool{ return $this->executeOnFirstTick; }

	public function setExecuteOnFirstTick(bool $executeOnFirstTick) : void{ $this->executeOnFirstTick = $executeOnFirstTick; }

	public function isPowered() : bool{ return $this->powered; }

	public function setPowered(bool $powered) : void{ $this->powered = $powered; }

	public function isConditionMet() : bool{ return $this->conditionMet; }

	public function setConditionMet(bool $conditionMet) : void{ $this->conditionMet = $conditionMet; }

	private static function limitString(string $value) : string{
		//Bedrock limits these fields to 32500 characters
		return mb_substr($value, 0, 32500, "UTF-8");
	}

	public function getSuccessCount() : int{ return $this->successCount; }

	public function setSuccessCount(int $successCount) : void{ $this->successCount = $successCount; }
}
