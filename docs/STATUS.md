# Project status

Last updated: 2026-11-20

This file is an honest, estimate-based snapshot. It separates **measured coverage** from
**estimated vanilla parity**, because a server can represent every block and item while still
lacking deep gameplay systems.

## Measured coverage

| Metric | Result | Source |
|---|---:|---|
| Canonical Bedrock block type names | **1,480 / 1,480 (100%)** | `build/audit/block-coverage.php` |
| Creative inventory items | **1,766 / 1,766 (100%)** | `build/audit/item-coverage.php` |
| Registered canonical blockstates | **20,651 / 23,960 (86%)** | block audit |
| PHPUnit | **211 tests, 130,392 assertions, all green** | `vendor/bin/phpunit` |
| Soak test | **0 errors, 0 MiB growth** | `build/audit/soak.php --iterations=2` |
| Performance baseline | ~300k block (de)serializations/s, ~100k item NBT ops/s | `build/audit/performance.php` |
| PHPStan | green except 2 missing `ext-webrtc` symbols in the local test PHP | `vendor/bin/phpstan` |

The blockstate number is reported separately on purpose. Many missing permutations are legacy or
invalid Bedrock combinations (for example redundant hanging-sign layouts), while all block types
and all states used by the implemented blocks round-trip through the serializer.

## Estimated completion

| View | Estimate | Notes |
|---|---:|---|
| **Roadmap checklist items addressed** | **~99%** | Almost every listed subsystem has at least a working implementation. |
| **Vanilla gameplay parity** | **~79%** | Many systems are simplified compared with vanilla Bedrock. |

### Estimated parity by area

| Area | Estimate | Notes |
|---|---:|---|
| Block registry / data | 100% | Every canonical block type name is registered. |
| Item registry / data | 100% | Every creative item is representable and serializable. |
| Block behaviour | ~82% | Most utility, technical and redstone blocks work; some container UIs and edge cases remain simplified. |
| Redstone | ~92% | Wire, torches, repeaters, comparators, lamps, observers, targets, pistons, dispensers/droppers, hoppers, crafters, vaults, trial spawners, sculk sensing and command blocks work. Exact update ordering is simplified. |
| Containers / automation | ~91% | Chests, barrels, hoppers, furnaces, brewing, enchanting, anvils, smithing, stonecutter, loom, cartography, crafter, bundles, chest minecarts/boats. Native grindstone/trading windows remain open; a form-based grindstone is available. |
| Mobs | ~71% | Basic AI, A* pathfinding, breeding, natural spawning, ranged attacks, taming, villager trades and bosses. No full vanilla pathfinding, herds, fear propagation or special attack parity. |
| World generation | ~41% | Noise terrain, caves, simplified aquifers, ores, trees, kelp/seagrass, Nether decoration, End islands, simple villages and mineshaft segments. No full vanilla biome/structures/aquifer parity. |
| Dimensions | ~75% | Overworld/Nether/End, portals, exit portal, gateways and dimension-aware chunks. Dragon fight phases and End content are simplified. |
| Networking / protocol | ~95% | Protocol 2225, RakNet, NetherNet and packet coverage inherited from the maintained base. |
| Plugin API | ~95% | PocketMine-MP 5.x API preserved; new block/item types are exposed. |
| Testing / performance | ~70% | Strong registry/serialization tests plus quick performance and soak audits. No long-running multi-player load test yet. |

## Recently completed

- 100% coverage of canonical block type names and the Bedrock creative inventory.
- Redstone, pistons, observers, hoppers, crafters, vaults and trial spawners.
- Command blocks, structure blocks with editor UI, structure save/load NBT format, Bedrock `.mcstructure` import/export and `/structure`.
- Jigsaw blocks with recursive piece expansion and `/jigsaw`.
- Sculk vibration frequencies, calibrated sensors, shriekers and Warden summoning.
- Boats, minecarts (normal/chest/hopper/TNT), rider links and entity inventories.
- Fishing, archaeology, bundles (storage + Bedrock insert/take actions).
- Weather cycles, rain extinguishing fire, cauldrons filling with rain, lightning strikes.
- Caves, simplified aquifers, Nether decoration, End generator, kelp/seagrass.
- Maps, map rendering and persistent `maps.nbt` storage.
- Wither and Ender Dragon bosses with boss bars; wither armour phase; dragon phases,
  exit portal, dragon egg, End gateways and crystal healing.
- Varied mob behaviour: A* chase, fleeing, herds, creeper explosions, enderman teleport,
  chicken eggs, milking, shearing, dyeing, taming, ranged skeletons/pillagers, golems,
  witches, blazes and ghasts.
- Natural mob spawning (land and water), deterministic villages with farms/lamps, villagers, iron golem defenders and natural raids.
- Overworld mineshaft segments with supports, rails, cobwebs and loot chests.
- Bad Omen from pillager kills and village raid triggering; Hero of the Village after a win.
- Form-based villager trade UI and grindstone UI.
- Bad Omen / Hero of the Village effects registered.
- `/raid`, `/summon`, `/structure`, `/jigsaw` commands.
- Performance and soak audit scripts.

## Known gaps / highest-value remaining work

1. Native Bedrock villager trade window (`UpdateTradePacket` + trade inventory requests).
2. Full vanilla raid mechanics, village POIs, villager professions/workstations and restocking.
3. Full vanilla world generation: structures, aquifers, biome variation, strongholds and End cities.
4. Native grindstone container window and remaining container/transaction edge cases.
5. Long-running multi-player soak/load tests and profiling on real hardware.
6. Remaining End content (dragon AI parity, gateways with exact coordinate rules, shulkers/end cities).
7. Full `.mcstructure` block-entity/tick-queue import/export.
8. More exact redstone update ordering and block-specific edge cases.

## Verification commands

```bash
php build/audit/block-coverage.php
php build/audit/item-coverage.php
php build/audit/performance.php
php build/audit/soak.php --iterations=5
vendor/bin/phpunit --bootstrap vendor/autoload.php tests/phpunit
vendor/bin/phpstan analyse --no-progress --memory-limit=2G
```

## Notes

- Vendor data is Bedrock 1.26.60 / protocol 2225 and is authoritative for integration.
- The reference folder `bedrockServer/` is Bedrock 1.26.30 and is only used for comparisons.
- Estimates are deliberately conservative. "100% content coverage" does **not** mean
  "100% vanilla gameplay parity".
