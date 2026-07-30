# Slice AN1 Bootstrap - Analytics Page Rework

## execution context
WordPress plugin in DDEV, Analytics protected by existing accounting/admin capability.

## authoritative sources
User-approved Analytics Page Rework plan, AGENTS.md, governance contracts, and current plugin schema/classes.

## deprecated sources
Old Analytics tab sections for Items Provided, Voucher Metrics by Organization Type, Breakdown by Voucher Type, and the export-local date/denied controls.

## anti-drift rule
Do not alter cashier, request, fulfillment, invoice, statement, accounting batch, or schema behavior.

## protected surface guardrails
Document protected admin/export changes before implementation and update acceptance before completion.

## AST contract rule
No AST-specific validator is registered; use PHP lint, JSON validation, and governance checks.

## provider scope rule
No provider work.

## dependency rule
No new dependencies.

## feature freeze rule
Only implement the approved Analytics rework.

## environment fidelity rule
Verify in DDEV WordPress admin after local syntax/governance checks.

## observability rule
No audit events are required because analytics filtering/exporting reads existing records.

## execution instructions
Centralize analytics reads in a shared helper, render all sections from that helper, wire AJAX/export through the helper, and preserve current permissions.

## stop condition
Stop after implementation, verification, checkpoint, and acceptance updates are complete.

## output requirements
Report files changed, checks run, and any verification gaps.
