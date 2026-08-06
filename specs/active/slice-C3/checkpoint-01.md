# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Checkpoint 01 - Slice C3 - Shared Fulfillment Workspace, Voucher Finalization, and Cashier Card Status

## Purpose

Verify that Slice C3 - Shared Fulfillment Workspace, Voucher Finalization, and Cashier Card Status satisfies its slice contract without introducing out-of-scope behavior.

## Required Review Inputs

- This slice's `implementation-brief.md`
- This slice's `codepack.md`
- `release-c-product-contract.md`
- Git diff for the slice
- Validation command output
- Any schema migration output or logs

## A. Scope Verification

Mark PASS / FAIL.

- [x] PASS: Add shared requested-line and fulfillment-entry model for new Release C Furniture and Household Goods vouchers.
- [x] PASS: Implement one-screen fulfillment workspace in cashier detail.
- [x] PASS: Allow multiple inline price rows per requested line.
- [x] PASS: Capture Price Each, fulfilled quantity, calculated line total, and derived not-fulfilled quantity.
- [x] PASS: Enforce fulfilled quantity never exceeds requested quantity before save or finalization.
- [x] PASS: Add Save Progress and Finalize Voucher behavior.
- [x] PASS: Add optional voucher-level Internal Finalization Note before finalization.
- [x] PASS: Remove Furniture item completion Notes from new workflow.
- [x] PASS: Improve cashier cards while preserving card architecture: READY TO REDEEM, REDEEMED, EXPIRED.
- [x] PASS: Preserve legacy Furniture voucher display and receipt behavior.

## B. Out-of-Scope Verification

Confirm none of the following were implemented:

- [x] NOT IMPLEMENTED: No public Assisted Builder migration.
- [x] NOT IMPLEMENTED: No Household Goods public request creation unless test fixtures are needed.
- [x] NOT IMPLEMENTED: No POS integration.
- [x] NOT IMPLEMENTED: No barcode/SKU/live inventory.
- [x] NOT IMPLEMENTED: No unrestricted item-level notes.
- [x] NOT IMPLEMENTED: No table replacement for cashier cards.

## C. Acceptance Criteria Verification

- [x] PASS: Cashier cards show READY TO REDEEM, REDEEMED, and EXPIRED as large, high-contrast labels with non-color cues.
- [x] PASS: Redeemed vouchers remain REDEEMED after expiration date passes.
- [x] PASS: Expired unredeemed vouchers cannot be redeemed through ordinary workflow.
- [x] PASS: Furniture and Household Goods fulfillment can be completed on one screen when requested lines exist.
- [x] PASS: Cashier can add multiple price rows inline without modal/page transitions.
- [x] PASS: Line totals calculate automatically.
- [x] PASS: Requested units left unfulfilled require no reason and are recorded as not fulfilled.
- [x] PASS: Save Progress does not redeem voucher or generate final documents.
- [x] PASS: Finalize is blocked only when fulfilled quantities exceed requested quantities or positive fulfilled quantities are missing valid prices.
- [x] PASS: Internal Finalization Note is voucher-level only and hidden from receipt/invoice.
- [x] PASS: Legacy Furniture vouchers retain historical behavior.

## D. Protected Surface Review

- [x] PASS: Protected surfaces touched are listed in the developer summary and registered in protected contracts.
- [x] PASS: Each protected surface change maps to this slice's implementation brief.
- [x] PASS: No unrelated protected surfaces were changed.
- [x] PASS: Protected contracts were updated where the release behavior changed.
- [x] PASS: Historical behavior was preserved where required.

## E. Migration / Data Safety

- [x] PASS: Schema changes follow the repo migration pattern through `SVDP_Database::maybe_upgrade()`, `dbDelta()`, guarded `ALTER TABLE`, schema version 12, and idempotent seed logic.
- [x] PASS: Data evolution log is updated for schema/config changes.
- [x] PASS: Historical vouchers remain valid.
- [x] PASS: Legacy `household` records are not reinterpreted as `household_goods`.
- [x] PASS: Snapshot rules are preserved where applicable.
- [x] PASS: No destructive migration occurred.

## F. Audit / Permission Safety

- [x] PASS: New protected mutations are auditable through `wp_svdp_voucher_fulfillment_audit`.
- [x] PASS: Capability checks are used where cashier authority is required.
- [x] PASS: Vincentians did not receive cashier/admin authority.
- [x] PASS: Internal Finalization Note remains voucher-level and internal-only.
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

Recorded final outputs:

