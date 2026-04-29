import pathlib
import re
import sys

required = {"implementation-brief.md", "codepack.md", "bootstrap.md"}
for sf in pathlib.Path("docs/roadmap").glob("v*/roadmap-state.md"):
    text = sf.read_text(errors="ignore")
    for line in text.splitlines():
        m = re.match(r"\|\s*([^|]+?)\s*\|\s*Complete\s*\|", line)
        if not m or m.group(1).lower() == "slice":
            continue
        slice_id = m.group(1).strip()
        candidates = [pathlib.Path("specs/active") / f"slice-{slice_id}"] + list(pathlib.Path("specs/archive").glob(f"**/slice-{slice_id}"))
        found = next((c for c in candidates if c.exists()), None)
        if not found:
            print(f"Slice completion integrity failed: missing slice folder for {slice_id}")
            sys.exit(1)
        names = {p.name for p in found.iterdir() if p.is_file()}
        missing = required - names
        if missing:
            print(f"Slice completion integrity failed: {slice_id} missing {sorted(missing)}")
            sys.exit(1)

print("Slice completion integrity passed")
