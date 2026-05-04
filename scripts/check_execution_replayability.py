import pathlib
import re
import sys

for sf in pathlib.Path("docs/roadmap").glob("v*/roadmap-state.md"):
    text = sf.read_text(errors="ignore")
    m = re.search(r"## Active Slice\s*\n([A-Z]\d+)", text)
    if not m:
        continue
    base = pathlib.Path("specs/active") / f"slice-{m.group(1)}"
    required = ["implementation-brief.md", "codepack.md", "checkpoint-01.md", "bootstrap.md"]
    missing = [r for r in required if not (base / r).exists()]
    if missing:
        print(f"Replayability failed for {m.group(1)}: missing {missing}")
        sys.exit(1)

print("Execution replayability passed")
