# Slice A1 Bootstrap — Monthly Accounting and Neighbor Voucher Printing

## Execution context
Execute only approved Slice A1 in this WordPress plugin.

## Authoritative sources
Approved user plan, AGENTS.md, governance standards, A1 implementation brief, protected contracts, migration policy, concurrency model, access-audit model, and canonical commands.

## Deprecated sources
Earlier manual-only statement and full-admin-only accounting assumptions are superseded for A1.

## Anti-drift rule
Do not change invoice calculations, catalog pricing, fulfillment, or public request behavior.

## Protected surface guardrails
Modify only protected surfaces mapped by A1 and record verification.

## AST contract rule
Run all available governance validators.

## Provider scope rule
Email/IIF settings are system-wide; organization billing mappings are per organization.

## Dependency rule
Bundle the approved Dompdf Composer dependency; add no JavaScript package tooling.

## Feature freeze rule
Unrelated voucher, catalog, delivery, and cashier mutation behavior remains frozen.

## Environment fidelity rule
Validate repository checks and DDEV WordPress runtime behavior.

## Observability rule
Protected actions require readable audit events and stored failure state.

## Execution instructions
Implement schema and services, then permissions/admin, cashier print, governance updates, and verification.

## Stop condition
Stop when A1 acceptance passes or a material external dependency is blocked.

## Output requirements
Report migration, compatibility, security, runtime verification, and remaining operational setup.
