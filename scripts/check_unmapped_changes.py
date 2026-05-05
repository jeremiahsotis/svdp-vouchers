import pathlib
import re
import subprocess
import sys

changed = set(
    c
    for c in subprocess.check_output(
        ["git", "diff", "--name-only", "HEAD~1..HEAD"], text=True
    ).splitlines()
    if c
)
allowed_prefixes = (
    ".codespellrc",
    ".github/",
    "config/",
    "contracts/",
    "docs/",
    "planning/",
    "scripts/",
    "specs/",
)
mapped = set()

for sf in pathlib.Path("docs/roadmap").glob("v*/roadmap-state.md"):
    text = sf.read_text(errors="ignore")
    m = re.search(r"## Active Slice\s*\n([A-Z]\d+)", text)
    if not m:
        continue
    cp = pathlib.Path("specs/active") / f"slice-{m.group(1)}" / "codepack.md"
    if cp.exists():
        body = cp.read_text(errors="ignore")
        for line in body.splitlines():
            line = line.strip("- ").strip()
            if not line.startswith("##") and (
                "/" in line or re.match(r"^[A-Za-z0-9_.-]+\.[A-Za-z0-9]+$", line)
            ):
                mapped.add(line)

unmapped = [
    c for c in changed if c not in mapped and not c.startswith(allowed_prefixes)
]
if unmapped:
    print("Unmapped changes detected:")
    for u in unmapped:
        print("-", u)
    sys.exit(1)

print("Unmapped change check passed")
