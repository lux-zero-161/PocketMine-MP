# PocketMine-MP Community Successor

**A community-driven continuation of PocketMine-MP — maintaining its legacy and expanding Minecraft: Bedrock Edition server capabilities.**

PocketMine-MP is a highly customisable, open-source server software for Minecraft: Bedrock Edition, written in PHP. It has powered custom Bedrock servers, minigames and plugin-driven gamemodes for over a decade.

In 2025 the upstream PocketMine-MP team announced the end of support: no one was left to update the project for new Minecraft versions. This repository continues from the last upstream release, now rebased onto the maintained NetherGames 5.51.2 base (Bedrock 1.26.60 / protocol 2225, compatible with 26.60–26.65 clients) as an actively maintained successor. It is **not** an official PocketMine-MP release and is not affiliated with the upstream team or Mojang.

- PHP 8.2+ (64-bit), PHP 8.4 recommended
- Bedrock 1.26.60 / protocol 2225 (26.65 clients use the same protocol)
- `ext-webrtc` for NetherNet (included in axolotl-pm PHP builds)
- LGPL-3.0

---

## Why this project exists

The original PocketMine-MP development has stopped. New Bedrock versions, blocks, items and protocol changes will no longer be handled upstream. Server owners and plugin developers need a maintained codebase that keeps working with current clients.

This project exists to fill that gap:

- continue protocol and data updates for new Bedrock releases;
- close the block, item and gameplay compatibility gap with the official Bedrock Dedicated Server;
- modernise the internals without breaking the plugin ecosystem;
- keep the project documented, tested and buildable.

This is a continuation of PocketMine-MP's legacy, not merely a maintenance fork. The objective is to make it better, more complete and sustainable.

## Project status

The project is under active development. Everything below is measured against the Bedrock 1.26.60 canonical block palette shipped in `nethergamesmc/bedrock-data`.

| Area | Status |
|---|---|
| Bedrock protocol 1.26.60 (2225), 26.65-compatible | Working (NetherGames 5.51.2 base, NetherNet + RakNet) |
| Block type names registered | **1,480 / 1,480** |
| Creative inventory items represented | **1,766 / 1,766** (some entity-backed items are item-only until their mobs/entities exist) |
| Sulfur & cinnabar families | Complete (27 block types / 33 Bedrock block names, including `potent_sulfur` states) |
| Sculk family | Catalyst, shrieker, vein, sensor, calibrated sensor implemented |
| Technical blocks | Observer, dispenser, dropper, pistons, command/structure/jigsaw, vault, trial spawner implemented as stateful blocks |
| Decorative 1.21–1.26 blocks | Bush, firefly bush, dry grass, leaf litter, wildflowers, pale moss, pale hanging moss, moss carpet, copper golem statues, etc. |
| Block entities for new blocks | Shelves, copper chests, beehives and decorated pots implemented as tile-backed blocks; multi-shelf hotbar swapping and bee release behaviour still open |
| Vanilla world generation | Not achieved. The built-in generator is PocketMine-MP's simplified noise generator |
| Nether / End dimensions | Nether generator exists; End generation and vanilla-style dimension travel are not implemented |
| Redstone, mob AI, vanilla survival mechanics | Basic redstone (wire, torches, levers, buttons, repeaters, comparators, lamps, observers, dispensers/droppers, pistons) implemented; sculk sensing and full vanilla parity are open |

Every canonical 1.26.60 block type name and every creative inventory item is now represented, including the poplar wood set, shelves, copper chests, beehives, ice crystals/icicles, disc press and straw bed. Remaining work is behavioural rather than registry coverage, and is tracked in [ROADMAP.md](ROADMAP.md).

A status breakdown with an estimate of remaining work is in [docs/STATUS.md](docs/STATUS.md).

A live audit can be run with:

```bash
php build/audit/block-coverage.php
php build/audit/item-coverage.php --missing
php build/audit/performance.php
php build/audit/soak.php --iterations=5
php build/audit/worldgen.php
```

The block audit reports canonical block type and state coverage; the item audit compares the Bedrock creative inventory against the block and item registries. The performance audit measures registry startup and block/item (de)serialization throughput; the soak audit repeats those cycles and checks for serialization errors and memory growth; the worldgen audit generates a small chunk area in memory.

### What "implemented" means here

A block is considered implemented when:

- its Bedrock block type names and all canonical state permutations are registered;
- state data round-trips through the Bedrock blockstate serializer/deserializer;
- block properties (hardness, blast resistance, light, flammability, collision, support) match the vanilla data;
- it can be placed, broken, saved and loaded, and its item form exists where Bedrock has one.

Gameplay systems layered on top of blocks (redstone propagation, piston animation details, sculk sensing, archaeology, bee AI) are tracked separately in the roadmap. No block is registered as an empty stub: unknown or unimplemented Bedrock states still fall back to the existing `UnknownBlock` mechanism rather than silently becoming an incorrect vanilla block.
## Feature overview

- **Plugin API** — stable PocketMine-MP 5.x API, Composer-based plugins, event system, commands, forms, permissions, tasks and async workers.
- **Block & item registry** — generated vanilla registries from official Bedrock data, full blockstate serialization for implemented blocks.
- **World system** — LevelDB and region world formats, chunk loading/unloading, lighting, biomes, block entities, multiple worlds.
- **Networking** — RakNet transport, current Bedrock protocol, login/authentication, resource packs, skins, forms.
- **Entities & gameplay** — players, items, projectiles, basic mobs, inventories, crafting, enchantments, effects, farming primitives, weather and time.
- **Tooling** — PHPStan level 9, PHPUnit suite, code generation for registries and Bedrock data constants, CI workflows.

