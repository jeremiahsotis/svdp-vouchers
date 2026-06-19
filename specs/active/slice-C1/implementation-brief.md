# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Slice C1 - Request Groups, Multi-Voucher Foundation, and Delivery Capabilities

## Status

Authoritative implementation brief for Slice C1.

## Purpose

Create the backend foundation for one request submission creating one request group with one to three linked child vouchers, plus configurable voucher-type delivery eligibility and group-level delivery snapshots.

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

- Depends on completion of C0-release-contract-and-planning.

## Scope

- Add request group schema.
- Add child-voucher request_group_id link and uniqueness protection for one voucher of each type per group.
- Add voucher-type capability settings, including delivery availability.
- Add group-level delivery record schema.
- Seed initial voucher type capability settings: Clothing delivery off, Furniture delivery on, Household Goods delivery on.
- Implement atomic service for creating a request group and child vouchers without yet wiring the public Assisted Builder UI.
- Maintain compatibility for standalone legacy vouchers with no request group.

## Out of Scope

- No Household Goods catalog/admin UI.
- No Household Goods request UI.
- No cashier fulfillment workspace.
- No Assisted Builder visual migration.
- No new dispatch/routing/delivery attempts.

## Likely Protected Surfaces

These are expected targets. Confirm exact paths against the current repo before editing.

- includes/class-database.php
- includes/class-voucher.php
- includes/class-delivery.php or existing delivery service/class
- includes/class-voucher-request-group.php (new, if repo style permits)
- includes/class-voucher-type-settings.php (new, if repo style permits)
- admin/settings templates for voucher type delivery settings, if minimal admin is included
- contracts/protected-contracts.json
- docs/data-governance/data-evolution-log.md
- docs/state/change-impact-log.md

## Acceptance Criteria

- Request groups can be created with one, two, or three child vouchers in backend tests/manual harness.
- Database prevents duplicate voucher types within one request group.
- Legacy vouchers without request_group_id still display and function.
- Delivery eligibility is read from configuration, not hardcoded to furniture.
- Delivery is stored once per request group and snapshots fee, address, eligible voucher types, and selected voucher types.
- Changing delivery settings affects future requests only.
- Creation failure in any child voucher prevents the whole group from being committed.

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

Slice C1 binds to the Release C Product Contract.

Required C1 terms:

- request group
- request_group_id
- multi-voucher
- child voucher
- delivery capability
- delivery eligibility
- delivery snapshot
- one delivery fee
- one delivery attempt
- clothing
- furniture
- household_goods
- atomic
- rollback
- backward compatibility

C1 implements the backend foundation for request groups, multi-voucher child creation, delivery capability lookup, and group-level delivery snapshots. C1 does not implement C2 catalog behavior, C3 cashier fulfillment behavior, or C4 Assisted Builder runtime behavior.
