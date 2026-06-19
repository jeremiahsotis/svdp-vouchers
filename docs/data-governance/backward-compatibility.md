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
