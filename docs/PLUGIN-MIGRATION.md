# Plugin migration notes

This project continues the PocketMine-MP 5.x plugin API. The goal is that existing plugins keep working without changes when the functionality they use is supported.

## Current compatibility

- API version: 5.x.
- Namespaces, event classes, commands, permissions, tasks and inventory APIs are inherited from upstream 5.44.4.
- Composer-based plugins and PHAR plugins load as before.
- No breaking API changes have been introduced by the block additions.

## What changed

New block and item types were added. Plugins that enumerate vanilla blocks will see additional entries; plugins that switch on `BlockTypeIds` should handle unknown/new IDs defensively (this was already required for custom blocks).

New public block classes and enums include:

- `pocketmine\block\PotentSulfur`, `pocketmine\block\utils\PotentSulfurState`
- `pocketmine\block\Seagrass`, `pocketmine\block\utils\SeaGrassType`
- `pocketmine\block\Kelp`
- `pocketmine\block\Scaffolding`
- `pocketmine\block\SuspiciousBlock`
- `pocketmine\block\Composter`
- `pocketmine\block\LeafLitter`, `pocketmine\block\Wildflowers`
- `pocketmine\block\PointedDripstone`, `pocketmine\block\SulfurSpike`, `pocketmine\block\utils\DripstoneThickness`
- `pocketmine\block\BubbleColumn`
- `pocketmine\block\TurtleEgg`, `pocketmine\block\SnifferEgg`, `pocketmine\block\utils\TurtleEggCount`, `pocketmine\block\utils\CrackedState`
- `pocketmine\block\MangrovePropagule`
- `pocketmine\block\SculkCatalyst`, `pocketmine\block\SculkShrieker`, `pocketmine\block\SculkVein`, `pocketmine\block\SculkSensor`, `pocketmine\block\CalibratedSculkSensor`
- `pocketmine\block\Observer`, `pocketmine\block\Dispenser`, `pocketmine\block\Piston`, `pocketmine\block\StickyPiston`, `pocketmine\block\PistonArmCollision`
- `pocketmine\block\CommandBlock`, `pocketmine\block\StructureBlock`, `pocketmine\block\Jigsaw`
- `pocketmine\block\Vault`, `pocketmine\block\TrialSpawner`, `pocketmine\block\Crafter`, `pocketmine\block\CreakingHeart`
- `pocketmine\block\Grindstone`, `pocketmine\block\DecoratedPot`, `pocketmine\block\DriedGhast`, `pocketmine\block\CopperGolemStatue`
- `pocketmine\block\PaleMossCarpet`, `pocketmine\block\PaleHangingMoss`

They are additions, not replacements.

## Behavioural notes

- Technical blocks added in this project provide correct blockstate and persistence behaviour, but their redstone/gameplay systems may not be implemented yet. Do not assume pistons move, observers pulse, or vaults open.
- Blocks whose Bedrock states are unknown still deserialize to `UnknownBlock`; plugins should continue to handle that case.
- `SulfurSpike` extends `PointedDripstone`; both share the same state model.

## Testing a plugin

1. Build the server: `composer install --no-dev --classmap-authoritative && composer make-server`.
2. Put the plugin in `plugins/`.
3. Start the server and check console output and logs.
4. Report incompatibilities with the plugin name, version and stack trace.

## Policy

- No silent breaking changes to the 5.x API.
- Deprecations are documented before removal.
- New APIs are added alongside old ones where practical.
- Plugin-facing changes are listed in release notes.
