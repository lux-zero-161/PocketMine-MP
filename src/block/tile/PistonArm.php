<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\world\World;
use function count;

class PistonArm extends Spawnable{
	private const TAG_ATTACHED_BLOCKS = "AttachedBlocks";
	private const TAG_BREAK_BLOCKS = "BreakBlocks";
	private const TAG_LAST_PROGRESS = "LastProgress";
	private const TAG_NEW_STATE = "NewState";
	private const TAG_PROGRESS = "Progress";
	private const TAG_STATE = "State";
	private const TAG_STICKY = "Sticky";

	/** @var Vector3[] */
	private array $attachedBlocks = [];
	/** @var Vector3[] */
	private array $breakBlocks = [];
	private float $lastProgress = 0.0;
	private int $newState = 0;
	private float $progress = 0.0;
	private int $state = 0;
	private bool $sticky = false;

	public function __construct(World $world, Vector3 $pos){
		parent::__construct($world, $pos);
	}

	/** @return Vector3[] */
	public function getAttachedBlocks() : array{ return $this->attachedBlocks; }

	/** @param Vector3[] $blocks */
	public function setAttachedBlocks(array $blocks) : void{ $this->attachedBlocks = $blocks; }

	/** @return Vector3[] */
	public function getBreakBlocks() : array{ return $this->breakBlocks; }

	/** @param Vector3[] $blocks */
	public function setBreakBlocks(array $blocks) : void{ $this->breakBlocks = $blocks; }

	public function getLastProgress() : float{ return $this->lastProgress; }
	public function setLastProgress(float $lastProgress) : void{ $this->lastProgress = $lastProgress; }

	public function getNewState() : int{ return $this->newState; }
	public function setNewState(int $newState) : void{ $this->newState = $newState; }

	public function getProgress() : float{ return $this->progress; }
	public function setProgress(float $progress) : void{ $this->progress = $progress; }

	public function getState() : int{ return $this->state; }
	public function setState(int $state) : void{ $this->state = $state; }

	public function isSticky() : bool{ return $this->sticky; }
	public function setSticky(bool $sticky) : void{ $this->sticky = $sticky; }

	public function readSaveData(CompoundTag $nbt) : void{
		$this->attachedBlocks = $this->readBlocks($nbt, self::TAG_ATTACHED_BLOCKS);
		$this->breakBlocks = $this->readBlocks($nbt, self::TAG_BREAK_BLOCKS);
		$this->lastProgress = $nbt->getFloat(self::TAG_LAST_PROGRESS, 0.0);
		$this->newState = $nbt->getByte(self::TAG_NEW_STATE, 0);
		$this->progress = $nbt->getFloat(self::TAG_PROGRESS, 0.0);
		$this->state = $nbt->getByte(self::TAG_STATE, 0);
		$this->sticky = $nbt->getByte(self::TAG_STICKY, 0) !== 0;
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->writeBlocks($nbt, self::TAG_ATTACHED_BLOCKS, $this->attachedBlocks);
		$this->writeBlocks($nbt, self::TAG_BREAK_BLOCKS, $this->breakBlocks);
		$nbt->setFloat(self::TAG_LAST_PROGRESS, $this->lastProgress);
		$nbt->setByte(self::TAG_NEW_STATE, $this->newState);
		$nbt->setFloat(self::TAG_PROGRESS, $this->progress);
		$nbt->setByte(self::TAG_STATE, $this->state);
		$nbt->setByte(self::TAG_STICKY, $this->sticky ? 1 : 0);
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt, TypeConverter $typeConverter) : void{
		$this->writeSaveData($nbt);
	}

	/** @return Vector3[] */
	private function readBlocks(CompoundTag $nbt, string $tagName) : array{
		$list = $nbt->getListTag($tagName, IntTag::class);
		if($list === null){
			return [];
		}

		$values = $list->getValue();
		$result = [];
		for($i = 0; $i + 2 < count($values); $i += 3){
			$result[] = new Vector3($values[$i]->getValue(), $values[$i + 1]->getValue(), $values[$i + 2]->getValue());
		}
		return $result;
	}

	/** @param Vector3[] $blocks */
	private function writeBlocks(CompoundTag $nbt, string $tagName, array $blocks) : void{
		$values = [];
		foreach($blocks as $block){
			$values[] = new IntTag($block->getFloorX());
			$values[] = new IntTag($block->getFloorY());
			$values[] = new IntTag($block->getFloorZ());
		}
		$nbt->setTag($tagName, new ListTag($values, NBT::TAG_Int));
	}
}
