#!/usr/bin/env python3
import argparse
import pathlib
import shutil

parser = argparse.ArgumentParser(description="Generate a slice skeleton from a feature intake entry")
parser.add_argument("--slice-id", required=True)
parser.add_argument("--target", default="specs/active")
args = parser.parse_args()

src = pathlib.Path("specs/slice-template")
dst = pathlib.Path(args.target) / f"slice-{args.slice_id}"
dst.mkdir(parents=True, exist_ok=True)

for name in ["implementation-brief.md", "codepack.md", "checkpoint-01.md", "bootstrap.md"]:
    shutil.copyfile(src / name, dst / name)

print(f"Generated slice skeleton at {dst}")
print("Reminder: human approval is required before adding the slice to roadmap and dependency graph.")