```text
git status --short
M contracts/protected-contracts.json
M contracts/protected-surface-acceptance.json
M contracts/protected-surfaces.json
M docs/architecture/concurrency-model.md
M docs/data-governance/backward-compatibility.md
M docs/data-governance/data-evolution-log.md
M docs/security/access-audit-model.md
M docs/state/change-impact-log.md
M includes/class-cashier-shell.php
M includes/class-database.php
M includes/class-furniture-voucher.php
M includes/class-invoice.php
M includes/class-voucher.php
M planning/release-c-slice-map.md
M public/css/voucher-forms.css
M public/js/cashier-shell.js
M public/js/cashier-station.js
M public/templates/cashier/partials/voucher-card.php
M public/templates/cashier/partials/voucher-detail-furniture.php
M public/templates/cashier/partials/voucher-detail.php
M public/templates/documents/furniture-invoice.php
M public/templates/documents/furniture-receipt.php
M svdp-vouchers.php
?? docs/adr/ADR-0004-release-c-shared-fulfillment-and-cashier-status.md
?? includes/class-household-goods-fulfillment.php
?? public/templates/cashier/partials/voucher-detail-shared-fulfillment.php
?? specs/active/slice-C3/

git log --oneline -n 12
8c51617 Merge pull request #18 from jeremiahsotis/slice/c2-household-goods-catalog-and-limits
5b45a3e Implement C2 Household Goods catalog and limits
195b478 Merge pull request #17 from jeremiahsotis/slice/c1-request-groups-multi-voucher-foundation-and-delivery-capabilities
4997ca0 Implement C1 request groups and delivery capabilities
8c7fd67 Merge pull request #16 from jeremiahsotis/slice/c0-contract-binding-and-slice-rebase
be01c40 Allow requestor domain term
c16aef4 Format C0 contract docs
0c59adf Bind Release C product contract and rebaseline S5
0f38306 Merge pull request #15 from jeremiahsotis/slice/S4
749555c Fix S4 hosted CI guardrails
7507441 Implement S4 admin voucher correction audit
5e5dade Merge pull request #14 from jeremiahsotis/slice/S3

find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l
PASS: no syntax errors detected in all checked PHP files.

python3 scripts/check_required_doc_sections.py
PASS: Required doc sections valid

python3 scripts/check_no_placeholders.py
PASS: No placeholders found

python3 -m py_compile scripts/*.py
PASS: no output

node --check public/js/cashier-shell.js
PASS: no output

node --check public/js/cashier-station.js
PASS: no output

find . -name '.DS_Store' -print
FOLLOW-UP: baseline files still present:
./.DS_Store
./specs/.DS_Store
./specs/active/.DS_Store
./docs/.DS_Store
```

Variance: `docs/governance/canonical-commands.json` specifies `git log --oneline -n 10` and `python3 -m py_compile scripts/*.py`; the C3 codepack requested `git log --oneline -n 12` and omitted Python compile. Both the requested `-n 12` log and canonical Python compile were run. The canonical forbidden-delivery search is noisy because vendored/minified JS and historical docs contain generic `dispatch` text; a changed-diff search found no new out-of-scope delivery logistics, POS, SKU, barcode, or inventory behavior.

## H. Human Acceptance

Reviewer confirms:

- [ ] The slice is understandable from the UI/user perspective where applicable.
- [ ] The slice does not create hidden cashier or Vincentian complexity.
- [ ] The slice respects the dignity-centered voucher workflow.
- [ ] The slice can safely proceed to the next Release C slice.

## Result

- [ ] PASS
- [x] PASS WITH FOLLOW-UP
- [ ] FAIL

## Notes

```text
Developer result: PASS WITH FOLLOW-UP.

Follow-up: The `.DS_Store` validation command reports pre-existing untracked Finder files. They were present at baseline and were not removed as part of C3.

Implementation notes:
- C3 adds additive schema version 12 only; no historical voucher records are rewritten.
- C3 does not implement C4 three-voucher Assisted Builder UI.
- Shared fulfillment requires requested-line rows. Line-backed Furniture and Household Goods vouchers use the new one-screen workspace; legacy Furniture vouchers without requested lines continue using the existing path.
- Request-group delivery snapshots are displayed for grouped vouchers, and invoice totals guard against billing the group-level delivery fee more than once.
- Historical unavailable reasons remain readable operational data; ordinary new fulfillment no longer requires reason selection.
- Internal Finalization Note is stored only at voucher level and excluded from external receipt/invoice templates.
```
