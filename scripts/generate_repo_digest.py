#!/usr/bin/env python3
import pathlib
from datetime import date

digest = pathlib.Path("docs/state/repo-digest.md")
digest.write_text(f"# Repo Digest\n\nGenerated on {date.today().isoformat()}\n\n## Current version\n## Version state\n## Active phase\n## Active slice\n## Last completed slice\n## Current blockers\n## Protected surface summary\n## Architecture snapshot\n## Outstanding risks\n## Feature freeze status\n## Recent roadmap changes\n## Canonical commands summary\n")
print(f"Generated {digest}")
