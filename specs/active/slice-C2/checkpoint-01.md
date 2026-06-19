# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Checkpoint 01 - Slice C2 - Household Goods Catalog, Limits, and Administration

## Purpose

Verify that Slice C2 - Household Goods Catalog, Limits, and Administration satisfies its slice contract without introducing out-of-scope behavior.

## Required Review Inputs

- This slice's `implementation-brief.md`
- This slice's `codepack.md`
- `release-c-product-contract.md`
- Git diff for the slice
- Validation command output
- Any schema migration output or logs

## A. Scope Verification

- [x] PASS: Add Household Goods browse group schema and admin management.
- [x] PASS: Add Household Goods catalog schema and admin management.
- [x] PASS: Add estimated Conference / Partner cost per unit.
- [x] PASS: Add per-category quantity max with 0 = no limit.
- [x] PASS: Add voucher-wide Household Goods quantity max with 0 = no limit.
- [x] PASS: Add max 10 selected categories as locked product rule.
- [x] PASS: Add active/archive behavior and prevent hard delete for historically referenced records.
- [x] PASS: Seed starter browse groups and categories from the contract.
- [x] PASS: Create snapshot helpers for issued Household Goods request lines.
- [x] PASS: Add configuration audit entries for catalog and limit changes.

## B. Out-of-Scope Verification

Confirm none of the following were implemented:

- [x] NOT IMPLEMENTED: No public Household Goods builder step yet.
- [x] NOT IMPLEMENTED: No cashier fulfillment workspace yet.
- [x] NOT IMPLEMENTED: No request group UI wiring.
- [x] NOT IMPLEMENTED: No POS/inventory/SKU features.
- [x] NOT IMPLEMENTED: No unrestricted Other category.

## C. Acceptance Criteria Verification

- [x] PASS: Authorized admin can add, edit, archive, restore, and reorder browse groups by sort order.
- [x] PASS: Authorized admin can add, edit, archive, restore, and reorder Household Goods categories by sort order.
- [x] PASS: Catalog categories have estimated Conference / Partner cost per unit, quantity max, active/archive, guidance, and sort order.
- [x] PASS: Archived categories do not appear from `SVDP_Household_Goods_Catalog::get_active_grouped_for_request()` but remain visible in admin/history-capable queries.
- [x] PASS: 0 means no limit for category and voucher-wide limits.
- [x] PASS: Negative cost and quantity values are rejected.
- [x] PASS: Duplicate active category names within the same browse group are rejected.
- [x] PASS: Configuration changes are audited and snapshot helper output is immutable request-time data.

## D. Protected Surface Review

- [x] PASS: Protected surfaces touched are listed in the developer summary.
- [x] PASS: Each protected surface change maps to this slice's implementation brief.
- [x] PASS: No unrelated protected surfaces were changed.
- [x] PASS: Protected contracts were updated where the release behavior changed.
- [x] PASS: Historical behavior was preserved where required.

## E. Migration / Data Safety

- [x] PASS: Schema changes follow the repo migration pattern through `SVDP_Database::maybe_upgrade()`, `dbDelta()`, schema version 11, and idempotent seed logic.
- [x] PASS: Data evolution log is updated for schema/config changes.
- [x] PASS: Historical vouchers remain valid.
- [x] PASS: Legacy `household` records are not reinterpreted as `household_goods`.
- [x] PASS: Snapshot rules are preserved through `SVDP_Household_Goods_Catalog::build_request_line_snapshots()`.
- [x] PASS: No destructive migration occurred.

## F. Audit / Permission Safety

- [x] PASS: New protected mutations write configuration audit entries.
- [x] PASS: Capability checks are used for Household Goods catalog, limits, and audit visibility.
- [x] PASS: Vincentians did not receive cashier/admin authority.
- [x] PASS: Internal Finalization Note was not touched.
- [x] PASS: No new unrestricted Notes field was introduced.

## G. Validation Commands

Record output for:

```bash
git status --short
git log --oneline -n 12
find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l
python3 scripts/check_required_doc_sections.py
python3 scripts/check_no_placeholders.py
find . -name ".DS_Store" -print
```

If commands differ in current repo, cite `docs/governance/canonical-commands.json` and record substituted commands.

```text
git status --short
PASS: Shows intended C2 modified/untracked files only.

git log --oneline -n 12
PASS: Latest commit remains 195b478 Merge pull request #17 from jeremiahsotis/slice/c1-request-groups-multi-voucher-foundation-and-delivery-capabilities.

find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l
PASS: No syntax errors detected.

python3 scripts/check_required_doc_sections.py
PASS: Required doc sections valid.

python3 scripts/check_no_placeholders.py
PASS: No placeholders found.

find . -name ".DS_Store" -print
VARIANCE: Reports pre-existing ignored files also present at baseline:
./.DS_Store
./specs/.DS_Store
./specs/active/.DS_Store

python3 -m py_compile scripts/*.py
PASS: No output.

python3 -m json.tool contracts/protected-surfaces.json
PASS: JSON valid.

python3 -m json.tool contracts/protected-contracts.json
PASS: JSON valid.

python3 -m json.tool contracts/protected-surface-acceptance.json
PASS: JSON valid.
```

## H. Human Acceptance

Reviewer confirms:

- [ ] Reviewer confirms the slice is understandable from the UI/user perspective where applicable.
- [ ] Reviewer confirms the slice does not create hidden cashier or Vincentian complexity.
- [ ] Reviewer confirms the slice respects the dignity-centered voucher workflow.
- [ ] Reviewer confirms the slice can safely proceed to the next Release C slice.

## Result

- [x] PASS PENDING HUMAN ACCEPTANCE

## Notes

```text
C2 adds admin/backend Household Goods catalog, limit, snapshot-helper, and configuration-audit support only. It does not implement the public Household Goods builder step, request-group UI wiring, cashier fulfillment workspace, POS/inventory/SKU behavior, delivery logistics, or unrestricted notes.
```
