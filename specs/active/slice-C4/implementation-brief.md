# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Slice C4 - Three-Voucher Assisted Builder UI and Request Submission

## Status

Authoritative implementation brief for Slice C4.

## Purpose

Implement the S5 Assisted Builder visual design as the Release C public request interface, supporting any combination of Clothing, Furniture, and Household Goods, dynamic steps, configurable delivery, stock messaging, and atomic request-group submission.

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

- Depends on completion of C3-shared-fulfillment-and-cashier-status.
- Requires C1, C2, and C3 foundations to exist before wiring final submission and fulfillment assumptions.

## Scope

- Implement multi-select Assistance Needed screen.
- Implement dynamic step array for all seven voucher-type combinations.
- Preserve household and requestor/organization separation.
- Implement Clothing informational step without notes.
- Implement Furniture selection using S5 design rules.
- Implement Household Goods selection from editable catalog with up to 10 categories and quantities.
- Show only Estimated Conference / Partner Cost for Household Goods.
- Show Delivery step only when selected voucher types have delivery enabled.
- Submit one request group with one to three child vouchers atomically.
- Add Review and Confirmation screens with stock fluctuation message.
- Add stronger/high-contrast search inputs and dynamic category-pill scroll arrows.

## Out of Scope

- No new backend schema beyond C1-C3 dependencies.
- No saved draft system.
- No neighbor self-service portal.
- No POS/inventory integration.
- No delivery dispatch/routing.
- No Vincentian notes.

## Likely Protected Surfaces

These are expected targets. Confirm exact paths against the current repo before editing.

- public/templates/voucher-request-form.php
- public/js/voucher-request.js
- public/css/voucher-request.css or existing stylesheet
- includes/class-voucher.php
- includes/class-furniture-catalog.php
- includes/class-household-goods-catalog.php
- includes/class-voucher-request-group.php
- includes/class-voucher-type-settings.php
- public/templates/partials/assisted-builder/\* (new if template decomposition is used)
- contracts/protected-contracts.json

## Acceptance Criteria

- All seven voucher combinations render correct dynamic steps.
- No step numbering is hardcoded.
- Deselecting a voucher type with entered data requires confirmation and clears that type's data.
- Furniture and Household Goods search fields are high contrast and visibly labeled.
- Category/browse-group pills show dynamic overflow arrows correctly.
- Household Goods card values do not expose unit, retail, shelf, or spendable amounts.
- Review shows Estimated Conference / Partner Cost only.
- Delivery appears once only when selected types are delivery eligible.
- Delivery review shows Not selected or the configured fee, never $0.00.
- Stock message appears on Review and Confirmation when Furniture or Household Goods is selected.
- Submission creates one request group and the correct child vouchers atomically.

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
