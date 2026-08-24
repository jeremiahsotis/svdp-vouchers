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
    "attr('placeholder'",
    'placeholder="',
    'setAttribute("placeholder"',
    "data-category-placeholder",
    "category-placeholder",
    "$placeholders",
    "not placeholder-only",
    "placeholder labels",
    "placeholder terms",
    "placeholder check",
    "placeholder checker",
    "placeholder marker",
    "placeholder search",
    "No placeholder text remains",
]

SCANNED_SUFFIXES = {
    ".css",
    ".html",
    ".js",
    ".json",
    ".md",
    ".php",
    ".py",
    ".sh",
    ".sql",
    ".txt",
    ".xml",
    ".yaml",
    ".yml",
}

SCANNED_FILENAMES = {
    ".gitignore",
}

SKIPPED_DIR_PARTS = {
    ".git",
    ".venv",
    "node_modules",
    "vendor",
}

SKIPPED_GENERATED_DIRS = {
    ("tmp", "pdfs"),
}


def is_under_generated_dir(path):
    parts = path.parts
    return any(
        parts[index:index + len(generated)] == generated
        for generated in SKIPPED_GENERATED_DIRS
        for index in range(0, len(parts) - len(generated) + 1)
    )


def should_scan(path):
    if not path.is_file():
        return False
    if path.name == "check_no_placeholders.py":
        return False
    if path.name.endswith("recon.txt"):
        return False
    if any(part in SKIPPED_DIR_PARTS for part in path.parts):
        return False
    if is_under_generated_dir(path):
        return False
    return path.suffix.lower() in SCANNED_SUFFIXES or path.name in SCANNED_FILENAMES


def is_allowed_reference(line):
    return any(allowed in line for allowed in PLACEHOLDER_REFERENCE_ALLOWLIST)


def line_contains_forbidden(line, token):
    if is_allowed_reference(line):
        return False
    if token == "placeholder":
        return bool(re.search(r"(?<![A-Za-z_])placeholder(?![A-Za-z_])", line))
    return token in line


paths = [pathlib.Path(arg) for arg in sys.argv[1:]]
if not paths:
    paths = pathlib.Path(".").rglob("*")

for path in paths:
    if not should_scan(path):
        continue
    try:
        text = path.read_text(errors="ignore")
    except Exception:
        continue
    for token in FORBIDDEN:
        found = any(line_contains_forbidden(line, token) for line in text.splitlines())

        if found:
            print(f"Placeholder found in {path}: {token}")
            sys.exit(1)

print("No placeholders found")
