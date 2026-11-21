#!/usr/bin/env python3
"""Layer the extracted BDS vanilla packs into an effective view.

Bedrock Dedicated Server ships one base ``vanilla`` pack plus a series of
``vanilla_<version>`` overlay packs. The engine selects and stacks the
overlays that are compatible with its own version, so a file in
``vanilla_1.26.60`` replaces the same path from ``vanilla``.

This script applies every overlay whose manifest ``min_engine_version`` is
less than or equal to the selected target version, in ascending version
order, and writes the resulting tree to the output directory.

Usage: build/bds/merge_packs.py <extracted-bds-dir> <output-dir> [target-version]

target-version defaults to 1.26.60 (the version of the analysed binary).
"""
import json
import os
import re
import shutil
import sys

VERSION_RE = re.compile(r"^vanilla_(\d+(?:\.\d+)*)$")


def parse_version(text):
    return tuple(int(part) for part in text.split("."))


def pack_version(path):
    manifest = os.path.join(path, "manifest.json")
    try:
        with open(manifest, "r", encoding="utf-8") as fh:
            data = json.load(fh)
        return tuple(data["header"]["version"])
    except (OSError, ValueError, KeyError):
        return None


def main():
    if len(sys.argv) < 3:
        sys.exit("usage: merge_packs.py <extracted-bds-dir> <output-dir> [target-version]")
    source = sys.argv[1]
    target = sys.argv[2]
    target_version = parse_version(sys.argv[3]) if len(sys.argv) > 3 else (1, 26, 60)
    if os.path.isdir(target):
        shutil.rmtree(target)
    for kind in ("behavior_packs", "resource_packs"):
        root = os.path.join(source, kind)
        if not os.path.isdir(root):
            continue
        packs = []
        for name in os.listdir(root):
            path = os.path.join(root, name)
            if not os.path.isdir(path):
                continue
            match = VERSION_RE.match(name)
            if name == "vanilla":
                version = (0, 0, 0)
            elif match:
                version = parse_version(match.group(1))
            else:
                continue
            if version > target_version:
                continue
            packs.append((version, path))
        packs.sort()
        applied = 0
        for version, path in packs:
            if not os.path.isdir(path):
                continue
            for base, _dirs, files in os.walk(path):
                rel = os.path.relpath(base, path)
                # Extracted .brarchive payloads live below __brarchive; keep
                # that directory name out of the merged tree.
                if rel == "__brarchive":
                    rel = ""
                elif rel.startswith("__brarchive" + os.sep):
                    rel = rel[len("__brarchive" + os.sep):]
                destination = os.path.join(target, kind, rel)
                os.makedirs(destination, exist_ok=True)
                for name in files:
                    if name.endswith(".brarchive"):
                        continue
                    shutil.copy2(os.path.join(base, name), os.path.join(destination, name))
                    applied += 1
            print(f"{kind}: layered {'.'.join(map(str, version))} ({os.path.basename(path)})")
        print(f"{kind}: {applied} files -> {os.path.join(target, kind)}")


if __name__ == "__main__":
    main()
