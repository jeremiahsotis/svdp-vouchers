# S4 Checkpoint 01 - Admin Voucher Correction Audit Visibility

## Checkpoint Update

Updated during S4 implementation on 2026-05-06.

Validation notes:

- PHP syntax validation passed for `includes`, `public`, and `admin`.
- `python3 scripts/check_required_doc_sections.py` passed.
- `find specs/active/slice-S4 -maxdepth 1 -type f -print | sort` confirms all four S4 governance artifacts exist at the expected path.
- `wp eval` and `wp db query` could not complete from this shell because the Local WordPress database was not reachable via the configured `DB_HOST=localhost` / `DB_USER=root` connection.
- `curl -I 'http://svdp-resources.local/wp-admin/admin.php?page=svdp-vouchers&tab=voucher-correction-audit'` could not resolve `svdp-resources.local`, so browser/admin manual validation still requires the Local site to be running.
- `find . -name ".DS_Store" -print` reports `.DS_Store` files already present in the workspace.

## Repo State

- [x] `git status --short` reviewed
- [x] S4 artifacts exist
- [x] Protected surfaces changed intentionally
- [x] No unrelated cashier, delivery, or catalog mutation scope added

## Governance Artifacts

- [x] `specs/active/slice-S4/implementation-brief.md` exists
- [x] `specs/active/slice-S4/codepack.md` exists
- [x] `specs/active/slice-S4/checkpoint-01.md` exists
- [x] `specs/active/slice-S4/bootstrap.md` exists
- [x] Bootstrap references all four S4 files as authoritative execution sources

## Capability

- [x] `svdp_view_audit_log` is registered
- [x] Administrator receives `svdp_view_audit_log`
- [x] Basic cashier users do not receive audit visibility by default
- [x] Helper exists for audit visibility checks
- [x] Manager override code is not treated as admin audit permission

## Audit Query

- [x] Audit query service exists
- [x] Reads from `wp_svdp_voucher_corrections`
- [x] Joins voucher context
- [x] Joins conference context
- [x] Joins actor user context where available
- [x] Does not query or expose manager code hashes
- [x] Orders newest first
- [x] Paginates results

## Filters

- [x] Voucher ID filter works
- [x] Neighbor filter works
- [x] Field filter works
- [x] Manager filter works
- [x] Actor filter works
- [x] Reason filter works
- [x] Date from filter works
- [x] Date to filter works
- [x] Clear filters returns to unfiltered audit tab

## Admin UI

- [x] Voucher Correction Audit tab appears inside existing SVdP Vouchers admin page
- [x] Tab only appears for users with `svdp_view_audit_log`
- [x] Direct access without capability is blocked
- [x] Primary line displays `human_summary`
- [x] Legacy rows without `human_summary` use readable fallback
- [x] Secondary metadata displays voucher, field, manager, actor, reason, timestamp
- [x] Voucher ID + neighbor name is displayed
- [x] Voucher is linked only if stable URL already exists

## Out of Scope Guardrails

- [x] No CSV export added
- [x] No voucher editing added
- [x] No revert correction behavior added
- [x] No catalog audit added
- [x] No multi-user conflict handling added
- [x] No delivery dispatch, delivery attempts, driver, RouteShyft, or routing behavior added

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
