import pathlib
import sys

FORBIDDEN = ["TBD","FIXME in docs","placeholder","lorem ipsum","coming soon","Add more details here","Example content"]

for path in pathlib.Path(".").rglob("*"):
    if not path.is_file():
        continue
    if any(part in {".git", "node_modules", ".venv"} for part in path.parts):
        continue
    try:
        text = path.read_text(errors="ignore")
    except Exception:
        continue
    for token in FORBIDDEN:
        if token in text:
            print(f"Placeholder found in {path}: {token}")
            sys.exit(1)

print("No placeholders found")
