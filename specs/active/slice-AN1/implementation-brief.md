# Slice AN1 - Analytics Page Rework

## Objective
Rebuild the Analytics tab around one shared filter system, selected-range reporting sections, and a CSV export that uses the same top filters.

## Scope / explicit non-scope
Includes Analytics admin queries, tab presentation, AJAX refresh, CSV export filter handling, and protected-surface documentation. Excludes schema changes, voucher creation, cashier workflows, fulfillment, invoices, statements, accounting batches, request forms, and role changes.

## Architectural context
WordPress tables remain authoritative. The Analytics page previously mixed initial PHP queries, partial AJAX refreshes, and a separate export filter form; AN1 centralizes those reads in one service.

## Affected systems
Admin Analytics tab, admin AJAX filter endpoint, voucher CSV export, plugin bootstrap, and protected-surface contracts.

## Data model impact
No schema migration. Existing voucher, conference, manager, reason, coat, and redemption columns are read only.

## Contracts
Analytics filters include Date Range, Organization Type, Specific Organization, and Voucher Type. Export uses those same filters and includes all statuses, including Denied/Blocked.

## Execution flow
Initial page load requests Month-to-Date dashboard data. Filter application posts the top filters to AJAX and re-renders every analytics section. Export posts the same hidden filter values to the CSV handler.

## State transitions
None. This slice is read-only except for normal browser/UI state.

## Failure modes
Invalid custom dates return a user-visible error. Empty result sets render empty-state rows. Export permission failure remains blocked by the accounting/admin capability.

## Idempotency
Repeated filter requests and exports do not mutate database state.

## Security / policy / permissions
Existing `svdp_manage_accounting` access continues to guard Analytics AJAX and CSV export.

## Provider scope decision
No provider integration.

## Protected surface impact
Touches `includes/class-admin.php`, `svdp-vouchers.php`, and new Analytics reporting service loaded by plugin bootstrap.

## Protected contract impact
Adds the Analytics reporting/export contract and preserves existing accounting, cashier, fulfillment, invoice, and request contracts.

## Guardrail registration impact
Register AN1 in protected surfaces/contracts/acceptance.

## Anti-drift mapping
The work maps only to the approved Analytics plan. Removed sections are Items Provided, Voucher Metrics by Organization Type, and Breakdown by Voucher Type.

## Observability
No new audit events because this is read-only reporting/export behavior.

## Environment fidelity
Use DDEV/WordPress admin to verify live Analytics rendering and export behavior.

## Testing requirements
PHP syntax, JavaScript syntax in the rendered inline script where practical, governance JSON, no placeholders, `git diff --check`, helper runtime query checks, DDEV browser filters/reset/export, and browser console.

## Version context
No schema version change.

## Dependencies
Existing voucher, conference, manager, override reason, settings, and permission classes.

## Finalization pass required before completion
Update checkpoint and protected-surface acceptance after verification.

## Non-goals
New charts, new database tables, status mutation, payment reconciliation, and export format redesign beyond removing export-local filters.

## Future work
Dedicated charting or downloadable multi-sheet summary reports can be added later.
