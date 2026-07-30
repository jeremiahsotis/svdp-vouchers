# Slice AN1 Checkpoint 01 - Analytics Page Rework

## Objective
Verify the reworked Analytics tab, shared filters, selected-range reporting, and top-filter CSV export.

## Files changed
`includes/class-analytics.php`, `admin/views/tab-analytics.php`, `includes/class-admin.php`, `svdp-vouchers.php`, protected-surface JSON contracts, and AN1 execution docs.

## Code changes
Added a shared Analytics query service, rebuilt the Analytics tab sections and filter UI, routed AJAX/export through the shared service, removed export-local filters, included Denied/Blocked rows in export, added Voucher Type to Recent Denied Vouchers, counted priced-voucher fulfilled items from stored invoice totals, and made Voucher Overview default to All Time with people/value/item impact totals.

## Data changes
No schema or data migration.

## Contract changes
Analytics reporting/export protected contract added.

## Security / policy changes
No new role or capability. Existing accounting/admin authority remains required.

## Protected surface verification
PASS - protected admin/export changes are named in contracts and acceptance.

## AST contract validation
PASS - no AST-specific command is registered; PHP lint and JSON validation passed.

## Guardrail auto-detection
PASS with caveat - JSON contracts validate. `check_no_placeholders.py` reports a pre-existing `TBD` inside `tmp/pdfs/website-admin-guide-render-final/page-5.png`, outside AN1 changes.

## Anti-drift enforcement
PASS - cashier workflows, request forms, fulfillment, invoices, statements, accounting batches, roles, and schema were not changed.

## Observability enforcement
No mutation audit required for read-only analytics/export behavior.

## Logging / audit
No new audit events.

## Environment fidelity validation
PASS - DDEV WordPress runtime and live admin page verified.

## Dependency validation
No dependency changes.

## Testing
PASS - PHP lint for all non-vendor/non-output/non-tmp PHP, targeted PHP lint for touched files, JSON validation, slice completion integrity, `git diff --check`, DDEV helper runtime checks, and browser checks.

LIMITED - `check_unmapped_changes.py` reports pre-existing/unrelated generated and prior A1 files, including `vendor/`, `tmp/`, `output/`, and accounting/print files outside the AN1 diff.

## Verification block
DDEV helper checks: initial Voucher Overview defaulted to All Time with 22 total vouchers and 27 people served while period overview remained Month to Date; All Time returned 22 total vouchers, 1 denied, 7 items, and $35.00 value; Furniture-only returned 9 total vouchers. Browser checks: Analytics loaded, default Date Range was `mtd`, Voucher Overview displayed All Time, 22 vouchers, 27 people served, `21 adults, 6 children`, $35.00, and 7 items; All Time updated overview and period totals to 22; Furniture filter updated totals to 9 and export hidden state to `furniture`; Recent Denied Vouchers rendered 7 columns; export triggered a CSV download; reset restored the starting state; console logs were empty.

## Editorial check
PASS - visible headings are Filters, Voucher Overview, Current Month-to-Date Overview, Community Impact, Performance by Organization, Denied/Blocked Vouchers, Emergency Override Statistics, and Export Data.

## Stop condition
Complete after automated and DDEV acceptance checks are recorded.
