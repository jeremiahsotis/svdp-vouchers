import pathlib
import re
import sys

status_file = pathlib.Path("docs/roadmap/version-status.md")
if not status_file.exists():
    print("No version-status.md found; skipping")
    sys.exit(0)

text = status_file.read_text(errors="ignore")
if "## Status\nFeature Freeze" not in text.replace("\r\n", "\n"):
    print("Feature freeze inactive")
    sys.exit(0)

for sf in pathlib.Path("docs/roadmap").glob("v*/roadmap-state.md"):
    state = sf.read_text(errors="ignore")
    m = re.search(r"## Active Slice\s*\n([A-Z]\d+)", state)
    if m:
        active = m.group(1)
        if active[:1] not in {"B", "H", "S"}:
            print(f"Feature freeze violation: active slice {active} is not B/H/S")
            sys.exit(1)

print("Feature freeze check passed")
