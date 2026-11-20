# Roadmap

Status snapshot: Bedrock 1.26.60 (protocol 2225), based on NetherGames PocketMine-MP 5.51.2. 26.65 clients use the same protocol. NetherNet is enabled alongside RakNet.

Legend: **done** — implemented and covered by tests; **active** — partially implemented, needs the listed work; **planned** — not started.

## Milestone 1 — Block and item coverage

### 1.1 Canonical block registry

**Done**

- Canonical Bedrock block palette wired through `pocketmine/bedrock-data`.
- Blockstate serializer/deserializer round-trip for every registered state.
- Sulfur and cinnabar building families (27 block types + double slabs), including the five `potent_sulfur` states.
- Flowering/decorative blocks added for 1.21–1.26: bush, firefly bush, short/tall dry grass, leaf litter, wildflowers, pale moss block, pale moss carpet, pale hanging moss, moss block, moss carpet, golden dandelion, open/closed eyeblossom, frog spawn.
- Utility blocks: honey block, dripstone block, moss block, lodestone, target, conduit, heavy core, scaffolding, composter, seagrass, kelp, suspicious sand/gravel, pointed dripstone, sulfur spike, bubble column, turtle egg, sniffer egg, mangrove propagule.
- Technical blocks with full state models: observer, dispenser, dropper, piston/sticky piston and arm collision blocks, moving block, command blocks, structure block, jigsaw, vault, trial spawner, crafter, creaking heart, decorated pot, grindstone, sculk sensor/calibrated sensor/shrieker/catalyst/vein, border/allow/deny blocks, chalkboard, camera, end portal/gateway, powder snow, unknown/client placeholder blocks.
- Cherry and pale oak saplings, with matching tree generators.
- 8 wooden shelves — block entity with 3 item slots, item insert/remove, powered state and comparator output.
- 8 copper chests — container block entity with oxidation/wax variants and same-variant chest pairing.
- `bee_nest`, `beehive` — bee block entity with NBT storage, honey level and harvest interaction.
- 1.26.60 content: poplar wood set, ice crystal/icicle, disc press, straw bed, red shrub, shelf mushroom and poplar leaves variants.
- Straw beds are placed as two halves and support sleeping/spawn setting like regular beds.
- Shelf mushrooms attach to walls, can be grown with bone meal and bounce entities.
- Pistons and sticky pistons move up to 12 blocks, place piston arm blocks and use a PistonArm block entity.
- End portal frames accept eyes of ender and activate the 3x3 portal when all 12 frames are filled.
- Nether portals can be lit with flint and steel or fire charges on a valid obsidian frame (dimension travel is still missing).
- Composters accept compostable items with vanilla chances, produce bone meal and expose their fill level to comparators.
- Hoppers can insert compostable items into composters and pull finished bone meal out.
- Hoppers can insert music discs into jukeboxes and pull them back out.
- Decorated pots act as single-slot containers, support hopper input/output and drop their contents when broken.
- Sponges absorb nearby water and turn into wet sponges.
- Powder snow slows and freezes entities without leather boots, and can be collected/filled with buckets.

Coverage: **1,480 of 1,480** canonical block type names (100%).

**Active**

- Shelves — multi-shelf powered connections and hotbar swapping.
- Copper chests — container block entity with oxidation/wax variants, same-variant chest pairing and waxing/scraping support.
- `bee_nest`, `beehive` — bee entity release/entry and honey production.
- Redstone — wire propagation, torch inversion, repeater delay/locking, comparator analog output and piston movement are implemented; sculk sensing, exact piston animation/update ordering and observer/dispenser edge cases remain.

**Planned**

- Block behaviour parity for the newly added technical blocks: full sculk vibration frequencies and command execution.
- Scaffolding climbing physics (stability is now calculated and unsupported scaffolding breaks).
- Seagrass and full fluid interaction behaviour (kelp already grows upward through water).

### 1.2 Items

**Done**

