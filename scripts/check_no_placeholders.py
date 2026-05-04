import pathlib
import re
import sys

FORBIDDEN = [
    "TBD",
    "FIXME in docs",
    "placeholder",
    "lorem ipsum",
    "coming soon",
    "Add more details here",
    "Example content",
]

PLACEHOLDER_REFERENCE_ALLOWLIST = [
    "check_no_placeholders.py",
    "known placeholder",
    "Placeholder found",
    "implementation placeholder",
    "literal placeholder",
]

paths = [pathlib.Path(arg) for arg in sys.argv[1:]]
if not paths:
    paths = pathlib.Path(".").rglob("*")

for path in paths:
    if not path.is_file():
        continue
    if path.name == "check_no_placeholders.py":
        continue
    if any(part in {".git", "node_modules", ".venv"} for part in path.parts):
        continue
    try:
        text = path.read_text(errors="ignore")
    except Exception:
        continue
    for token in FORBIDDEN:
        if token == "placeholder":
            found = any(
                re.search(r"(?<![A-Za-z_])placeholder(?![A-Za-z_])", line)
                and not any(
                    allowed in line for allowed in PLACEHOLDER_REFERENCE_ALLOWLIST
                )
                for line in text.splitlines()
            )
        else:
            found = token in text

        if found:
            print(f"Placeholder found in {path}: {token}")
            sys.exit(1)

print("No placeholders found")
