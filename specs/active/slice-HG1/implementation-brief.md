# Slice HG1 — Household Goods Total Requested Quantity Limit Fix

## Objective
Ensure the configurable Household Goods voucher-wide Maximum Total Requested Quantity is enforced consistently in the public builder and server request-line snapshot validator.

## Scope / explicit non-scope
Includes Household Goods browser quantity controls, Household Goods step/review validation wording, authoritative server request-line validation, and governance records. Excludes schema, admin settings UI, pricing, invoices, statements, fulfillment, Clothing, Furniture behavior, and issued-voucher mutation.

## Architectural context
Household Goods category selections are held in browser state before request-group submission. The server remains authoritative and builds immutable request-line snapshots only after validating submitted category quantities against configured limits.

## Affected systems
Public voucher request JavaScript and the Household Goods catalog request-line snapshot service.

## Data model impact
No schema migration. Existing settings and issued request snapshots remain unchanged.

## Contracts
`Maximum Total Requested Quantity` counts total Household Goods units across selected categories. A value of `0` remains unlimited. `Maximum Selected Categories` continues to count distinct categories only.

## Execution flow
The browser rejects and disables increments that would exceed the voucher-wide unit limit, while preserving per-category and distinct-category checks. Review/submit validation keeps enforcing the same limit. The server sums category quantities and rejects any manipulated payload whose submitted total exceeds the configured max before returning snapshots for insertion.

## Failure modes
Reject over-limit browser increments with inline feedback. Reject manipulated REST submissions with a clear `WP_Error` message containing the submitted total and configured maximum.

## Idempotency
No persistent migration or batch process. Validation is deterministic for the current settings.

## Security / policy / permissions
No capability changes. Public rejection uses existing visible request-form errors; no new audit event is required for rejected public requests.

## Protected surface impact
Touches protected public request validation and request-line snapshot validation surfaces.

## Protected contract impact
Clarifies and strengthens enforcement of existing Household Goods limit semantics.

## Anti-drift mapping
Only the Household Goods total-unit limit defect is changed. Pricing, catalog payloads, snapshots, accounting, cashier, and analytics behavior remain frozen.

## Observability
No new audit event. Server rejection remains visible through the existing REST error response shape.

## Testing requirements
JavaScript syntax, PHP syntax, governance validation, JSON validation, `git diff --check`, local staged-value server tests for 10 selected categories / 15 total units, and DDEV/browser verification where available.

## Version context
No schema version change.

## Rollback
Revert the JavaScript and PHP validation changes. No data cleanup is required.
