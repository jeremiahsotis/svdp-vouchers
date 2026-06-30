# Data Evolution Log

## YYYY-MM-DD

Change:
Impact:
Backfill required:
Risk:
Mitigation:

## 2026-06-22 - Slice C4 Three-Voucher Assisted Builder UI and Request Submission

Change: Exposed Release C public request-group submission through the Assisted Builder. New grouped submissions create child Clothing, Furniture, and Household Goods voucher rows through the existing request-group transaction, and new Furniture/Household Goods child vouchers receive shared requested-line snapshots at issuance. Added a public active Household Goods catalog payload for the builder. No schema version or database table definition changed.
Impact: Future public requests can create one request group with one to three independently redeemable child vouchers. New line-backed Furniture and Household Goods child vouchers are ready for the C3 shared cashier fulfillment workspace. Legacy standalone Clothing/Furniture vouchers and legacy `household` voucher values remain unchanged.
Backfill required: No. Historical voucher records, Furniture item rows, delivery records, receipts, invoices, and legacy `household` values are not rewritten or reinterpreted.
Risk: Existing installs may have stored the old default voucher-type configuration of Clothing plus Furniture before Household Goods existed.
Mitigation: The public request availability helper treats the old non-store two-type default as Release C's three-type default for future requests only, while still preserving explicit store-only Clothing behavior and without mutating stored historical records.

## 2026-06-19 - Slice C3 Shared Fulfillment and Cashier Status

Change: Added schema version 12 with `wp_svdp_voucher_requested_lines`, `wp_svdp_voucher_fulfillment_entries`, `wp_svdp_unavailable_reasons`, and `wp_svdp_voucher_fulfillment_audit`. Added voucher-level `finalized_at`, `finalized_by_user_id`, `finalization_note`, `finalization_note_by_user_id`, `finalization_note_at`, and `receipt_file_path` fields. Seeded starter structured unavailable reasons when the reason table is empty.
Impact: New Release C Furniture and Household Goods vouchers can use requested lines, multiple fulfillment price rows, unavailable quantities/reasons, Save Progress, Finalize Voucher, internal voucher-level finalization notes, and audit rows. Legacy Furniture voucher item records remain valid and continue through the legacy display/document path unless a voucher has shared requested lines.
Backfill required: No. Historical Clothing, Furniture, delivery, invoice, receipt, completion-note, and legacy `household` records are not rewritten, reinterpreted, or migrated into the shared fulfillment tables.
Risk: C4 builder work must create requested-line snapshots for new Release C child vouchers; without requested lines, Household Goods and new shared Furniture vouchers cannot be fulfilled through the C3 workspace.
Mitigation: Shared fulfillment routes refuse vouchers without requested lines, legacy Furniture routes remain available for historical vouchers, finalization requires the requested quantity invariant, and fulfillment/finalization events write audit rows.

## 2026-06-19 - Slice C2 Household Goods Catalog and Limits

Change: Added schema version 11 with `wp_svdp_household_goods_browse_groups`, `wp_svdp_household_goods_catalog`, and `wp_svdp_configuration_audit`. Added the `household_goods_voucher_quantity_max` setting with `0` as no limit. Seeded starter Household Goods browse groups and catalog categories from the Release C product contract examples when the Household Goods catalog is empty.
Impact: Authorized administrators can manage future Household Goods browse groups, categories, cost estimates, quantity limits, archive state, and configuration audit history. No public Household Goods builder step or cashier fulfillment workflow is exposed in this slice.
Backfill required: No. Historical voucher, Furniture, delivery, and legacy `household` records are not rewritten or reinterpreted.
Risk: Later slices must use request-time snapshots from the catalog helper and must not recalculate issued vouchers after catalog or limit changes.
Mitigation: Catalog mutations write configuration audit rows; active-only request data excludes archived groups/categories; snapshot helper preserves category name, browse group, estimated cost, quantity cap, guidance, sort order, and voucher-wide limit at issuance time.

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
