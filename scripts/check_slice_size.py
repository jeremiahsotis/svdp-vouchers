import os
import pathlib
import re
import subprocess
import sys

THRESHOLD = int(os.environ.get("SLICE_FILE_THRESHOLD", "12"))


def changed_files():
    try:
        output = subprocess.check_output(
            ["git", "diff", "--name-only", "HEAD~1..HEAD"],
            stderr=subprocess.DEVNULL,
            text=True,
        )
    except subprocess.CalledProcessError:
        output = subprocess.check_output(
            ["git", "diff-tree", "--no-commit-id", "--name-only", "-r", "HEAD"],
            text=True,
        )

    return [c for c in output.splitlines() if c]


changed = changed_files()

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

for cp in pathlib.Path("specs/active").glob("slice-*/codepack.md"):
    body = cp.read_text(errors="ignore")
    if "## 14. Slice Size Justification" in body:
        print("Slice size exceeds threshold but justification present")
        sys.exit(0)

print(
    f"Slice size exceeds threshold ({len(changed)} > {THRESHOLD}) without justification"
)
sys.exit(1)
