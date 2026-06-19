# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.


# Codepack - Slice C0 - Release C Contract Binding and Slice Rebaseline

## Task

Implement Slice C0 according to the matching `implementation-brief.md`.

## Preflight

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

- docs/product/release-c-product-contract.md
- docs/decisions/release-c-s5-rebaseline.md
- planning/release-c-slice-map.md
- contracts/protected-surfaces.json
- contracts/protected-contracts.json
- contracts/protected-surface-acceptance.json
- docs/state/change-impact-log.md

## Implementation Guardrails

- No schema changes.
- No Assisted Builder implementation.
- No cashier UI implementation.
- No Household Goods catalog implementation.
- No delivery logic changes.
- No financial document changes.

## Slice-Specific Build Requirements

- Release C contract exists in repo and is referenced by planning docs.
- S5 is explicitly rebaselined: design remains binding, single-voucher backend assumption is superseded.
- C1-C5 dependency order is documented.
- Protected contracts identify request group creation, Household Goods catalog, delivery snapshots, fulfillment entries, finalization notes, receipt/invoice behavior, and cashier card status.
- No PHP, JavaScript, template, or schema runtime behavior changes occur.

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
