# Data Evolution Log

## YYYY-MM-DD

Change:
Impact:
Backfill required:
Risk:
Mitigation:

## 2026-06-19 - Slice C1 Request Groups and Delivery Foundation

Change: Added schema version 10 with `wp_svdp_voucher_request_groups`, `wp_svdp_voucher_request_group_delivery`, `wp_svdp_voucher_type_capabilities`, and nullable `request_group_id` on `wp_svdp_vouchers` with a unique `(request_group_id, voucher_type)` key. Seeded delivery capability defaults of Clothing off, Furniture on, and Household Goods on.
Impact: New grouped requests can snapshot shared household/requestor data, link one to three child vouchers, and store one delivery snapshot per group. Existing vouchers remain valid with `request_group_id = NULL`.
Backfill required: No. Historical voucher records and legacy `household` values are not rewritten or reinterpreted.
Risk: Future slices must avoid duplicating delivery fees or treating the new `household_goods` root type as a Furniture catalog category.
Mitigation: Database uniqueness prevents duplicate child voucher types per group, delivery eligibility is read from `svdp_voucher_type_capabilities`, and grouped creation uses a transaction so failed child creation rolls back the group.

## 2026-06-19

Change: Added Release C product contract and planning constraints for future request groups, Household Goods catalog, delivery snapshots, fulfillment entries, finalization notes, receipt/invoice behavior, and cashier card status.
Impact: Documentation-only. No schema, migration, runtime, PHP, JavaScript, template, or database behavior changed in Slice C0.
Backfill required: No.
Risk: Future slices must not reinterpret legacy `household` records as `household_goods` and must preserve existing Clothing and Furniture history.
Mitigation: Protected contracts and Release C slice map now state the hard-stop rules before implementation begins.
