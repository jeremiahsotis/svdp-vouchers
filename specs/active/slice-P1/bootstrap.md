# Slice P1 Bootstrap — Unified Priced Catalogs

## Execution context
Execute only Slice P1 in the `svdp-vouchers` WordPress plugin.

## Authoritative sources
The approved user plan, `AGENTS.md`, `MASTER-STANDARD.md`, `PROJECT-PROFILE.md`, this implementation brief, protected contracts, migration policy, concurrency model, access-audit model, and canonical commands.

## Deprecated sources
Earlier Release C statements forbidding public Household Goods prices or hardcoding a ten-category limit are superseded only for this slice.

## Anti-drift rule
Do not add unrelated business behavior.

## Protected surface guardrails
Only change protected surfaces mapped by the P1 implementation brief and record them in the checkpoint.

## AST contract rule
Run available governance validators and document any unavailable validator.

## Provider scope rule
No external provider changes.

## Dependency rule
Do not add package managers or third-party dependencies.

## Feature freeze rule
Clothing, legacy `household`, fulfillment finalization, receipts, invoices, and delivery behavior remain frozen.

## Environment fidelity rule
Use repository checks locally and reserve WordPress integration checks for Local by Flywheel.

## Observability rule
All protected configuration mutations require readable audit events.

## Execution instructions
Implement schema/migration first, then services and snapshots, admin UI, public UI, governance updates, and validation.

## Stop condition
Stop when P1 acceptance checks pass and the checkpoint records results.

## Output requirements
Summarize data migration, compatibility, changed behavior, validation, and remaining manual checks.
