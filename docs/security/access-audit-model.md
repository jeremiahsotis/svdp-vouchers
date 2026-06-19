# Access Audit Model

## Rule

All auth and policy decisions must be traceable and explainable.

## Required fields

- decision: allow or deny
- reason
- actor
- resource
- timestamp

## Release C Audit Rule

Slice C0 adds planning constraints only.

Future Release C protected mutations must preserve auditability for request group creation, delivery snapshots, Household Goods catalog changes, fulfillment entries, finalization, finalization notes, receipt and invoice generation, and configuration changes.

Vincentians must not receive cashier or administrator authority through the Assisted Builder.

## Slice C1 Implementation Note

The C1 request-group service is backend-only and is not exposed through a public REST route or UI. Request-group rows snapshot requestor, household, Conference, submitted timestamp, and created-by source. Voucher-type capability rows preserve the last updater and update timestamp for future configuration changes.

## Slice C2 Implementation Note

Household Goods catalog and limit mutations require explicit capabilities:

- `svdp_manage_household_goods_catalog`
- `svdp_manage_household_goods_limits`
- `svdp_view_voucher_configuration_audit`

Administrators receive these capabilities by default. Cashiers and Vincentians do not receive Household Goods configuration authority. C2 configuration changes write read-only audit rows with record type, record identifier, record name snapshot, changed field, before value, after value, actor, timestamp, and a human summary.
