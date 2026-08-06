# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Slice C3 - Shared Fulfillment Workspace, Voucher Finalization, and Cashier Card Status

## Status

Authoritative implementation brief for Slice C3.

## Purpose

Replace item-by-item Furniture completion with the Release C one-screen fulfillment workspace for Furniture and Household Goods, including multiple price rows, derived not-fulfilled quantities, Save Progress, Finalize Voucher, optional internal finalization note, and clearer cashier card statuses.

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

- Depends on completion of C2-household-goods-catalog-and-limits.

## Scope

- Add shared requested-line and fulfillment-entry model for new Release C Furniture and Household Goods vouchers.
- Implement one-screen fulfillment workspace in cashier detail.
- Allow multiple inline price rows per requested line.
- Capture Price Each, fulfilled quantity, calculated line total, and derived not-fulfilled quantity.
- Enforce fulfilled quantity never exceeds requested quantity before save or finalization.
- Add Save Progress and Finalize Voucher behavior.
- Add optional voucher-level Internal Finalization Note before finalization.
- Remove Furniture item completion Notes from new workflow.
- Improve cashier cards while preserving card architecture: READY TO REDEEM, REDEEMED, EXPIRED.
- Preserve legacy Furniture voucher display and receipt behavior.

## Out of Scope

- No public Assisted Builder migration.
- No Household Goods public request creation unless test fixtures are needed.
- No POS integration.
- No barcode/SKU/live inventory.
- No unrestricted item-level notes.
- No table replacement for cashier cards.

## Likely Protected Surfaces

These are expected targets. Confirm exact paths against the current repo before editing.

- includes/class-database.php
- includes/class-furniture-voucher.php
- includes/class-voucher.php
- includes/class-invoice.php
- includes/class-household-goods-fulfillment.php (new, if needed)
- public/js/cashier-shell.js
- public/templates/cashier/partials/voucher-card.php or existing card template
- public/templates/cashier/partials/voucher-detail-furniture.php
- public/templates/cashier/partials/voucher-detail-household-goods.php (new)
- public/templates/documents/furniture-receipt.php
- public/templates/documents/household-goods-receipt.php (new, if separate)
- contracts/protected-contracts.json

## Acceptance Criteria

- Cashier cards show READY TO REDEEM, REDEEMED, and EXPIRED as large, high-contrast labels with non-color cues.
- Redeemed vouchers remain REDEEMED after expiration date passes.
- Expired unredeemed vouchers cannot be redeemed through ordinary workflow.
- Furniture and Household Goods fulfillment can be completed on one screen.
- Cashier can add multiple price rows inline without modal/page transitions.
- Line totals calculate automatically.
- Requested units left unfulfilled require no reason and are recorded as not fulfilled.
- Save Progress does not redeem voucher or generate final documents.
- Finalize is blocked only when fulfilled quantities exceed requested quantities or positive fulfilled quantities are missing valid prices.
- Internal Finalization Note is voucher-level only and hidden from receipt/invoice.
- Legacy Furniture vouchers retain historical behavior.

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

## Release C Product Contract anchors

Slice C3 is governed by the Release C Product Contract for shared cashier fulfillment, voucher finalization, cashier card status display, auditability, and backward compatibility.

The shared fulfillment workspace must preserve these required terms and behaviors:

- Fulfilled quantity is recorded per Price Each row.
- Not-fulfilled quantity is derived per requested line.
- Fulfillment in Progress appears as the secondary workflow state when Save Progress has saved valid fulfillment rows but the voucher has not been finalized.
- Status precedence is redeemed/finalized first, expired second, and ready to redeem third.
- A redeemed voucher stays redeemed even after its original expiration date passes.
- Backward compatibility must preserve existing Clothing vouchers, existing Furniture vouchers, legacy standalone Furniture workflows, and legacy household records that continue to resolve as Furniture history.

## Exact contract language for automated checks

The C3 implementation preserves status precedence exactly as required by the Release C Product Contract.

The cashier status rule is: redeemed stays redeemed, expired applies only to unredeemed expired vouchers, and ready to redeem applies to valid unredeemed vouchers.
