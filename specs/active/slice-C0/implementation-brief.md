# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

# Slice C0 - Release C Contract Binding and Slice Rebaseline

## Status

Authoritative implementation brief for Slice C0.

## Purpose

Bind the consolidated Release C Product Contract into the repo, supersede old S5 single-voucher assumptions, and prepare the implementation sequence without runtime feature changes.

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

- None. This is the first Release C slice.

## Scope

- Add Release C product contract to repo documentation.
- Create or update Release C planning index.
- Record that S5 visual and interaction decisions remain binding, while S5's former single-voucher backend limitation is superseded.
- Update protected-surface and protected-contract planning documents to include request groups, Household Goods, configurable delivery, fulfillment entries, and cashier status display.
- Create slice dependency map C1-C5.
- Confirm no runtime feature code changes are made in C0.

## Out of Scope

- No schema changes.
- No Assisted Builder implementation.
- No cashier UI implementation.
- No Household Goods catalog implementation.
- No delivery logic changes.
- No financial document changes.

## Likely Protected Surfaces

These are expected targets. Confirm exact paths against the current repo before editing.

- docs/product/release-c-product-contract.md
- docs/decisions/release-c-s5-rebaseline.md
- planning/release-c-slice-map.md
- contracts/protected-surfaces.json
- contracts/protected-contracts.json
- contracts/protected-surface-acceptance.json
- docs/state/change-impact-log.md

## Acceptance Criteria

- Release C contract exists in repo and is referenced by planning docs.
- S5 is explicitly rebaselined: design remains binding, single-voucher backend assumption is superseded.
- C1-C5 dependency order is documented.
- Protected contracts identify request group creation, Household Goods catalog, delivery snapshots, fulfillment entries, finalization notes, receipt/invoice behavior, and cashier card status.
- No PHP, JavaScript, template, or schema runtime behavior changes occur.

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

## Release C Locked UI Terms

Slice C0 binds the following Release C terminology from the Product Contract:

- Cashier-facing valid voucher status: READY TO REDEEM
- Cashier-facing finalized voucher status: REDEEMED
- Cashier-facing expired voucher status: EXPIRED
- Household Goods request estimate label: Estimated Conference / Partner Cost

These are contract terms for later implementation slices. C0 does not implement the runtime UI behavior.

## Release C Locked UI and Estimate Terms

Slice C0 binds the following Release C terms from the Product Contract for later implementation slices:

- Cashier-facing valid voucher status: READY TO REDEEM
- Cashier-facing finalized voucher status: REDEEMED
- Cashier-facing expired voucher status: EXPIRED
- Household Goods request estimate label: Estimated Conference / Partner Cost

These are contract terms for later implementation slices. C0 does not implement runtime UI behavior.
