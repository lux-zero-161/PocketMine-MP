#!/usr/bin/env python3
"""Extract Mojang Bedrock Dedicated Server .brarchive containers.

The 1.26.x BDS ships its vanilla resource/behaviour pack JSON files inside
``__brarchive/*.brarchive`` containers. Format (little endian, x86_64 build):

    header (16 bytes)
        0x00 u32  magic (0xb125277d)
        0x04 u32  build/hash
        0x08 u32  file count
        0x0c u32  format version (1)

    file records (file count * 256 bytes)
        +0x000 u8   name length
        +0x001 char name[239] (NUL padded)
        +0x0f0 ...  padding
        +0x0f8 u32  data offset (relative to data start)
        +0x0fc u32  data size

    data blob starts directly after the last record.

Usage: extract_brarchive.py <input.brarchive> <output-dir>
"""
import os
import struct
import sys

MAGIC = 0xB125277D
RECORD_SIZE = 0x100
DATA_META_OFFSET = 0xF8
NAME_OFFSET = 0x00
NAME_MAX = 0xEF


def parse(path):
    with open(path, "rb") as fh:
        blob = fh.read()
    if len(blob) < 16:
        raise ValueError("file too small")
    magic, build, count, version = struct.unpack_from("<IIII", blob, 0)
    if magic != MAGIC:
        raise ValueError(f"bad magic 0x{magic:08x}")
    if version != 1:
        raise ValueError(f"unsupported archive version {version}")
    records_start = 16
    data_start = records_start + count * RECORD_SIZE
    entries = []
    for index in range(count):
        base = records_start + index * RECORD_SIZE
        name_len = blob[base + NAME_OFFSET]
        if name_len > NAME_MAX:
            raise ValueError(f"record {index}: implausible name length {name_len}")
        name = blob[base + NAME_OFFSET + 1:base + NAME_OFFSET + 1 + name_len].decode("utf-8")
        offset, size = struct.unpack_from("<II", blob, base + DATA_META_OFFSET)
        data = blob[data_start + offset:data_start + offset + size]
        if len(data) != size:
            raise ValueError(f"record {index} ({name}): truncated data")
        entries.append((name, data))
    if data_start > len(blob):
        raise ValueError("record table exceeds file size")
    return build, entries


def main():
    if len(sys.argv) != 3:
        sys.exit("usage: extract_brarchive.py <input.brarchive> <output-dir>")
    src, out_dir = sys.argv[1], sys.argv[2]
    build, entries = parse(src)
    os.makedirs(out_dir, exist_ok=True)
    for name, data in entries:
        target = os.path.join(out_dir, name)
        parent = os.path.dirname(target)
        if parent:
            os.makedirs(parent, exist_ok=True)
        with open(target, "wb") as fh:
            fh.write(data)
    print(f"{src}: build=0x{build:08x} files={len(entries)} -> {out_dir}")


if __name__ == "__main__":
    main()
