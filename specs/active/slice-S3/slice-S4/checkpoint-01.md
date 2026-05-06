# S4 Checkpoint 01 - Admin Voucher Correction Audit Visibility

## Repo State

- [ ] `git status --short` reviewed
- [ ] S4 artifacts exist
- [ ] Protected surfaces changed intentionally
- [ ] No unrelated cashier, delivery, or catalog mutation scope added

## Governance Artifacts

- [ ] `specs/active/slice-S4/implementation-brief.md` exists
- [ ] `specs/active/slice-S4/codepack.md` exists
- [ ] `specs/active/slice-S4/checkpoint-01.md` exists
- [ ] `specs/active/slice-S4/bootstrap.md` exists
- [ ] Bootstrap references all four S4 files as authoritative execution sources

## Capability

- [ ] `svdp_view_audit_log` is registered
- [ ] Administrator receives `svdp_view_audit_log`
- [ ] Basic cashier users do not receive audit visibility by default
- [ ] Helper exists for audit visibility checks
- [ ] Manager override code is not treated as admin audit permission

## Audit Query

- [ ] Audit query service exists
- [ ] Reads from `wp_svdp_voucher_corrections`
- [ ] Joins voucher context
- [ ] Joins conference context
- [ ] Joins actor user context where available
- [ ] Does not query or expose manager code hashes
- [ ] Orders newest first
- [ ] Paginates results

## Filters

- [ ] Voucher ID filter works
- [ ] Neighbor filter works
- [ ] Field filter works
- [ ] Manager filter works
- [ ] Actor filter works
- [ ] Reason filter works
- [ ] Date from filter works
- [ ] Date to filter works
- [ ] Clear filters returns to unfiltered audit tab

## Admin UI

- [ ] Voucher Correction Audit tab appears inside existing SVdP Vouchers admin page
- [ ] Tab only appears for users with `svdp_view_audit_log`
- [ ] Direct access without capability is blocked
- [ ] Primary line displays `human_summary`
- [ ] Legacy rows without `human_summary` use readable fallback
- [ ] Secondary metadata displays voucher, field, manager, actor, reason, timestamp
- [ ] Voucher ID + neighbor name is displayed
- [ ] Voucher is linked only if stable URL already exists

## Out of Scope Guardrails

- [ ] No CSV export added
- [ ] No voucher editing added
- [ ] No revert correction behavior added
- [ ] No catalog audit added
- [ ] No multi-user conflict handling added
- [ ] No delivery dispatch, delivery attempts, driver, RouteShyft, or routing behavior added

## Validation Commands

```bash
git status --short
git log --oneline -n 12

find specs/active/slice-S4 -maxdepth 1 -type f -print | sort

grep -RIn "svdp_view_audit_log\|SVDP_Voucher_Correction_Audit\|voucher-correction-audit\|human_summary" \
  includes admin svdp-vouchers.php specs/active/slice-S4 | head -n 500

wp eval '
$rows = SVDP_Voucher_Correction_Audit::get_rows(["per_page" => 5]);
echo "Rows: " . count($rows) . PHP_EOL;
print_r($rows);
'

find includes public admin -name "*.php" -print0 | xargs -0 -n1 php -l

python3 scripts/check_required_doc_sections.py
find . -name ".DS_Store" -print
````

## Manual Validation

* [ ] Admin can open Voucher Correction Audit tab
* [ ] Non-authorized user cannot see audit tab
* [ ] Rows display in newest-first order
* [ ] Human summaries are readable
* [ ] Voucher ID filter works
* [ ] Neighbor filter works
* [ ] Field filter works
* [ ] Manager filter works
* [ ] Actor filter works
* [ ] Reason filter works
* [ ] Date filters work
* [ ] Pagination works
* [ ] No manager code or code hash appears anywhere

## Done Decision

S4 is complete only when the full voucher correction audit is visible in admin, filtered, paginated, permission-gated by `svdp_view_audit_log`, and read-only.
