#!/usr/bin/env bash
set -euo pipefail

echo "Bootstrap this governance pack into a target repo by copying templates and adjusting paths."
echo "1. Copy AGENTS.template.md -> AGENTS.md"
echo "2. Copy contracts/*.template.json into active contracts/"
echo "3. Copy specs/slice-template into specs/active/slice-G0 or your starting slice"
echo "4. Wire canonical commands and CI to your stack"
