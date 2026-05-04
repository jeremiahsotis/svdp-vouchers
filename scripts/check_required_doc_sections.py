import pathlib
import sys

rules = {
    "docs/adr": ["## Status", "## Date", "## Context", "## Decision", "## Alternatives considered", "## Consequences", "## Contracts affected", "## Docs affected", "## Superseded by"],
}

for base, required in rules.items():
    root = pathlib.Path(base)
    if not root.exists():
        continue
    for path in root.rglob("*.md"):
        text = path.read_text(errors="ignore")
        missing = [r for r in required if r not in text]
        if missing:
            print(f"{path} missing sections: {', '.join(missing)}")
            sys.exit(1)

print("Required doc sections valid")
