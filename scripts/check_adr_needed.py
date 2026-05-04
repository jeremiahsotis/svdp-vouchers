import subprocess
import sys

changed = subprocess.check_output(["git", "diff", "--name-only", "HEAD~1..HEAD"], text=True).splitlines()
sensitive = [p for p in changed if p.startswith(("contracts/", "docs/security/", "docs/guardrails/")) or "/auth/" in p or "/policy/" in p]
adr_changed = any(p.startswith("docs/adr/ADR-") for p in changed)
recon_changed = any("reconciliation-log.md" in p for p in changed)

if sensitive and not (adr_changed or recon_changed):
    print("Sensitive architecture/contract/security change without ADR or reconciliation update")
    sys.exit(1)

print("ADR-needed check passed")
