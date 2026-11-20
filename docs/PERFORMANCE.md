# Performance and startup

This document summarizes the current performance work and the audits used to verify it.

## Baseline audits

```bash
php build/audit/performance.php
php build/audit/soak.php --iterations=5
php build/audit/worldgen.php
```

Typical results on a development machine:

| Audit | Result |
|---|---:|
| Registry cold start | ~0.13 s |
| Block state serialize/deserialize | ~300k ops/s |
| Item NBT serialize/deserialize | ~100k ops/s |
| Vanilla block/item lookups | >1M ops/s |
| Worldgen (in-memory) | ~40 chunks/s |
| Soak test | 0 errors, 0 MiB growth |

## Recent optimizations

- **Sculk vibration scans** now compare raw block state IDs from chunk storage before creating Block objects.
  Previously every vibration created Block objects for up to 4,913 nearby positions; now only actual
  sculk sensors/shriekers are instantiated.
- **Natural mob spawning** counts nearby mobs once per player per tick instead of once per spawn attempt.
- **Chunk caches** are dimension-aware, so Nether/End chunks serialize with the correct subchunk bounds.
- **Dimension IDs are cached per world** instead of re-reading and lowercasing the generator name for every chunk request.
- **Chunk serialization reuses the already-computed subchunk count**, avoiding a second scan of the chunk for every outgoing chunk packet.
- **Structure export/import** uses a palette and a single block-state lookup per unique state.
- **Map rendering** is throttled to once per 20 ticks and reuses map data between requests.

## PHP version

The project now requires **PHP 8.3 or newer** and is tested with PHP 8.4.
This allows modern language features and ensures compatibility with maintained PHP binaries.

## Notes

- Chunk packets are prepared asynchronously per chunk and compressed once per compressor/protocol; repeated requests hit the chunk cache.
- Enabling OPcache and JIT remains recommended for production.
- Long-running multi-player load tests are still on the roadmap.
