# BDS asset tooling

Scripts for unpacking Minecraft: Bedrock Edition Dedicated Server reference
assets. See [docs/BDS-REFERENCE.md](../../docs/BDS-REFERENCE.md) for the file
format, measured contents and usage.

- `extract_brarchive.py` — unpack `.brarchive` containers into directories.
- `merge_packs.py` — layer the base vanilla pack and versioned overlays into an
  effective view for a target Bedrock version.

These scripts are development tools only. Extracted Mojang assets must stay
outside the repository.
