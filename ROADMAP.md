# Roadmap

Status snapshot: Bedrock 1.26.30 (protocol 1001), based on upstream PocketMine-MP 5.44.4.

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

Coverage: **1,334 of 1,356** canonical block type names.

**Active**

- 8 wooden shelves — block entity with 4 item slots, comparator output and powered state.
- 6 copper chests — container block entity with oxidation/wax variants; decide on chest pairing semantics.
- `bee_nest`, `beehive` — bee block entity, honey level and release behaviour.

**Planned**

- Block behaviour parity for the newly added technical blocks: redstone updates, piston movement, sculk sensing, vault/trial spawner logic, crafter automation, command execution.
- Archaeology for suspicious sand/gravel.
- Powder snow entity interaction (sinking, freezing, leather boots).
- Scaffolding stability calculation and climbing physics.
- Kelp/seagrass growth and fluid interactions.

### 1.2 Items

**Done**

- Item registry and Bedrock item serialization inherited from upstream.
- Aliases for all newly added block items.

**Active**

- Verify new block items against the creative inventory and crafting data.
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

- Redstone: wire, torches, repeaters, comparators exist; pistons, observers, dispensers and droppers now have state models but no update logic.
- Fluids: basic water/lava flow exists; waterlogging and fluid containers need work.
- Mobs: basic entity framework; vanilla AI, spawning rules and breeding are incomplete.
- Combat: damage, armor and enchantments exist; knockback, shields and attack cooldowns need parity work.
- Farming: crops exist; growth, bone meal and composting interactions are partial.
- Portals: Nether portal blocks exist; travel and coordinate scaling are not vanilla-complete.

## Milestone 4 — Networking and protocol

**Done**

- RakNet transport, current protocol (1001), login/authentication, resource packs, skins, forms.

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
