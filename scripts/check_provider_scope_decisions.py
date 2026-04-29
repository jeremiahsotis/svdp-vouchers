import pathlib
import subprocess
import sys

changed = subprocess.check_output(["git", "diff", "--name-only", "HEAD~1..HEAD"], text=True).splitlines()
providerish = [c for c in changed if any(k in c.lower() for k in ["provider", "webhook", "twilio", "telnyx", "sinch", "mail", "sms", "sip"])]
if not providerish:
    print("No provider-related changes detected")
    sys.exit(0)

briefs = list(pathlib.Path("specs/active").glob("slice-*/implementation-brief.md"))
if not briefs:
    print("Provider scope decision required but no active implementation brief found")
    sys.exit(1)

text = briefs[0].read_text(errors="ignore")
required_phrases = ["## 12. Provider Scope Decision", "scope: system-wide / per-tenant"]
if not all(p in text for p in required_phrases):
    print("Provider-related changes detected but provider scope decision is missing from active brief")
    sys.exit(1)

print("Provider scope decision check passed")
