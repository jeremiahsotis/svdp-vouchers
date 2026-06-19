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