## Bedrock compatibility goals

1. **Blocks and items** — reach full coverage of the canonical Bedrock palette, including tile-backed blocks.
2. **Worlds** — Bedrock-compatible storage is already supported; generation parity with Bedrock Dedicated Server is the long-term goal.
3. **Dimensions** — complete Overworld, Nether and End generation and portal travel.
4. **Gameplay** — redstone, fluids, projectiles, combat, farming, breeding and block interactions at vanilla parity where practical.
5. **Protocol** — current Bedrock versions, with modular updates for new releases.
6. **Performance** — measured improvements, not claims. Benchmarks and profiling belong in the repository, not the README.

## Requirements

- 64-bit PHP 8.1 or newer (PHP 8.2+ recommended; the build guide assumes 8.2+)
- Required extensions: `chunkutils2`, `crypto`, `ctype`, `curl`, `date`, `encoding`, `gmp`, `hash`, `igbinary`, `json`, `leveldb`, `mbstring`, `morton`, `openssl`, `pcre`, `phar`, `pmmpthread`, `reflection`, `simplexml`, `sockets`, `spl`, `yaml`, `zip`, `zlib`
- Composer 2
- A bash shell for building (Git Bash works on Windows)

PMMP publishes prebuilt PHP binaries with all required extensions:
[pmmp/PHP-Binaries releases](https://github.com/pmmp/PHP-Binaries/releases).

## Installation

### From source

NetherNet (required by 26.60+ clients) needs `ext-webrtc`; the axolotl-pm prebuilt PHP binaries include it. Plain pmmp binaries do not.

```bash
git clone <this-repository> PocketMine-MP
cd PocketMine-MP
composer install
php src/PocketMine.php
```

Open the game port for **both TCP and UDP** (NetherNet signaling uses TCP on `server-port`). On first start the server creates `server.properties`, `pocketmine.yml` and the `worlds/` directory. Edit `server.properties` before inviting players.

### Build a distributable PHAR

```bash
composer install --no-dev --classmap-authoritative
composer make-server
php PocketMine-MP.phar
```

`composer make-server` produces `PocketMine-MP.phar` in the repository root. See [BUILDING.md](BUILDING.md) for custom PHP binaries and platform notes.

### Docker

The upstream Docker image is not maintained by this project. Build from the included `docker/` directory if you need a container, and pin the PHP binary version you test with.

## Development setup

```bash
composer install
composer update-codegen        # regenerate registries from bedrock-data after data bumps
vendor/bin/phpunit --bootstrap vendor/autoload.php tests/phpunit
vendor/bin/phpstan analyse
```

Useful entry points:

- `src/block/` — block implementations and `VanillaBlocksInputs`
- `src/data/bedrock/block/convert/` — Bedrock blockstate mappings
- `src/item/` — items, `StringToItemParser`, item type IDs
- `src/world/` — worlds, formats, generators
- `tests/phpunit/` — unit tests; `tests/plugins/` — server-level test plugins

When adding a block:

1. add the type ID to `src/block/BlockTypeIds.php`;
2. register it in `src/block/VanillaBlocksInputs.php`;
3. add serializer/deserializer mappings in `VanillaBlockMappings.php`;
4. regenerate `generated/block/VanillaBlocks.php`:

   ```bash
   php build/codegen/registry-interface.php src generated
   ```

5. regenerate the block factory consistency table:

   ```bash
   php tests/phpunit/block/regenerate_consistency_check.php
   ```

6. add the item alias in `src/item/StringToItemParser.php`;
7. run PHPUnit and PHPStan.

`BlockTest` verifies hardness/blast resistance against the vanilla properties table, `BlockSerializerDeserializerTest` round-trips every registered state, and `BlockCoverageTest` fails if any canonical Bedrock block type name is missing.

## Plugin development

The plugin API is compatible with PocketMine-MP 5.x. Existing plugins should continue to work when the functionality they use is supported.

- API version: 5.x (`src/plugin/ApiVersion.php`)
- Plugins are Composer packages or PHARs loaded from `plugins/`
- Documentation: [pmmp docs](https://doc.pmmp.io/) (upstream API documentation remains valid for the inherited API)
- Plugin repository: [Poggit](https://poggit.pmmp.io/plugins)
- Migration notes: [docs/PLUGIN-MIGRATION.md](docs/PLUGIN-MIGRATION.md)

## Contributing

Contributions are welcome: bug fixes, block implementations, protocol updates, tests, documentation and profiling results.

- Read [CONTRIBUTING.md](CONTRIBUTING.md) for coding standards and PR expectations.
- Keep changes focused and include tests where behaviour changes.
- Do not add placeholder implementations; a block or feature is either implemented or tracked in the roadmap.
- Report security issues privately according to [SECURITY.md](SECURITY.md).

## Roadmap

See [ROADMAP.md](ROADMAP.md) for the current milestone list, remaining block work and long-term goals.

## License

PocketMine-MP is licensed under the GNU Lesser General Public License v3.0. See [LICENSE](LICENSE).

PocketMine-MP and this community successor are not affiliated with Mojang or Microsoft. Minecraft is a trademark of Mojang Synergies AB. All brands and trademarks belong to their respective owners.
