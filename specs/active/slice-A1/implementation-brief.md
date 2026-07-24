# Slice A1 — Monthly Accounting and Neighbor Voucher Printing

## Objective
Automate recoverable monthly statements and QuickBooks Desktop IIF packages, add limited bookkeeping authority, and provide a protected price-free neighbor voucher print view.

## Scope / explicit non-scope
Includes statement PDF/email delivery, monthly reconciliation, export batches, accounting configuration, billing mappings, audits, and cashier printing. Payments, credits, QuickBooks API sync, public document URLs, dispatch, and pricing changes are excluded.

## Architectural context
WordPress tables remain authoritative. WP-Cron is a recoverable trigger, not a queue. Stored invoice/request snapshots are the source for financial exports and approved-item printouts.

## Affected systems
Schema, plugin bootstrap, roles, REST, admin tabs, statements, email/document storage, cashier detail UI, and governance contracts.

## Data model impact
Schema 14 additively extends organizations and statements and adds accounting batches/audit rows. Existing statements are retained as legacy unexported records.

## Contracts
Monthly batches include unstatemented invoices through the prior month end. IIF contains invoices; statement PDFs and CSV manifest accompany it. Neighbor print payloads contain identity and approved quantities but no price/accounting fields.

## Execution flow
Daily reconciliation acquires a monthly lock, generates organization statements atomically, sends recoverable emails, and exports eligible statements when configuration is complete. Cashier print is rendered on demand through an authenticated endpoint.

## State transitions
Statements move pending → sent/failed/missing_recipient and unexported → exported. Batches move processing → completed/partial/blocked/failed.

## Failure modes
Missing recipients/mappings, mail/PDF/storage failure, invoice attachment conflicts, stale/repeated cron, and oversized email packages are recorded and retryable.

## Idempotency
Unique monthly keys, conditional invoice attachment, statement export ownership, and stored delivery status prevent duplicate mutation and successful-email replay.

## Security / policy / permissions
`svdp_manage_accounting` protects accounting views/mutations. Existing cashier access protects neighbor printing. Print rendering receives an allowlisted price-free DTO only.

## Provider scope decision
Email and QuickBooks mappings are system-wide; billing email and QuickBooks customer name are per organization. QuickBooks remains file-based with no provider API.

## Protected surface impact
Database, bootstrap/REST, permissions, admin routing, statement/invoice/cashier templates, and document contracts are protected and covered by this slice.

## Protected contract impact
Extends receipt/invoice integrity, audit logging, authorization, and concurrency contracts without changing invoice calculations.

## Guardrail registration impact
Register A1 acceptance, migration, concurrency, and protected-surface descriptions.

## Anti-drift mapping
Every mutation maps to the approved plan; catalog pricing, fulfillment rules, and public request behavior remain frozen.

## Observability
Accounting and print actions write human-readable audit events and surface delivery/batch errors in admin.

## Environment fidelity
Use Composer only for bundled Dompdf, repository canonical checks, and DDEV runtime/browser verification.

## Testing requirements
Syntax, governance, schema idempotency, cron retry/concurrency, email/PDF/IIF fixtures, authorization, price-absence assertions, and DDEV print/admin scenarios.

## Version context
Schema 13 → 14.

## Dependencies
Existing invoices, statements, fulfillment/request snapshots, WordPress mail/cron/roles, and Dompdf.

## Finalization pass required before completion
Complete checkpoint, acceptance registry, compatibility notes, and verification results.

## Non-goals
Payment tracking, credits, QuickBooks list creation, direct QuickBooks integration, and document retention changes.

## Future work
Payment reconciliation and additional approved-item adapters may be added in later slices.
