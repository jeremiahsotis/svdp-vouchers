# Backward Compatibility

## Rules

Define for each affected surface:

- what can break immediately
- what must be phased
- what must remain stable

## Default

- API contracts: versioned or stable by explicit rule
- DB: must not corrupt existing data
- tenant data: must remain valid when demo or seed data is removed

## Release C Compatibility Rules

Slice C0 is documentation-only and does not change runtime compatibility.

Future Release C slices must preserve:

- existing Clothing voucher behavior;
- existing Furniture voucher behavior, including historical fulfillment, receipts, invoices, and completion notes where already present;
- historical vouchers that do not have a request group;
- legacy `household` values as Furniture history, not Household Goods.

Configuration changes introduced by Release C must affect future records only unless the Release C Product Contract explicitly says otherwise.

## Slice C1 Compatibility Notes

Slice C1 adds nullable request-group linkage to voucher rows. Existing Clothing and Furniture vouchers without `request_group_id` remain valid and continue through the legacy display and redemption paths.

The explicit new `household_goods` root type is recognized for Release C request-group foundation work. Legacy `household` values continue to normalize to Furniture history and are not converted to Household Goods.

Voucher-type delivery capabilities and delivery fee lookups affect future request-group snapshots. Existing Furniture voucher meta, receipts, invoices, and delivery fee snapshots are not recalculated.

## Slice C2 Compatibility Notes

Slice C2 adds Household Goods catalog/configuration tables and admin-only mutation surfaces. Existing Clothing and Furniture voucher behavior remains unchanged, including the legacy Furniture catalog data and historical `household` normalization as Furniture history.

Household Goods catalog and limit changes affect future requests only. Issued Household Goods request lines in later slices must use the C2 snapshot helper so later catalog edits, archive/restore actions, or limit changes do not alter historical vouchers.

## Slice C3 Compatibility Notes

Slice C3 adds shared requested-line and fulfillment-entry tables for new Release C Furniture and Household Goods vouchers. Existing Furniture vouchers that only have legacy `wp_svdp_voucher_items` rows continue to use the legacy Furniture detail, receipt, and invoice behavior.

Household Goods and shared Furniture fulfillment require requested-line snapshots. C3 does not backfill historical Furniture items into the new tables and does not reinterpret legacy `household` voucher values as Household Goods.

Voucher finalization notes are voucher-level, internal-only, and not printed on neighbor receipts or Conference/Partner invoices. Existing historical Furniture completion notes remain preserved on legacy records.
