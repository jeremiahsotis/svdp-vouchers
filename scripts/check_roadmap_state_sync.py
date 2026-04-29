import pathlib
import re
import sys

for sf in pathlib.Path("docs/roadmap").glob("v*/roadmap-state.md"):
    text = sf.read_text(errors="ignore")
    for line in text.splitlines():
        m = re.match(r"\|\s*([^|]+?)\s*\|\s*Complete\s*\|", line)
        if not m or m.group(1).lower() == "slice":
            continue
        slice_id = m.group(1).strip()
        active = pathlib.Path("specs/active") / f"slice-{slice_id}"
        archived = list(pathlib.Path("specs/archive").glob(f"**/slice-{slice_id}"))
        if not active.exists() and not archived:
            print(f"Roadmap sync violation: {slice_id} marked complete but no slice folder found")
            sys.exit(1)

print("Roadmap state sync passed")
