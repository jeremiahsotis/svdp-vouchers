# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.
**Repository:** WordPress plugin, `svdp-vouchers`.
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Codepack - Slice C4 - Three-Voucher Assisted Builder UI and Request Submission

## Task

Implement Slice C4 according to the matching `implementation-brief.md`.

## Preflight

- Confirm prior slice `C3-shared-fulfillment-and-cashier-status` is merged and validation passed.
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

- public/templates/voucher-request-form.php
- public/js/voucher-request.js
- public/css/voucher-request.css or existing stylesheet
- includes/class-voucher.php
- includes/class-furniture-catalog.php
- includes/class-household-goods-catalog.php
- includes/class-voucher-request-group.php
- includes/class-voucher-type-settings.php
- public/templates/partials/assisted-builder/\* (new if template decomposition is used)
- contracts/protected-contracts.json

## Implementation Guardrails

- No new backend schema beyond C1-C3 dependencies.
- No saved draft system.
- No neighbor self-service portal.
- No POS/inventory integration.
- No delivery dispatch/routing.
- No Vincentian notes.

## Slice-Specific Build Requirements

- All seven voucher combinations render correct dynamic steps.
- No step numbering is hardcoded.
- Deselecting a voucher type with entered data requires confirmation and clears that type's data.
- Furniture and Household Goods search fields are high contrast and visibly labeled.
- Category/browse-group pills show dynamic overflow arrows correctly.
- Household Goods card values do not expose unit, retail, shelf, or spendable amounts.
- Review shows Estimated Conference / Partner Cost only.
- Delivery appears once only when selected types are delivery eligible.
- Delivery review shows Not selected or the configured fee, never $0.00.
- Stock message appears on Review and Confirmation when Furniture or Household Goods is selected.
- Submission creates one request group and the correct child vouchers atomically.

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

## Governance File Map

- svdp-vouchers.php
- admin/views/tab-conferences.php
- admin/views/tab-settings.php
- public/css/voucher-forms.css
- includes/class-admin.php
- includes/class-conference.php
- includes/class-settings.php
- includes/class-voucher-request-group.php
- public/js/voucher-request.js
- public/templates/voucher-request-form.php
