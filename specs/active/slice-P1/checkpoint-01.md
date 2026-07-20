# Slice P1 Checkpoint 01 — Unified Priced Catalogs

## Objective
Verify the approved unified pricing, configurable limits, and editable Furniture category work.

## Files changed
Database/catalog/request services, admin and public catalog UI, protected contracts, governance logs, and this P1 execution packet. See the final `git status --short` output.

## Code changes
Implemented unified priced catalogs, configurable Household Goods limits, immutable snapshots, editable Furniture categories, audit events, stale category-write rejection, compact shared selection cards, and a theme-isolated public builder width/typography boundary matching the approved desktop/mobile references.

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
PASS — DDEV verification against Twenty Twenty-Five confirmed the builder computes to the scoped system font at 18px/400 with normal letter spacing and 1.45 line height. Catalog values compute to 18px/800 title, 11px/500 italic category, 14px/400 pricing, 24px quantity buttons, and 18px/800 quantity value. At 1280px the centered builder is 1180px wide with the existing 748px main/360px summary layout; at 390px it is 362px wide with 14px gutters and no horizontal overflow. Titles remain one line, pricing remains at no more than two lines, quantity/selected state still updates, and browser console warnings/errors are absent. Prior Furniture/Household Goods card behavior, schema, admin, limit, audit, and public flow verification remains passing.

## Verification block
`find includes public admin -name '*.php' ... php -l`: PASS. `node --check` changed JS: PASS. JSON validation: PASS. Governance validators: PASS. `git diff --check`: PASS. DDEV schema 13: PASS. Live browser console warnings/errors: none.

## Editorial check
PASS — public labels use Retail Price and Maximum selected-organization-name Cost; zero-limit copy explicitly says no limit.

## Stop condition
Complete after all automated checks pass and the DDEV responsive scenarios above are recorded.
