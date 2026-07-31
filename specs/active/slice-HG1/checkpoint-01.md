# Slice HG1 Checkpoint 01 — Household Goods Total Requested Quantity Limit Fix

## Objective
Verify that Household Goods `Maximum Total Requested Quantity` blocks over-limit public selections and manipulated submissions.

## Files changed
`public/js/voucher-request.js`, `includes/class-household-goods-catalog.php`, `contracts/protected-surface-acceptance.json`, and the HG1 execution packet.

## Code changes
Added Household Goods projected-unit helpers, disabled `+` controls when adding one more unit would exceed `voucher_quantity_max`, rejected over-limit quantity changes before state mutation, reused the stronger inline validation message during review/submit validation, and strengthened server-side total-unit rejection to include submitted total and configured maximum before returning request-line snapshots.

## Data changes
No schema migration expected.

## Contract changes
The existing total-unit limit contract is now explicitly enforced during item increment, review/submit validation, and server snapshot building.

## Security / policy changes
No capability or authentication changes.

## Protected surface verification
PASS - public request JavaScript and Household Goods request-line snapshot validation are named in the HG1 brief and acceptance registry.

## Anti-drift enforcement
PASS - no schema, pricing, catalog payload, admin settings, fulfillment, cashier, accounting, analytics, receipt, invoice, or issued-voucher behavior changed.

## Observability enforcement
No new audit event; rejected public requests use existing inline/REST error reporting.

## Testing
PASS - JavaScript syntax, PHP syntax, JSON validation, Python script compile, `git diff --check`, DDEV staged-value REST catalog check, DDEV staged-value server snapshot/rejection checks, DDEV public browser quantity-control check, and browser console check.

LIMITED - the canonical placeholder checker still reports a pre-existing generated artifact at `tmp/pdfs/website-admin-guide-render-final/page-5.png`; scoped search over HG1 changed files found no placeholder markers.

## Verification block
`node --check public/js/voucher-request.js`: PASS. `php -l includes/class-household-goods-catalog.php`: PASS. Full `find includes public admin -name '*.php' ... php -l`: PASS. `python3 -m py_compile scripts/*.py`: PASS. JSON validation for protected contracts/surfaces/acceptance: PASS. `git diff --check`: PASS. DDEV temporary `10/15` REST catalog returned `selected_category_limit: 10` and `voucher_quantity_max: 15`. DDEV server helper accepted exactly 15 units and snapshotted `voucher_quantity_max_snapshot = 15` and `selected_category_limit_snapshot = 10`; 16 units rejected with `household_goods_voucher_limit_exceeded` and message `This Household Goods voucher is limited to 15 total requested items. You submitted 16.` Public browser check showed `Requested units: 15 of 15`, all 18 Household Goods plus buttons disabled at 15, and all 18 plus buttons re-enabled after decrementing to 14. Browser console warnings/errors: none. Local DDEV settings restored to `0/0` after staged-value checks.

## Stop condition
Complete.
