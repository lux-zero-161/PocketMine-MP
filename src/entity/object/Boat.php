<?php

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\item\BoatType;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\particle\BubbleParticle;
use function deg2rad;
use function sin;
use function cos;

class Boat extends Entity{
	private BoatType $boatType;
	private ?Item $dropItem;

	public function __construct(Location $location, ?\pocketmine\nbt\tag\CompoundTag $nbt = null, BoatType $boatType = BoatType::OAK, ?Item $dropItem = null){
		$this->boatType = $boatType;
		$this->dropItem = $dropItem;
		parent::__construct($location, $nbt);
	}

	public static function getNetworkTypeId() : string{ return EntityIds::BOAT; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.5625, 1.375);
	}

	protected function getInitialDragMultiplier() : float{ return 0.01; }

	protected function getInitialGravity() : float{ return 0.03; }

	public function getName() : string{
		return "Boat";
	}

	public function getBoatType() : BoatType{ return $this->boatType; }

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		if($this->getPassenger() === null){
			return $this->addPassenger($player);
		}
		return false;
	}

	public function handleRiderInput(Player $rider, BitSet $flags) : void{
		$forward = $flags->get(PlayerAuthInputFlags::UP) ? 1 : ($flags->get(PlayerAuthInputFlags::DOWN) ? -1 : 0);
		$strafe = $flags->get(PlayerAuthInputFlags::LEFT) ? -1 : ($flags->get(PlayerAuthInputFlags::RIGHT) ? 1 : 0);
		if($forward === 0 && $strafe === 0){
			return;
		}

		$yaw = deg2rad($rider->getLocation()->yaw);
		$motion = new Vector3(
			(sin($yaw) * $forward + cos($yaw) * $strafe) * 0.35,
			$this->getMotion()->y,
			(-cos($yaw) * $forward + sin($yaw) * $strafe) * 0.35
		);
		$this->setMotion($motion);
	}

	public function onUpdate(int $currentTick) : bool{
		$block = $this->getWorld()->getBlock($this->location);
		if($block instanceof \pocketmine\block\Water){
			$this->setMotion($this->getMotion()->multiply(0.9)->add(0, 0.08, 0));
			if($currentTick % 10 === 0){
				$this->getWorld()->addParticle($this->location, new BubbleParticle());
			}
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
