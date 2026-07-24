# Slice P1 — Unified Priced Catalogs

## Objective
Unify Furniture and Household Goods pricing and request-card behavior, make Household Goods selection limits configurable, and make Furniture categories editable.

The final request-card presentation uses a compact three-line maximum: one ellipsized item/category title line, up to two pricing lines, and a unified three-segment quantity control. The complete public builder owns its system typography and viewport-centered responsive width so WordPress theme defaults cannot reduce the usable layout or restyle its content. Search, filters/arrows, counters, progress, and desktop/mobile summaries remain behaviorally unchanged.

## Scope / explicit non-scope
Includes catalog schema, migrations, immutable request snapshots, public selection UI, catalog administration, permissions, auditing, and conflict guards. Clothing, historical voucher reinterpretation, receipts, dispatch, inventory, and POS behavior are excluded.

## Architectural context
WordPress database tables remain authoritative. Catalog configuration applies to future requests; issued request-line snapshots remain immutable.

## Affected systems
Database upgrade, Furniture and Household Goods catalog services, request-group issuance, admin AJAX/views/scripts, and the Assisted Builder.

## Data model impact
Schema version 13 adds Furniture categories, Household Goods retail/coverage fields, Household Goods pricing snapshots, and configurable selected-category limits. Migration is idempotent and preserves historical rows.

## Contracts
Priced cards expose `Retail price: … • [Organization Type] pays …` for Fixed/Exact pricing and `Retail price: … • [Organization Type] pays up to …` for Range or unknown pricing. They use the friendly Conference, Partner, or Store type instead of the potentially long organization name and fall back to Organization when unresolved. A shared pricing-label formatter is the presentation path for current and future priced catalogs. Cards show inline category context only under the All/unset filter and use full-width decrement/increment touch targets. The public builder uses a scoped system-font boundary and an outer maximum width of 1180px with 14px viewport gutters. Range display defaults to Up to. Clothing and legacy `household` behavior do not change.

## Execution flow
Upgrade schema and seed categories; normalize catalog data; administer future configuration; return normalized public catalog data; validate and snapshot selections atomically.

## State transitions
Categories and items transition Active ↔ Archived. A Furniture category cannot archive while active items reference it.

## Failure modes
Reject invalid pricing, limits, missing/inactive categories, duplicate names, active-child archive attempts, and stale category updates.

## Idempotency
Schema creation, seed/backfill, and defaults are repeatable. Existing issued snapshots are never rewritten.

## Security / policy / permissions
Existing Furniture and Household Goods catalog capabilities protect mutations. Configuration audit visibility remains separately capability protected.

## Provider scope decision
No external provider or Monday.com behavior changes.

## Protected surface impact
Touches protected database, catalog, request-group, public builder, and admin mutation surfaces under this brief.

## Protected contract impact
Updates pricing-display and Release C Household Goods catalog behavior while retaining historical and invoice/receipt contracts.

## Guardrail registration impact
Protected-surface acceptance and data-evolution records must identify P1 changes.

## Anti-drift mapping
Every mutation maps to the approved plan and this brief; unrelated Release C behavior is frozen.

## Observability
Configuration changes write human-readable before/after audit rows with actor and timestamp.

## Environment fidelity
Validate with repository canonical commands; WordPress runtime verification remains a Local by Flywheel manual checkpoint.

## Testing requirements
PHP and JavaScript syntax, governance validation, migration idempotency review, pricing/limit validation, Fixed/Exact versus Range public payment wording, public card behavior, category CRUD/archive/conflict, audit, and Clothing/legacy regression checks.

## Version context
Schema version 12 → 13.

## Dependencies
Existing Release C request groups, requested lines, capabilities, and configuration audit table.

## Finalization pass required before completion
Update checkpoint, governance acceptance, data evolution, and change-impact records after validation.

## Non-goals
No inventory, shopper pricing, retrospective recalculation, dispatch, routing, or new notes.

## Future work
Additional priced voucher types may adopt the normalized priced-card contract later.
