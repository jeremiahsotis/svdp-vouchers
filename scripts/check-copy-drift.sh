#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SEARCH_DIRS=(
  includes
  public/templates
  public/js
  admin
)

PATTERNS=(
  "Maximum Conference commitment"
  "Total maximum Conference commitment"
  "Selected item retail maximum"
  "maximum Conference cost"
  "Final fulfilled pricing may be lower"
  "may be lower based on the items chosen"
  "Most items are calculated at 50% of the retail prices shown. Mattress/Frame Bundles use the exact price shown."
  "retail prices shown"
  "exact price shown"
  "Conference cost"
  "Actual fulfilled item total"
  "Conference share (50%)"
  "Conference share"
  "Delivery fee"
  "Delivery Fee"
  "delivery fee"
  "Total invoice amount"
  "Conference invoice uses actual fulfilled prices x 50%"
  "actual fulfilled prices"
  "Final fulfilled pricing may vary"
  "Estimated Requestor Portion"
  "Estimated Total"
)

violations=0

for pattern in "${PATTERNS[@]}"; do
  matches="$(
    rg -n -F "$pattern" "${SEARCH_DIRS[@]}" \
      --glob '!public/vendor/**' \
      --glob '!includes/class-voucher-rules.php' || true
  )"

  if [[ -n "$matches" ]]; then
    printf 'Copy drift found for "%s":\n%s\n\n' "$pattern" "$matches"
    violations=1
  fi
done

if [[ "$violations" -ne 0 ]]; then
  printf 'Pricing copy must come from includes/class-voucher-rules.php.\n'
  exit 1
fi

printf 'No pricing copy drift found.\n'
