<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_|  | | | | | |  __/_____| |  | |  __/
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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\PortalHelper;

class EndPortal extends Transparent{
	public function getLightLevel() : int{
		return 15;
	}

	protected function recalculateCollisionBoxes() : array{
		return [];
	}

	public function getDropsForCompatibleTool(\pocketmine\item\Item $item) : array{
		return [];
	}

	public function onEntityInside(Entity $entity) : bool{
		if($entity instanceof Player && $entity->isAlive()){
			PortalHelper::useEndPortal($entity);
		}
		return true;
	}
}
