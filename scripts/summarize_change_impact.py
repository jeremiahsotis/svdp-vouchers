#!/usr/bin/env python3
import subprocess

changed = subprocess.check_output(["git", "diff", "--name-only", "HEAD~1..HEAD"], text=True).splitlines()
print("# Change Impact Summary\n")
print("## Changed files")
for c in changed:
    print("-", c)
print("\n## Likely impacts")
for c in changed:
    lc = c.lower()
    if "contract" in lc:
        print("- contracts likely affected")
    if "auth" in lc or "policy" in lc or "permission" in lc:
        print("- auth/policy implications likely")
    if "migration" in lc or "model" in lc or "schema" in lc:
        print("- migrations/data model likely affected")
    if "route" in lc or "router" in lc:
        print("- API docs and response contracts likely affected")
    if "provider" in lc or "webhook" in lc:
        print("- provider scope decision likely required")
