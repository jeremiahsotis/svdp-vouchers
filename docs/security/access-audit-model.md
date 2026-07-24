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

## Slice P1 Implementation Note

Furniture category mutations require `svdp_manage_furniture_catalog`; Household Goods catalog and limit mutations retain their dedicated capabilities. Category, pricing, coverage, archive, and limit changes write human-readable configuration audit rows with before/after values, actor, and timestamp.

## Slice C1 Implementation Note

The C1 request-group service is backend-only and is not exposed through a public REST route or UI. Request-group rows snapshot requestor, household, Conference, submitted timestamp, and created-by source. Voucher-type capability rows preserve the last updater and update timestamp for future configuration changes.

## Slice C2 Implementation Note

Household Goods catalog and limit mutations require explicit capabilities:

- `svdp_manage_household_goods_catalog`
- `svdp_manage_household_goods_limits`
- `svdp_view_voucher_configuration_audit`

Administrators receive these capabilities by default. Cashiers and Vincentians do not receive Household Goods configuration authority. C2 configuration changes write read-only audit rows with record type, record identifier, record name snapshot, changed field, before value, after value, actor, timestamp, and a human summary.

## Slice C3 Implementation Note

Shared Furniture and Household Goods fulfillment mutations require the existing cashier access and furniture-redemption capability checks. Vincentians receive no cashier, catalog, unavailable-reason, or finalization authority in C3.

Save Progress and Finalize Voucher write `wp_svdp_voucher_fulfillment_audit` rows with voucher, event type, actor, timestamp, human summary, and serialized after-state. Finalization stores the optional Internal Finalization Note only on the voucher row with author and timestamp, and external receipts/invoices omit that note.

## Slice C4 Implementation Note

The Assisted Builder exposes public request-group submission with the same public request posture as the existing Vincentian voucher creation endpoints. It does not grant cashier, fulfillment, catalog administration, unavailable-reason, finalization, receipt, invoice, or configuration authority to Vincentians.

Request-group creation snapshots household, requestor, Conference/Organization, selected voucher types, delivery choice, delivery fee, address verification data, and requested Furniture/Household Goods lines. C4 does not introduce unrestricted Vincentian notes or item-level cashier notes.

## Slice A1 Implementation Note

Accounting views and mutations require `svdp_manage_accounting`, granted to administrators, voucher managers, and the limited `svdp_bookkeeper` role. Neighbor voucher printing requires existing cashier access plus a per-voucher nonce. Accounting, delivery, download, configuration, denial, and print events write actor/source, resource, decision, timestamp, error, and human-readable audit context; rendered neighbor contact data is not retained in the print audit.
