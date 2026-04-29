import subprocess
import sys

changed = subprocess.check_output(["git", "diff", "--name-only", "HEAD~1..HEAD"], text=True).splitlines()
code = [p for p in changed if p.startswith(("apps/", "packages/", "services/", "src/"))]
docs = [p for p in changed if p.startswith("docs/")]
contracts = [p for p in changed if p.startswith("contracts/")]

if any("/routes/" in p or "/routers/" in p for p in code) and not (docs or contracts):
    print("Route code changed but no docs/contracts changed")
    sys.exit(1)

if any("/models/" in p or "/schemas/" in p for p in code) and not any(p.startswith("docs/data-model/") or p.startswith("contracts/db/") for p in changed):
    print("Model/schema changed but no data-model or db contract docs changed")
    sys.exit(1)

print("Docs touched check passed")
