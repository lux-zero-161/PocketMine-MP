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

namespace pocketmine\block\utils;

use pocketmine\utils\LegacyEnumShimTrait;

/**
 * TODO: These tags need to be removed once we get rid of LegacyEnumShimTrait (PM6)
 *  These are retained for backwards compatibility only.
 *
 * @method static CrafterOrientation DOWN_EAST()
 * @method static CrafterOrientation DOWN_NORTH()
 * @method static CrafterOrientation DOWN_SOUTH()
 * @method static CrafterOrientation DOWN_WEST()
 * @method static CrafterOrientation EAST_UP()
 * @method static CrafterOrientation NORTH_UP()
 * @method static CrafterOrientation SOUTH_UP()
 * @method static CrafterOrientation UP_EAST()
 * @method static CrafterOrientation UP_NORTH()
 * @method static CrafterOrientation UP_SOUTH()
 * @method static CrafterOrientation UP_WEST()
 * @method static CrafterOrientation WEST_UP()
 */
enum CrafterOrientation{
	use LegacyEnumShimTrait;

	case DOWN_EAST;
	case DOWN_NORTH;
	case DOWN_SOUTH;
	case DOWN_WEST;
	case UP_EAST;
	case UP_NORTH;
	case UP_SOUTH;
	case UP_WEST;
	case WEST_UP;
	case EAST_UP;
	case NORTH_UP;
	case SOUTH_UP;
}
