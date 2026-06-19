# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Codepack - Slice C1 - Request Groups, Multi-Voucher Foundation, and Delivery Capabilities

## Task

Implement Slice C1 according to the matching `implementation-brief.md`.

## Preflight

- Confirm prior slice `C0-release-contract-and-planning` is merged and validation passed.
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
2. Read this slice's `implementation-brief.md`.
3. Confirm current repo file paths and protected surfaces.
4. Update planning or contracts first where required.
5. Make the smallest coherent implementation changes for this slice only.
6. Update data governance docs for any schema/configuration changes.
7. Update or add tests/manual checkpoints aligned with `checkpoint-01.md`.
8. Run validation commands.
9. Prepare a concise implementation summary.

## Expected Change Areas

- includes/class-database.php
- includes/class-voucher.php
- includes/class-delivery.php or existing delivery service/class
- includes/class-voucher-request-group.php (new, if repo style permits)
- includes/class-voucher-type-settings.php (new, if repo style permits)
- admin/settings templates for voucher type delivery settings, if minimal admin is included
- contracts/protected-contracts.json
- docs/data-governance/data-evolution-log.md
- docs/state/change-impact-log.md

## Implementation Guardrails

- No Household Goods catalog/admin UI.
- No Household Goods request UI.
- No cashier fulfillment workspace.
- No Assisted Builder visual migration.
- No new dispatch/routing/delivery attempts.

## Slice-Specific Build Requirements

- Request groups can be created with one, two, or three child vouchers in backend tests/manual harness.
- Database prevents duplicate voucher types within one request group.
- Legacy vouchers without request_group_id still display and function.
- Delivery eligibility is read from configuration, not hardcoded to furniture.
- Delivery is stored once per request group and snapshots fee, address, eligible voucher types, and selected voucher types.
- Changing delivery settings affects future requests only.
- Creation failure in any child voucher prevents the whole group from being committed.

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

includes/class-database.php
includes/class-settings.php
includes/class-voucher.php
includes/class-voucher-request-group.php
includes/class-voucher-type-settings.php
svdp-vouchers.php
