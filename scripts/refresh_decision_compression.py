#!/usr/bin/env python3
import pathlib
from datetime import date

out = pathlib.Path("docs/decisions/current-state.md")
out.write_text(f"# Current State\n\nGenerated on {date.today().isoformat()}\n\n## Current Version\n\n## Version Status\n\n## Active Phase\n\n## Active Slice\n\n## Current architecture summary\n\n## Current protected surfaces summary\n\n## Current release/freeze state\n")
print(f"Refreshed {out}")
