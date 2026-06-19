# ADR-0003: Release C Household Goods Catalog and Limits

## Status

Accepted

## Date

2026-06-19

## Context

Release C requires Household Goods to be a distinct operational catalog with browse groups, estimated Conference / Partner cost per unit, category quantity limits, voucher-wide quantity limits, active/archive behavior, request-time snapshots, and configuration audit.

Slice C2 is intentionally admin/backend-only. The public Assisted Builder Household Goods step and shared cashier fulfillment workspace are reserved for later slices.

## Decision

Slice C2 adds:

- `wp_svdp_household_goods_browse_groups`
- `wp_svdp_household_goods_catalog`
- `wp_svdp_configuration_audit`
- `household_goods_voucher_quantity_max` setting
- `SVDP_Household_Goods_Catalog` service
- Household Goods admin tab and AJAX mutation flows

Browse groups and categories are archived/restored with an `active` flag. Category and voucher-wide quantity values use `0` as no limit. The fixed selected-category limit remains hardcoded at 10 as required by the product contract.

Configuration changes create audit rows. The snapshot helper returns immutable request-line data for later request creation without implementing the C3 public builder or C4 fulfillment workflow.

## Consequences

Administrators can manage Household Goods configuration before the public request flow is wired. Future slices must consume active-only catalog data for new requests and must snapshot values at issuance.

Historical Clothing, Furniture, and legacy `household` voucher behavior remains unchanged. No delivery logistics, inventory, SKU, POS, unrestricted notes, public Household Goods builder step, or cashier fulfillment workspace is introduced by C2.

## Protected Surfaces Affected

- `includes/class-database.php`
- `includes/class-household-goods-catalog.php`
- `includes/class-permissions.php`
- `includes/class-admin.php`
- `admin/views/admin-page.php`
- `admin/views/tab-household-goods.php`
- `admin/js/household-goods-admin.js`
- `svdp-vouchers.php`
- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`
- `docs/architecture/concurrency-model.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `planning/release-c-slice-map.md`
- `specs/active/slice-C2/implementation-brief.md`
- `specs/active/slice-C2/codepack.md`
- `specs/active/slice-C2/checkpoint-01.md`
- `specs/active/slice-C2/bootstrap.md`

## Superseded by

Not superseded.

## Alternatives considered

1. Store Household Goods as Furniture catalog categories.
   Rejected because the Release C contract makes Household Goods a distinct root voucher type with separate catalog and quantity semantics.

2. Wait until C3/C4 to add snapshots.
   Rejected because C2 requires snapshot helpers and configuration audit before public request submission depends on the catalog.

3. Add a full shared requested-line table in C2.
   Rejected because shared fulfillment/request-line persistence belongs to the later fulfillment slice. C2 provides snapshot helpers without implementing C3/C4 behavior.

## Contracts affected

- `contracts/protected-contracts.json`
- `contracts/protected-surfaces.json`
- `contracts/protected-surface-acceptance.json`
- Release C Household Goods catalog contract
- Data migration and backward-compatibility contracts
- Access audit model for configuration mutation

## Docs affected

- `docs/adr/ADR-0003-release-c-household-goods-catalog-and-limits.md`
- `docs/architecture/concurrency-model.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `planning/release-c-slice-map.md`
- `specs/active/slice-C2/checkpoint-01.md`
