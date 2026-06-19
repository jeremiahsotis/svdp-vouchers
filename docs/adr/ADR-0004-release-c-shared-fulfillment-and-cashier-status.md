# ADR-0004: Release C Shared Fulfillment and Cashier Status

## Status

Accepted

## Date

2026-06-19

## Context

Slice C3 implements the Release C contract requirement that new Furniture and Household Goods vouchers use one cashier fulfillment workspace with requested lines, multiple price rows, unavailable quantities, structured unavailable reasons, Save Progress, Finalize Voucher, and an optional voucher-level Internal Finalization Note.

Historical Furniture vouchers already use `wp_svdp_voucher_items` and document generation based on completed/cancelled item outcomes. The Release C contract requires those records to remain valid and not be destructively migrated.

## Decision

Add additive schema version 12 tables for:

- `wp_svdp_voucher_requested_lines`
- `wp_svdp_voucher_fulfillment_entries`
- `wp_svdp_unavailable_reasons`
- `wp_svdp_voucher_fulfillment_audit`

Add voucher-level finalization fields for finalized timestamp, actor, internal finalization note, note actor/timestamp, and stored neighbor receipt path.

Route vouchers with shared requested lines, plus Household Goods vouchers, to a new one-screen shared fulfillment template. Keep legacy Furniture vouchers without shared requested lines on the existing Furniture detail path.

Resolve cashier-facing status with this precedence:

1. Redeemed
2. Expired
3. Ready to Redeem

## Consequences

Save Progress replaces unfinalized fulfillment entries for each requested line using last-write-wins behavior and writes audit rows.

Finalize Voucher requires every requested line to satisfy:

```text
requested quantity = fulfilled quantity + unavailable quantity
```

Finalization records actual redemption totals, locks ordinary editing by setting the voucher to `Redeemed`, generates/enables receipt and invoice documents, and stores the optional Internal Finalization Note only on the voucher row. Receipts and invoices omit the Internal Finalization Note.

Historical Furniture vouchers, legacy `household` voucher values, existing completion notes, and existing receipts/invoices are not rewritten or reinterpreted.

## Protected Surfaces Affected

- `includes/class-database.php`
- `includes/class-household-goods-fulfillment.php`
- `includes/class-voucher.php`
- `includes/class-furniture-voucher.php`
- `includes/class-invoice.php`
- `includes/class-cashier-shell.php`
- `svdp-vouchers.php`
- `public/js/cashier-shell.js`
- `public/js/cashier-station.js`
- `public/templates/cashier/partials/voucher-card.php`
- `public/templates/cashier/partials/voucher-detail.php`
- `public/templates/cashier/partials/voucher-detail-furniture.php`
- `public/templates/cashier/partials/voucher-detail-shared-fulfillment.php`
- `public/templates/documents/furniture-receipt.php`
- `public/templates/documents/furniture-invoice.php`
- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`
- `docs/architecture/concurrency-model.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `planning/release-c-slice-map.md`
- `specs/active/slice-C3/checkpoint-01.md`

## Superseded by

Not superseded.

## Alternatives considered

1. Reuse only `wp_svdp_voucher_items` for Household Goods.
   Rejected because the product contract requires a shared requested-line model and states Household Goods must not be forced into the legacy Furniture item table.

2. Backfill historical Furniture voucher items into requested lines.
   Rejected because the migration would rewrite historical fulfillment records and risk changing legacy receipt/invoice behavior.

3. Store one total price per requested line.
   Rejected because the contract requires multiple inline Price Each rows so cashiers can record mixed unit prices without manual calculation.

## Contracts affected

- `contracts/protected-contracts.json`
- `contracts/protected-surfaces.json`
- `contracts/protected-surface-acceptance.json`
- Release C shared fulfillment entries contract
- Release C finalization notes contract
- Release C receipt/invoice behavior contract
- Release C cashier card status contract
- Data migration and backward-compatibility contracts
- Access audit model for fulfillment mutation

## Docs affected

- `docs/adr/ADR-0004-release-c-shared-fulfillment-and-cashier-status.md`
- `docs/architecture/concurrency-model.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `planning/release-c-slice-map.md`
- `specs/active/slice-C3/checkpoint-01.md`

## References

- `docs/product/release-c-product-contract.md`
- `specs/active/slice-C3/implementation-brief.md`
- `specs/active/slice-C3/codepack.md`
- `specs/active/slice-C3/checkpoint-01.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`
