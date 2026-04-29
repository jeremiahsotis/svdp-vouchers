import os
import pathlib
import re
import subprocess
import sys

THRESHOLD = int(os.environ.get("SLICE_FILE_THRESHOLD", "12"))
changed = [c for c in subprocess.check_output(["git", "diff", "--name-only", "HEAD~1..HEAD"], text=True).splitlines() if c]

if len(changed) <= THRESHOLD:
    print("Slice size within threshold")
    sys.exit(0)

for sf in pathlib.Path("docs/roadmap").glob("v*/roadmap-state.md"):
    text = sf.read_text(errors="ignore")
    m = re.search(r"## Active Slice\s*\n([A-Z]\d+)", text)
    if not m:
        continue
    cp = pathlib.Path("specs/active") / f"slice-{m.group(1)}" / "codepack.md"
    if cp.exists():
        body = cp.read_text(errors="ignore")
        if "## 14. Slice Size Justification" in body:
            print("Slice size exceeds threshold but justification present")
            sys.exit(0)

print(f"Slice size exceeds threshold ({len(changed)} > {THRESHOLD}) without justification")
sys.exit(1)
