<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\block\BaseRail;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use function cos;
use function deg2rad;
use function sin;

class Minecart extends Entity{
	private ?Item $dropItem;

	public function __construct(Location $location, ?\pocketmine\nbt\tag\CompoundTag $nbt = null, ?Item $dropItem = null){
		$this->dropItem = $dropItem;
		parent::__construct($location, $nbt);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::MINECART; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.7, 0.98);
	}

	protected function getInitialDragMultiplier() : float{ return 0.01; }

	protected function getInitialGravity() : float{ return 0.03; }

	public function getName() : string{
		return "Minecart";
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		if($this->getPassenger() === null){
			return $this->addPassenger($player);
		}
		return false;
	}

	public function onRail() : bool{
		return $this->getWorld()->getBlock($this->location->subtract(0, 0.5, 0)) instanceof BaseRail;
	}

	public function handleRiderInput(Player $rider, BitSet $flags) : void{
		if(!$this->onRail()){
			return;
		}
		$forward = $flags->get(PlayerAuthInputFlags::UP) ? 1 : ($flags->get(PlayerAuthInputFlags::DOWN) ? -1 : 0);
		$strafe = $flags->get(PlayerAuthInputFlags::LEFT) ? -1 : ($flags->get(PlayerAuthInputFlags::RIGHT) ? 1 : 0);
		if($forward === 0 && $strafe === 0){
			return;
		}

		$yaw = deg2rad($rider->getLocation()->yaw);
		$this->setMotion(new Vector3(
			(sin($yaw) * $forward + cos($yaw) * $strafe) * 0.3,
			0,
			(-cos($yaw) * $forward + sin($yaw) * $strafe) * 0.3
		));
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->onRail()){
			$motion = $this->getMotion();
			$this->setMotion(new Vector3($motion->x * 0.95, 0, $motion->z * 0.95));
		}
		return parent::onUpdate($currentTick);
	}

	/**
	 * @return Item[]
	 */
	public function getDrops() : array{
		return $this->dropItem !== null ? [clone $this->dropItem] : [];
	}

	public function getXpDropAmount() : int{
		return 0;
	}
}
