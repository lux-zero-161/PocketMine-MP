# Bedrock Dedicated Server reference assets

This directory contains the tooling used to unpack the official Minecraft: Bedrock
Edition Dedicated Server (BDS) assets for use as a **reference** when implementing
blocks, items, recipes, trading, entities and world generation.

The extracted assets are not part of this repository and are not redistributed.
Mojang's pack data remains subject to Mojang's terms; keep it outside version
control and use it only to derive facts (IDs, shapes, tables) for our own code.

## Analysed build

| Property | Value |
|---|---|
| Version | Bedrock 1.26.60 (compatibility pack `vanilla_1.26.66` shipped alongside) |
| Binary | `bedrock_server`, ELF 64-bit PIE, x86-64, stripped |
| Size | 265,503,224 bytes |
| SHA-256 | `dba89ef48dfd54b26ecaaff2554bfb0f06008a37a1645285c42d2fd62f409887` |
| Build ID | `5c51eac8c55341d7defb1b7cdd419a8be5f49a4c` |

Because the binary is stripped, symbol-based dumpers (such as the deprecated
`BedrockProtocolDumper`) cannot recover packet IDs from it. Version and content
data are recovered from the shipped packs and from string/RTTI data instead.

## `__brarchive` containers

BDS 1.26.x ships its vanilla pack JSON inside `__brarchive/*.brarchive` files.
The format was reverse-engineered from the 1.26.60 Linux build:

```
header (16 bytes, little endian)
    0x00  u32  magic (0xB125277D)
    0x04  u32  build hash (0x267052A0 for 1.26.60)
    0x08  u32  file count
    0x0c  u32  format version (1)

record (file count * 256 bytes)
    +0x000  u8    name length
    +0x001  char  name[239] (NUL padded)
    +0x0f0  ...   padding
    +0x0f8  u32   data offset, relative to the data blob
    +0x0fc  u32   data size

data blob
    starts immediately after the last record
```

`extract_brarchive.py` implements this and writes each member to a directory.
`merge_packs.py` then layers the base `vanilla` pack and every
`vanilla_<version>` overlay whose version is `<=` the requested target, matching
how BDS stacks packs. Only the extracted assets need to be present.

### Usage

```bash
# Unpack every container in a BDS installation (paths relative to the repo).
find ../bedrockserver -name '*.brarchive' -size +16c | while read -r archive; do
    out="../decompiled/bedrockserver/${archive#../bedrockserver/}"
    python3 build/bds/extract_brarchive.py "$archive" "${out%.brarchive}"
done

# Produce the effective 1.26.60 view (base + versioned overlays).
python3 build/bds/merge_packs.py ../decompiled/bedrockserver ../decompiled/bds-1.26.60 1.26.60
```

## What the 1.26.60 reference contains

Measured from the 1.26.60 installation:

| Asset | Count | Used for |
|---|---:|---|
| `__brarchive` containers | 546 | source containers |
| Extracted members | 9,543 | raw reference data |
| Effective recipes | 1,892 | shaped/shapeless/furnace/brewing/smithing tables |
| Effective loot tables | 309 | chest, block and entity loot |
| Effective villager trades | 27 tables | profession and wandering trader offers |
| Effective structures | 1,300 | `.mcstructure`/NBT structure templates |
| Effective entity definitions | 129 | behaviour components, spawn rules |
| Effective biomes | 89 | climate, surface builders, tags |
| Effective spawn rules | 60 | mob spawn conditions |
| Block shapes | 218 | selection/collision boxes (binary `MCB` format) |
| Block definitions | 98 | runtime block IDs, mining time, blast resistance, map colour, tags, loot and archetype |
| Worldgen features (pack) | 210 | behaviour pack `features/*.json` |
| Worldgen feature rules (pack) | 241 | placement passes, biome filters, distribution |
| Worldgen features (`definitions/`) | 139 | standalone client-side definitions, 59 overlapping the pack |
| Worldgen feature rules (`definitions/`) | 120 | standalone client-side definitions, 94 overlapping the pack |
| Raid spawn groups | 7 | raid wave compositions |

The effective view is a merge of the base pack plus versioned overlays. It is a
convenience for reading, not a claim that BDS loaded exactly those files in that
order.

## How this maps to the project

- **Block palette and item IDs** remain authoritative from
  `nethergamesmc/bedrock-data` (`vendor/`). The BDS packs do not contain the
  full runtime block palette or item registry.
- **Block definitions** cover the newer slab/stair/cushion/copper families and
  carry the runtime block ID, `destructible_by_mining`, explosion resistance, map
  colour, tags, loot table and block archetype. Use them to validate properties;
  they do not replace the full palette.
- **Recipes, loot tables, trades and worldgen features** are the useful payload.
  They are the reference for replacing simplified hand-written tables with
  data-driven implementations.
- **Structures** can be imported with the existing `.mcstructure` support to
  validate structure generation.
- **Shapes** are stored in a binary format and have not been converted yet; the
  block properties table from `bedrock-data` remains authoritative for collision
  and selection boxes for now.

## Reference folders in this workspace

Paths below are relative to the repository root (`PocketMine-MP/`).

| Path | Contents |
|---|---|
| `../bedrockserver/` | Unmodified BDS 1.26.60 installation |
| `../bedrockserver/definitions/` | Client-side feature, feature-rule, attachable and spawn-group JSON |
| `../decompiled/bedrockserver/` | Unpacked containers, one directory per archive |
| `../decompiled/bds-1.26.60/` | Effective 1.26.60 behaviour and resource pack view |
| `../tool/BedrockData/` | Older 1.26.30 `bedrock-data` snapshot, kept for comparison only |
