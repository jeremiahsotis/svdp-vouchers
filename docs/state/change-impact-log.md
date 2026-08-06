# Change Impact Log

Advisory impact summaries.

## 2026-06-19 - Slice C0 Release C Contract Binding

Changed surfaces:

- `docs/product/release-c-product-contract.md`
- `docs/decisions/release-c-s5-rebaseline.md`
- `planning/release-c-slice-map.md`
- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`
- `docs/data-governance/migration-policy.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`
- `scripts/check_no_placeholders.py`
- `specs/active/slice-C0/*`

Impact summary:

Slice C0 binds the Release C Product Contract into the repository, rebaselines S5 so its design remains binding while the single-voucher backend assumption is superseded, and documents C1-C5 dependency order.

Runtime impact:

None. No PHP, JavaScript, template, schema, REST, database, cashier, public-builder, receipt, invoice, or delivery behavior changed.

Governance impact:

Protected contracts now identify Release C request group creation, Household Goods catalog behavior, delivery snapshots, fulfillment entries, finalization notes, receipt/invoice behavior, and cashier card status.

The unfinished-text validator now ignores generated recon transcripts, vendor directories, and known source-code/UI hint-attribute patterns so the canonical check can distinguish unfinished planning text from intentional UI attributes and historical generated artifacts.

## 2026-06-19 - Slice C1 Request Groups and Delivery Foundation

Changed surfaces:

- `includes/class-database.php`
- `includes/class-settings.php`
- `includes/class-voucher.php`
- `includes/class-voucher-request-group.php`
- `includes/class-voucher-type-settings.php`
- `scripts/c1-request-group-manual-harness.php`
- `specs/active/slice-C1/checkpoint-01.md`
- `svdp-vouchers.php`
- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`
- `docs/data-governance/data-evolution-log.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`

Impact summary:

Slice C1 adds the backend foundation for Release C request groups, grouped delivery snapshots, and voucher-type delivery capabilities. It recognizes the explicit `household_goods` root type for new Release C records while preserving legacy `household` as Furniture history.

Runtime impact:

The public Assisted Builder, cashier workspace, Household Goods catalog UI, and delivery logistics behavior are unchanged. Existing standalone vouchers remain valid because `request_group_id` is nullable.

Governance impact:

Protected contracts now register the new request-group and voucher-type settings classes. Data governance records the schema version 10 migration and the no-backfill/no-reinterpretation compatibility rule.

## 2026-06-19 - Slice C2 Household Goods Catalog and Limits

Changed surfaces:

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
- `docs/data-governance/data-evolution-log.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`
- `docs/adr/ADR-0003-release-c-household-goods-catalog-and-limits.md`
- `specs/active/slice-C2/checkpoint-01.md`

Impact summary:

Slice C2 adds admin-managed Household Goods browse groups, catalog categories, category limits, voucher-wide requested quantity limits, active/archive behavior, request-line snapshot helpers, and configuration audit.

Runtime impact:

The public Assisted Builder, request-group submission UI, cashier fulfillment workspace, receipts, invoices, delivery logistics, and legacy Clothing/Furniture voucher behavior are unchanged.

Governance impact:

Protected contracts now register the Household Goods catalog service, admin UI, and admin JavaScript mutation flow. Data governance records schema version 11 and the no-backfill/no-recalculation rule for future Household Goods snapshots.

## 2026-06-19 - Slice C3 Shared Fulfillment Workspace and Cashier Status

Changed surfaces:

- `includes/class-database.php`
- `includes/class-household-goods-fulfillment.php`
- `includes/class-voucher.php`
- `includes/class-furniture-voucher.php`
- `includes/class-invoice.php`
- `includes/class-cashier-shell.php`
- `svdp-vouchers.php`
- `public/js/cashier-shell.js`
- `public/js/cashier-station.js`
- `public/css/voucher-forms.css`
- `public/templates/cashier/partials/voucher-card.php`
- `public/templates/cashier/partials/voucher-detail.php`
- `public/templates/cashier/partials/voucher-detail-furniture.php`
- `public/templates/cashier/partials/voucher-detail-shared-fulfillment.php`
- `public/templates/documents/furniture-receipt.php`
- `public/templates/documents/furniture-invoice.php`
- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`
- `docs/data-governance/data-evolution-log.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`
- `planning/release-c-slice-map.md`
- `specs/active/slice-C3/checkpoint-01.md`

Impact summary:

Slice C3 adds the shared requested-line and fulfillment-entry model, one-screen cashier fulfillment workspace, Save Progress, Finalize Voucher, derived not-fulfilled quantities, internal voucher-level finalization note, and cashier card status labels for READY TO REDEEM, REDEEMED, and EXPIRED.

Runtime impact:

Line-backed Furniture and Household Goods vouchers use the shared fulfillment workspace. Legacy Furniture vouchers without shared requested lines keep the existing item-resolution and document path. Redeemed vouchers take status precedence over expiration, and unredeemed expired vouchers are blocked from ordinary redemption/finalization.

Governance impact:

Protected contracts now register the shared fulfillment service and template. Data governance records schema version 12 with no destructive backfill, concurrency records last-write Save Progress before finalization, and access audit records fulfillment/finalization audit behavior.

2026-08-06 support update: Shared fulfillment now prevents fulfilled quantities above requested quantities live in the cashier workflow and on the server. Item Unavailable/reason selection is deprecated for ordinary new fulfillment; remaining requested units are recorded as not fulfilled without a reason. Fixed-price requested snapshots prefill redemption prices while remaining editable.

## 2026-06-22 - Slice C4 Three-Voucher Assisted Builder UI and Request Submission

Changed surfaces:

- `includes/class-settings.php`
- `includes/class-voucher-request-group.php`
- `public/templates/voucher-request-form.php`
- `public/js/voucher-request.js`
- `public/css/voucher-forms.css`
- `svdp-vouchers.php`
- `docs/data-governance/data-evolution-log.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `docs/future-governance/cross-slice-impact.md`
- `specs/active/slice-C4/checkpoint-01.md`

Impact summary:

Slice C4 replaces the public single-voucher request form with the Release C Assisted Builder. The builder supports Clothing, Furniture, and Household Goods in any valid combination; derives visible steps dynamically; shows one configuration-driven Delivery step; displays Review and Confirmation content; and submits one atomic request group with child vouchers.

Runtime impact:

Public grouped submissions now use `POST /svdp/v1/vouchers/request-group`. New Furniture and Household Goods child vouchers receive shared requested-line snapshots for the C3 fulfillment workspace. The existing legacy voucher creation endpoint remains available for standalone Clothing/Furniture behavior outside the new builder.

Governance impact:

No schema migration was introduced. Data governance records no backfill/no reinterpretation of historical records. Concurrency records atomic grouped submission, and access audit records that the public builder does not grant cashier or admin authority.

## 2026-07-20 - Slice P1 Unified Priced Catalogs

Changed surfaces: schema/upgrade, Furniture and Household Goods catalog services and administration, request-line issuance, and Assisted Builder catalog cards.

Impact summary: Furniture and Household Goods share priced selection cards; Household Goods limits are configurable; Furniture categories are editable, archive-safe, conflict-guarded, and audited.

Runtime impact: Schema 13 performs additive catalog migration while preserving issued snapshots, Clothing, legacy `household`, receipts, and invoices.

Governance impact: P1 includes an implementation brief, bootstrap, checkpoint, migration record, concurrency rule, and access-audit rule.