- Item registry and Bedrock item serialization inherited from upstream.
- Aliases for all newly added block items.
- Poplar boat item, including BoatType and Bedrock item serialization.
- Pottery sherd and banner pattern items, plus the bolt and flow armor trim smithing templates.
- 1.26.60 cushion and harness items in all 16 colors.
- Cherry and pale oak boats, plus horse, nautilus and wolf armor items.
- Powder snow bucket and powder snow cauldron, including bucket pickup from powder snow blocks.
- Lead, saddle, armadillo scute, breeze rod, trial keys, eye of ender, brush, glow stick and sparkler items.
- 1.26.60 Bounce and Tears music discs, including jukebox comparator outputs.
- Carrot on a Stick and Warped Fungus on a Stick with correct durability.
- 1.26.60 blue and brown egg items.
- Bamboo raft item and BoatType support.
- All 1,766 creative-inventory items are representable, including chest boats, bundles, spears, shields, crossbows, elytra, mace, buckets, minecarts and spawn eggs.
- Armor stands are spawnable entities, accept armor and drop their equipment when destroyed.
- Spawn eggs spawn basic mob entities with simple movement; fish/mob buckets place water and spawn their mob.
- Shields block frontal hits, crossbows fire arrows, elytra enable gliding and maces deal fall-based smash damage.
- Crafters have a 3x3 inventory, redstone-triggered crafting, recipe matching and output ejection into containers/droppers.
- Vaults accept normal/ominous trial keys, track per-player usage and eject normal/ominous loot after the unlock animation.
- Trial spawners spawn simple mob waves when players are nearby and eject loot after three waves.
- Generated mobs have basic wandering/hostile movement, vanilla-style drops for common mobs and XP rewards.
- Generated mobs support basic breeding with vanilla breeding foods and baby scaling.
- Boats and chest boats are spawnable, rideable entities with basic water buoyancy and rider input; the generic rider/link system is implemented.
- Sculk sensors react to nearby block changes with a basic signal pulse; full vibration frequencies/ranges are still simplified.
- Minecarts (normal, chest, hopper, TNT) are spawnable, rideable entities with basic rail friction and rider input.
- Bundles store items in NBT (64 bundle slots) and can eject the last stored stack on use; full inventory UI insertion remains open.
- Archaeology: brushes progress suspicious sand/gravel through three stages and reveal loot.

**Active**

- Minecart/boat entities and bundle storage still need dedicated entity/item implementations.
- Fill item components for 1.26 content where Bedrock defines them.

## Milestone 2 — World system

**Done**

- LevelDB and region world formats.
- Chunk loading/unloading, lighting, biomes, block entities, multiple worlds.
- Flat and noise-based generators, Nether generator, tree/ore populators.

**Active**

- Bedrock-style Overworld terrain: biome placement, caves, aquifers, structures.
- End dimension generator and dimension travel.
- Structure generation (villages, temples, strongholds, ancient cities).

**Planned**

- World conversion improvements and round-trip tests against Bedrock Dedicated Server worlds.
- Deterministic world generation tests with golden chunk hashes.

## Milestone 3 — Gameplay

**Planned / active by system**

- Redstone: wire, torches, levers, buttons, repeaters (delay/locking), comparators (analog container output), lamps, observers, trapped chests, targets and dispensers/droppers have logic. Pistons, sculk sensing and full vanilla update ordering still need work.
- Fluids: basic water/lava flow exists; waterlogging and fluid containers need work.
- Mobs: basic entity framework; vanilla AI, spawning rules and breeding are incomplete.
- Combat: damage, armor and enchantments exist; knockback, shields and attack cooldowns need parity work.
- Farming: crops and composter interactions are implemented; crop growth and bone meal edge cases remain partial.
- Portals: Nether portal blocks exist; travel and coordinate scaling are not vanilla-complete.

## Milestone 4 — Networking and protocol

**Done**

- RakNet transport, NetherNet (TCP signaling + UDP game data), protocol 2225, login/authentication, resource packs, skins, forms.
- NetherNet is required by 26.60+ clients; the server needs the port open for TCP and UDP.

**Active**

- Keep protocol updates modular; document the update procedure in-repo.
- Add protocol regression tests for login and chunk streaming.

## Milestone 5 — Performance and stability

Policy: no performance claims without measurements.

**Active**

- Add reproducible benchmarks for tick time, chunk generation, blockstate serialization and packet encoding.
- Profile hot paths before changing them.
- Track memory per chunk and per player in CI where feasible.

**Planned**

- Reduce allocations in chunk serialization and entity ticking.
- Improve async chunk generation scheduling.
- Long-running soak tests.

## Milestone 6 — Plugin ecosystem

**Done**

- PocketMine-MP 5.x plugin API preserved.
- Composer compatibility, event system, command registration, permissions.

**Active**

- Document API differences and deprecations: [docs/PLUGIN-MIGRATION.md](docs/PLUGIN-MIGRATION.md).
- Run representative third-party plugins against the test server.

**Planned**

- Additional lifecycle and dependency diagnostics.
- API additions for new block/container types as they land.

## Milestone 7 — Maintenance

- Keep `pocketmine/bedrock-data`, `bedrock-protocol`, `raklib` and related dependencies current.
- Update the protocol and data constants with `composer update-codegen`.
- Maintain changelogs and release notes under `changelogs/`.
- Keep PHPStan level 9 and the PHPUnit suite green.
- Keep `README.md`, this roadmap and the plugin migration notes synchronized with reality.

## How to pick up work

1. Run `php build/audit/block-coverage.php` to see missing block type names and states.
2. Pick a block family with a clear data model; add the type ID, registration, mapping and tests.
3. For tile-backed blocks, implement the block entity first; do not register a block that silently discards inventory data.
4. Run PHPUnit and PHPStan before opening a change.
