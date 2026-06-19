# Checkpoint 01 - Slice C1 - Request Groups, Multi-Voucher Foundation, and Delivery Capabilities

## Purpose

Verify that Slice C1 satisfies its slice contract without introducing out-of-scope behavior.

## A. Scope Verification

- [x] PASS: Add request group schema.
- [x] PASS: Add child-voucher request_group_id link and uniqueness protection for one voucher of each type per group.
- [x] PASS: Add voucher-type capability settings, including delivery availability.
- [x] PASS: Add group-level delivery record schema.
- [x] PASS: Seed initial voucher type capability settings: Clothing delivery off, Furniture delivery on, Household Goods delivery on.
- [x] PASS: Implement atomic service for creating a request group and child vouchers without yet wiring the public Assisted Builder UI.
- [x] PASS: Maintain compatibility for standalone legacy vouchers with no request group.

## B. Out-of-Scope Verification

- [x] NOT IMPLEMENTED: No Household Goods catalog/admin UI.
- [x] NOT IMPLEMENTED: No Household Goods request UI.
- [x] NOT IMPLEMENTED: No cashier fulfillment workspace.
- [x] NOT IMPLEMENTED: No Assisted Builder visual migration.
- [x] NOT IMPLEMENTED: No new dispatch/routing/delivery attempts.

## C. Acceptance Criteria Verification

- [x] PASS: Request groups can be created with one, two, or three child vouchers through `SVDP_Voucher_Request_Group::create()` and the opt-in manual harness.
- [x] PASS: Database prevents duplicate voucher types within one request group through `UNIQUE KEY uniq_svdp_request_group_voucher_type (request_group_id, voucher_type)`.
- [x] PASS: Legacy vouchers without request_group_id still display and function because the column is nullable and existing voucher queries are unchanged.
- [x] PASS: Delivery eligibility is read from `wp_svdp_voucher_type_capabilities` via `SVDP_Voucher_Type_Settings`.
- [x] PASS: Delivery is stored once per request group and snapshots fee, address, eligible voucher types, and selected voucher types.
- [x] PASS: Changing delivery settings affects future requests only because issued groups store immutable delivery snapshots.
- [x] PASS: Creation failure in any child voucher prevents the whole group from being committed through one database transaction.

## D. Protected Surface Review

- [x] PASS: Protected surfaces touched are listed in the developer summary.
- [x] PASS: Each protected surface change maps to the C1 implementation brief.
- [x] PASS: No unrelated protected surfaces were changed.
- [x] PASS: Protected contracts were updated for new C1 service classes and schema behavior.
- [x] PASS: Historical behavior was preserved where required.

## E. Migration / Data Safety

- [x] PASS: Schema changes follow the repo migration pattern through `SVDP_Database::maybe_upgrade()`, `dbDelta()`, guarded `ALTER TABLE`, and schema version 10.
- [x] PASS: Data evolution log is updated for schema/config changes.
- [x] PASS: Historical vouchers remain valid.
- [x] PASS: Legacy `household` records are not reinterpreted as `household_goods`.
- [x] PASS: Snapshot rules are preserved where applicable.
- [x] PASS: No destructive migration occurred.

## F. Audit / Permission Safety

- [x] PASS: New grouped request rows snapshot requestor, household, Conference, source, and submitted timestamp.
- [x] PASS: No admin or cashier authority surface was added in C1.
- [x] PASS: Vincentians did not receive cashier/admin authority.
- [x] PASS: Internal Finalization Note was not touched.
- [x] PASS: No new unrestricted Notes field was introduced.

## G. Validation Commands

```text
git status --short
PASS: Shows only intended C1 modified/untracked files.

git log --oneline -n 12
PASS: Latest commit remains 8c7fd67 Merge pull request #16 from jeremiahsotis/slice/c0-contract-binding-and-slice-rebase.

find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l
PASS: No syntax errors detected.

python3 scripts/check_required_doc_sections.py
PASS: Required doc sections valid.

python3 scripts/check_no_placeholders.py
PASS: No placeholders found.

find . -name ".DS_Store" -print
PASS: No output.

python3 -m py_compile scripts/*.py
PASS: No output. Initial sandbox run failed because macOS Python tried to write bytecode cache outside the workspace; rerun with approved escalation passed.

php -l scripts/c1-request-group-manual-harness.php
PASS: No syntax errors detected.
```

## H. Human Acceptance

- [ ] Reviewer confirms the slice is understandable from the UI/user perspective where applicable.
- [ ] Reviewer confirms the slice does not create hidden cashier or Vincentian complexity.
- [ ] Reviewer confirms the slice respects the dignity-centered voucher workflow.
- [ ] Reviewer confirms the slice can safely proceed to the next Release C slice.

## Result

- [x] PASS PENDING HUMAN ACCEPTANCE

## Notes

```text
C1 is backend-only. The public Assisted Builder, Household Goods catalog/request UI, cashier fulfillment workspace, and delivery logistics are not implemented.
```
