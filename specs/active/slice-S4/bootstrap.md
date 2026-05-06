# S4 Bootstrap - Admin Voucher Correction Audit Visibility

## Active Slice

S4: Admin Voucher Correction Audit Visibility

## Authoritative Execution Sources

Read these files before coding, in this order:

1. `specs/active/slice-S4/implementation-brief.md`
2. `specs/active/slice-S4/codepack.md`
3. `specs/active/slice-S4/checkpoint-01.md`
4. `specs/active/slice-S4/bootstrap.md`
5. `specs/active/slice-S1/implementation-brief.md`
6. `specs/active/slice-S2/implementation-brief.md`
7. `specs/active/slice-S2/codepack.md`
8. `specs/active/slice-S3/implementation-brief.md`
9. `specs/active/slice-S3/codepack.md`
10. `contracts/protected-surfaces.json`
11. `contracts/protected-contracts.json`
12. `contracts/protected-surface-acceptance.json`
13. `docs/governance/canonical-commands.json`

## Locked Decisions

1. Add a new tab inside the existing SVdP Vouchers admin page.
2. Use new capability `svdp_view_audit_log`.
3. Defer CSV export.
4. Show voucher ID + neighbor name; link only if a stable admin/cashier URL already exists.
5. Primary line is `human_summary`. Secondary metadata is voucher, field, manager, actor, reason, timestamp.

## Execution Rules

- Build read-only admin audit visibility only.
- Do not add voucher correction logic.
- Do not add revert behavior.
- Do not add CSV export.
- Do not add catalog audit.
- Do not add multi-user conflict management.
- Do not expose manager codes.
- Do not expose manager code hashes.
- Do not introduce delivery dispatch, delivery attempts, driver, RouteShyft, or routing behavior.
- Prefer server-rendered WordPress admin UI with GET filters.
- Use existing admin page/tab structure.
- Use WordPress escaping and sanitization throughout.

## Required Implementation Order

1. Add or update `svdp_view_audit_log` capability registration.
2. Add helper `SVDP_Permissions::user_can_view_audit_log()`.
3. Add `includes/class-voucher-correction-audit.php`.
4. Require the new class from `svdp-vouchers.php`.
5. Implement paginated audit query method.
6. Implement filter sanitization and prepared SQL.
7. Add Voucher Correction Audit nav tab to `admin/views/admin-page.php`.
8. Add `admin/views/tab-voucher-correction-audit.php`.
9. Add optional minimal CSS only if needed.
10. Run checkpoint validation.

## Expected Changed Files

Likely:

- `includes/class-permissions.php`
- `includes/class-voucher-correction-audit.php`
- `includes/class-admin.php`
- `admin/views/admin-page.php`
- `admin/views/tab-voucher-correction-audit.php`
- `svdp-vouchers.php`
- `specs/active/slice-S4/implementation-brief.md`
- `specs/active/slice-S4/codepack.md`
- `specs/active/slice-S4/checkpoint-01.md`
- `specs/active/slice-S4/bootstrap.md`

Optional:

- `admin/css/admin.css`

## Completion Output Required

When complete, provide:

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

wp db query "
SELECT id, voucher_id, field_name, before_value, after_value, manager_id, manager_name_snapshot, actor_user_id, reason_id, reason_text_snapshot, human_summary, created_at
FROM wp_svdp_voucher_corrections
ORDER BY id DESC
LIMIT 10;
"

find includes public admin -name "*.php" -print0 | xargs -0 -n1 php -l

python3 scripts/check_required_doc_sections.py
find . -name ".DS_Store" -print
```

## Manual Validation Required

- Confirm administrator can view Voucher Correction Audit tab.
- Confirm user without `svdp_view_audit_log` cannot view audit content.
- Confirm audit rows show primary human summary.
- Confirm filters work.
- Confirm pagination works.
- Confirm no manager code or code hash is visible.
