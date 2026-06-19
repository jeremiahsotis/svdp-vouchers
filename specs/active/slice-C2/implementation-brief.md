# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Slice C2 - Household Goods Catalog, Limits, and Administration

## Status

Authoritative implementation brief for Slice C2.

## Purpose

Introduce Household Goods as an editable operational catalog with browse groups, estimated Conference / Partner cost values, category limits, voucher-wide limits, archive behavior, snapshots, and configuration audit.

## Authoritative Sources

- `release-c-product-contract.md`
- Current repository `AGENTS.md`
- `MASTER-STANDARD.md`
- `PROJECT-PROFILE.md`
- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`
- `docs/governance/canonical-commands.json`
- `docs/data-governance/migration-policy.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`
- The attached S5 Assisted Builder HTML/sample and S5 design decisions, where this slice touches builder UI

## Dependencies

- Depends on completion of C1-request-groups-and-delivery-foundation.

## Scope

- Add Household Goods browse group schema and admin management.
- Add Household Goods catalog schema and admin management.
- Add estimated Conference / Partner cost per unit.
- Add per-category quantity max with 0 = no limit.
- Add voucher-wide Household Goods quantity max with 0 = no limit.
- Add max 10 selected categories as locked product rule.
- Add active/archive behavior and prevent hard delete for historically referenced records.
- Seed starter browse groups and categories from the contract.
- Create snapshot helpers for issued Household Goods request lines.
- Add configuration audit entries for catalog and limit changes.

## Out of Scope

- No public Household Goods builder step yet.
- No cashier fulfillment workspace yet.
- No request group UI wiring.
- No POS/inventory/SKU features.
- No unrestricted Other category.

## Likely Protected Surfaces

These are expected targets. Confirm exact paths against the current repo before editing.

- includes/class-database.php
- includes/class-household-goods-catalog.php (new)
- includes/class-household-goods-settings.php (new, if separate)
- admin/templates/household-goods-browse-groups.php (new or equivalent)
- admin/templates/household-goods-catalog.php (new or equivalent)
- admin/templates/household-goods-limits.php (new or equivalent)
- assets/admin JS/CSS if needed
- contracts/protected-contracts.json
- docs/data-governance/data-evolution-log.md

## Acceptance Criteria

- Authorized admin can add, edit, archive, restore, and reorder browse groups.
- Authorized admin can add, edit, archive, restore, and reorder Household Goods categories.
- Catalog categories have estimated Conference / Partner cost per unit, quantity max, active/archive, guidance, and sort order.
- Archived categories do not appear for new requests but remain visible historically.
- 0 means no limit for category and voucher-wide limits.
- Negative cost and quantity values are rejected.
- Duplicate active category names within the same browse group are rejected.
- Configuration changes are audited and do not alter issued snapshots.

## Hard Rules Across All Release C Slices

- Do not reinterpret legacy `household` voucher records as the new `household_goods` type.
- Do not add unrestricted Vincentian Notes fields.
- Do not add item-level fulfillment Notes fields.
- Only allow the optional voucher-level Internal Finalization Note where specified.
- Do not introduce POS integration, live inventory, SKU tracking, barcode scanning, dispatch, routing, driver scheduling, delivery attempts, or RouteShyft behavior.
- Do not duplicate delivery charges across child vouchers. Delivery is once per request group.
- Preserve legacy Clothing and Furniture voucher behavior.
- Preserve historical Furniture completion notes where already stored, but do not expose them as a new-entry field.
- Treat request group creation as atomic. No partial request groups.
- Snapshot configuration values onto issued request records where required.

## Data and Migration Rules

- Any schema change must use the repo's established schema versioning and migration pattern.
- Any new table or column must be documented in the data evolution log.
- Existing records must remain valid and viewable.
- Configuration changes must affect future records only unless the contract explicitly states otherwise.
- Historical snapshots must not be recalculated after later catalog, delivery, or setting changes.

## Audit Rules

- Protected mutation must create or preserve auditability.
- Configuration changes must include before value, after value, actor, timestamp, and human summary where applicable.
- Fulfillment and finalization must preserve who acted, when, and what changed.
- Corrections must add audit entries rather than rewriting history.

## Implementation Notes

- Keep implementation incremental and reviewable.
- Prefer small, named services/classes over spreading Release C conditionals across templates.
- Keep display labels separate from technical values where the contract requires it.
- Preserve existing production behavior unless this slice explicitly changes it.

## Literal Release C Contract Bindings

Slice C2 binds to the Release C Product Contract.

Required C2 terms:

- Household Goods
- household_goods
- browse group
- catalog
- quantity
- limit
- voucher-wide
- 0 means no limit
- max 10
- Estimated Conference / Partner Cost
- archive
- restore
- sort order
- cashier guidance
- snapshot
- configuration audit
- backward compatibility

C2 implements the Household Goods catalog, browse groups, quantity limits, voucher-wide limits, configuration audit, archive and restore behavior, and snapshot preparation required by the Release C Product Contract.

C2 preserves backward compatibility by leaving existing Clothing, Furniture, and legacy household behavior unchanged.
