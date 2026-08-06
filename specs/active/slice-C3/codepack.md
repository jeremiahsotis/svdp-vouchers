# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Codepack - Slice C3 - Shared Fulfillment Workspace, Voucher Finalization, and Cashier Card Status

## Task

Implement Slice C3 according to the matching `implementation-brief.md`.

## Preflight

- Confirm prior slice `C2-household-goods-catalog-and-limits` is merged and validation passed.
- Run baseline status and validation commands before editing.

## Baseline Validation Commands

Run from the plugin repository root unless a command says otherwise.

```bash
git status --short
git log --oneline -n 12

find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l

python3 scripts/check_required_doc_sections.py
python3 scripts/check_no_placeholders.py

find . -name ".DS_Store" -print
```

If project governance scripts differ in the current repo, use `docs/governance/canonical-commands.json` as the source of truth and record the variance in the checkpoint.

## Work Plan

1. Read `release-c-product-contract.md`.
1. Read this slice's `implementation-brief.md`.
1. Confirm current repo file paths and protected surfaces.
1. Update planning or contracts first where required.
1. Make the smallest coherent implementation changes for this slice only.
1. Update data governance docs for any schema/configuration changes.
1. Update or add tests/manual checkpoints aligned with `checkpoint-01.md`.
1. Run validation commands.
1. Prepare a concise implementation summary.

## Expected Change Areas

- includes/class-database.php
- includes/class-furniture-voucher.php
- includes/class-voucher.php
- includes/class-invoice.php
- includes/class-household-goods-fulfillment.php (new, if needed)
- public/js/cashier-shell.js
- public/templates/cashier/partials/voucher-card.php or existing card template
- public/templates/cashier/partials/voucher-detail-furniture.php
- public/templates/cashier/partials/voucher-detail-household-goods.php (new)
- public/templates/documents/furniture-receipt.php
- public/templates/documents/household-goods-receipt.php (new, if separate)
- contracts/protected-contracts.json

## Implementation Guardrails

- No public Assisted Builder migration.
- No Household Goods public request creation unless test fixtures are needed.
- No POS integration.
- No barcode/SKU/live inventory.
- No unrestricted item-level notes.
- No table replacement for cashier cards.

## Slice-Specific Build Requirements

- Cashier cards show READY TO REDEEM, REDEEMED, and EXPIRED as large, high-contrast labels with non-color cues.
- Redeemed vouchers remain REDEEMED after expiration date passes.
- Expired unredeemed vouchers cannot be redeemed through ordinary workflow.
- Furniture and Household Goods fulfillment can be completed on one screen.
- Cashier can add multiple price rows inline without modal/page transitions.
- Line totals calculate automatically.
- Requested units left unfulfilled require no reason and are recorded as not fulfilled.
- Save Progress does not redeem voucher or generate final documents.
- Finalize is blocked only when fulfilled quantities exceed requested quantities or positive fulfilled quantities are missing valid prices.
- Internal Finalization Note is voucher-level only and hidden from receipt/invoice.
- Legacy Furniture vouchers retain historical behavior.

## Required Developer Summary

At completion, report:

- Files changed
- Schema changes made, if any
- Protected surfaces touched and why
- Acceptance criteria passed
- Validation commands run and result
- Known issues or deferred work
- Confirmation that Release C non-goals were not introduced

## Stop Conditions

Stop and ask for direction if:

- A contract rule conflicts with existing production behavior in a way not covered by this slice.
- Current repo paths differ materially from expected paths.
- A migration would rewrite historical voucher or delivery data.
- Any change would introduce POS, inventory, SKU, dispatch, routing, delivery attempts, or unrestricted notes.
- The slice cannot be completed without implementing scope reserved for a later slice.

## Guardrail Runtime File Map

admin/js/household-goods-admin.js
admin/views/admin-page.php
admin/views/tab-household-goods.php
includes/class-admin.php
includes/class-cashier-shell.php
includes/class-database.php
includes/class-furniture-voucher.php
includes/class-household-goods-catalog.php
includes/class-household-goods-fulfillment.php
includes/class-invoice.php
includes/class-permissions.php
includes/class-voucher.php
public/css/voucher-forms.css
public/js/cashier-shell.js
public/js/cashier-station.js
public/templates/cashier/partials/voucher-card.php
public/templates/cashier/partials/voucher-detail.php
public/templates/cashier/partials/voucher-detail-furniture.php
public/templates/cashier/partials/voucher-detail-shared-fulfillment.php
public/templates/documents/furniture-invoice.php
public/templates/documents/furniture-receipt.php
svdp-vouchers.php
