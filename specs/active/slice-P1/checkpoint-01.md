# Slice P1 Checkpoint 01 — Unified Priced Catalogs

## Objective
Verify the approved unified pricing, configurable limits, and editable Furniture category work.

## Files changed
Database/catalog/request services, admin and public catalog UI, protected contracts, governance logs, and this P1 execution packet. See the final `git status --short` output.

## Code changes
Implemented unified priced catalogs, configurable Household Goods limits, immutable snapshots, editable Furniture categories, audit events, stale category-write rejection, compact shared selection cards, pricing-type-aware organization payment labels, and a theme-isolated public builder width/typography boundary matching the approved desktop/mobile references.

## Data changes
Schema 13 forward migration; no issued snapshot rewrites. Rollback retains additive columns/tables and can run older code against preserved compatibility fields.

## Contract changes
Household Goods becomes publicly priced like Furniture; the ten-category constant becomes an unlimited-by-default setting.

## Security / policy changes
No capability expansion.

## Protected surface verification
PASS — affected protected surfaces map to the P1 implementation brief and acceptance registry.

## AST contract validation
PASS — repository required-document validator passed; no separate AST validator is registered in canonical commands.

## Guardrail auto-detection
PASS — protected contracts and acceptance registry were updated and JSON-validated.

## Anti-drift enforcement
PASS — Clothing, legacy `household`, receipts, invoices, delivery, fulfillment, inventory, and notes remain outside the implementation.

## Observability enforcement
PASS — protected catalog/category/limit mutations emit configuration audit rows.

## Logging / audit
PASS — entries include area, record identity/name, field, before/after, actor, timestamp, and human summary.

## Environment fidelity validation
PASS — DDEV schema upgrade completed against `https://test-site.ddev.site/`; WordPress reports schema 13. Database inspection confirmed seeded Furniture categories, empty legacy Household Goods Furniture category archived, zero/unlimited Household Goods settings, Furniture range Up to backfill, and 18 Household Goods fixed-price migrations.

## Dependency validation
No new dependency expected.

## Testing
PASS — DDEV verified that the Range Furniture Sofa renders `Conference pays up to $37.50`, the Fixed Furniture Twin Mattress renders `Conference pays $60.00`, and migrated Fixed Household Goods cards render `Conference pays …`. Visible pricing and full `title` text match, remain within the two-line limit, and browser console warnings/errors are absent. Static branch verification confirms case-insensitive `fixed`/`exact` use `pays`, while `range`, missing, and unknown types conservatively use `pays up to`; the organization-label helper continues to supply Conference, Partner, Store, or Organization. Prior organization-type labels, responsive theme-isolation, Furniture/Household Goods card behavior, schema, admin, limit, audit, and public flow verification remains passing.

## Verification block
`find includes public admin -name '*.php' ... php -l`: PASS. `node --check` changed JS: PASS. JSON validation: PASS. Governance validators: PASS. `git diff --check`: PASS. DDEV schema 13: PASS. Live browser console warnings/errors: none.

## Editorial check
PASS — public labels use Retail Price and Maximum selected-organization-name Cost; zero-limit copy explicitly says no limit.

## Stop condition
Complete after all automated checks pass and the DDEV responsive scenarios above are recorded.
