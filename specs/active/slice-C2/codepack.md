# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Codepack - Slice C2 - Household Goods Catalog, Limits, and Administration

## Task

Implement Slice C2 according to the matching `implementation-brief.md`.

## Preflight

- Confirm prior slice `C1-request-groups-and-delivery-foundation` is merged and validation passed.
- Run baseline status and validation commands before editing.

## Baseline Validation Commands

Run from the plugin repository root unless a command says otherwise.

```bash
git status --short
git log --oneline -n 12

find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l

python3 scripts/check_required_doc_sections.py
python3 scripts/check_no_placeholders.py

find . -name ".DS_Store" -print
```

If project governance scripts differ in the current repo, use `docs/governance/canonical-commands.json` as the source of truth and record the variance in the checkpoint.

## Work Plan

1. Read `release-c-product-contract.md`.
2. Read this slice's `implementation-brief.md`.
3. Confirm current repo file paths and protected surfaces.
4. Update planning or contracts first where required.
5. Make the smallest coherent implementation changes for this slice only.
6. Update data governance docs for any schema/configuration changes.
7. Update or add tests/manual checkpoints aligned with `checkpoint-01.md`.
8. Run validation commands.
9. Prepare a concise implementation summary.

## Expected Change Areas

- includes/class-database.php
- includes/class-household-goods-catalog.php (new)
- includes/class-household-goods-settings.php (new, if separate)
- admin/templates/household-goods-browse-groups.php (new or equivalent)
- admin/templates/household-goods-catalog.php (new or equivalent)
- admin/templates/household-goods-limits.php (new or equivalent)
- assets/admin JS/CSS if needed
- contracts/protected-contracts.json
- docs/data-governance/data-evolution-log.md

## Implementation Guardrails

- No public Household Goods builder step yet.
- No cashier fulfillment workspace yet.
- No request group UI wiring.
- No POS/inventory/SKU features.
- No unrestricted Other category.

## Slice-Specific Build Requirements

- Authorized admin can add, edit, archive, restore, and reorder browse groups.
- Authorized admin can add, edit, archive, restore, and reorder Household Goods categories.
- Catalog categories have estimated Conference / Partner cost per unit, quantity max, active/archive, guidance, and sort order.
- Archived categories do not appear for new requests but remain visible historically.
- 0 means no limit for category and voucher-wide limits.
- Negative cost and quantity values are rejected.
- Duplicate active category names within the same browse group are rejected.
- Configuration changes are audited and do not alter issued snapshots.

## Required Developer Summary

At completion, report:

- Files changed
- Schema changes made, if any
- Protected surfaces touched and why
- Acceptance criteria passed
- Validation commands run and result
- Known issues or deferred work
- Confirmation that Release C non-goals were not introduced

## Stop Conditions

Stop and ask for direction if:

- A contract rule conflicts with existing production behavior in a way not covered by this slice.
- Current repo paths differ materially from expected paths.
- A migration would rewrite historical voucher or delivery data.
- Any change would introduce POS, inventory, SKU, dispatch, routing, delivery attempts, or unrestricted notes.
- The slice cannot be completed without implementing scope reserved for a later slice.

## Guardrail Runtime File Map

admin/js/household-goods-admin.js
admin/views/admin-page.php
admin/views/tab-household-goods.php
includes/class-admin.php
includes/class-database.php
includes/class-household-goods-catalog.php
includes/class-permissions.php
svdp-vouchers.php
