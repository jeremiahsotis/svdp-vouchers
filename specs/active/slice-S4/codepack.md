# S4 Codepack - Admin Voucher Correction Audit Visibility

## Intent

Implement a read-only admin audit tab for voucher corrections.

This slice exposes the S2/S3 correction audit trail to authorized administrators using the new `svdp_view_audit_log` capability.

## Expected Files To Change

Likely:

- includes/class-permissions.php
- includes/class-admin.php
- admin/views/admin-page.php
- admin/views/tab-voucher-correction-audit.php
- svdp-vouchers.php
- specs/active/slice-S4/implementation-brief.md
- specs/active/slice-S4/codepack.md
- specs/active/slice-S4/checkpoint-01.md
- specs/active/slice-S4/bootstrap.md

Likely new:

- includes/class-voucher-correction-audit.php
- admin/views/tab-voucher-correction-audit.php

Optional:

- admin/css/admin.css

Do not modify:

- public/js/cashier-station.js
- public/js/cashier-shell.js unless a stable cashier link already requires it
- public/templates/cashier/\* unless correcting a broken link from S3
- delivery or RouteShyft surfaces

## Capability Work

### 1. Register `svdp_view_audit_log`

File:

- includes/class-permissions.php

Add new capability:

```php
svdp_view_audit_log
```

Grant it to:

- administrator
- any existing system/admin role used for full SVdP Vouchers administration

Do not grant it to basic cashier users by default.

If the permission class has a capability registry array, add it there.

Add helper:

```php
public static function user_can_view_audit_log($user = null)
```

Expected behavior:

- returns true if user has `svdp_view_audit_log`
- administrator/manage_options fallback is acceptable only as compatibility fallback
- should not depend on manager override code

## Class Loading

### 2. Require the audit class

File:

- svdp-vouchers.php

Add:

```php
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-voucher-correction-audit.php';
```

Place it near the other required class files.

## Audit Query Service

### 3. Add read-only audit query class

Create:

- includes/class-voucher-correction-audit.php

Class:

```php
class SVDP_Voucher_Correction_Audit
```

Required methods:

```php
public static function get_rows($args = [])
public static function count_rows($args = [])
public static function get_allowed_fields()
```

Optional helper methods:

```php
private static function build_where_clause($args, &$params)
private static function sanitize_args($args)
private static function format_row($row)
```

### Query requirements

Read from:

- `{$wpdb->prefix}svdp_voucher_corrections vc`

Join:

- `{$wpdb->prefix}svdp_vouchers v ON v.id = vc.voucher_id`
- `{$wpdb->prefix}svdp_conferences c ON c.id = v.conference_id`
- `{$wpdb->users} u ON u.ID = vc.actor_user_id`

Return these fields:

- correction id
- voucher id
- field_name
- before_value
- after_value
- manager_id
- manager_name_snapshot
- actor_user_id
- actor display name or user login
- reason_id
- reason_text_snapshot
- human_summary
- created_at
- voucher first_name
- voucher last_name
- voucher dob
- voucher status
- voucher_type
- conference_name

### Filters

Support sanitized filters:

- `voucher_id`
- `neighbor`
- `field_name`
- `manager`
- `actor`
- `reason`
- `date_from`
- `date_to`

Filtering behavior:

- `voucher_id`: exact integer match
- `neighbor`: partial match against first name, last name, and full name
- `field_name`: exact match against allowed field list
- `manager`: partial match against manager_name_snapshot
- `actor`: partial match against user display name or user login
- `reason`: partial match against reason_text_snapshot
- `date_from`: inclusive date start
- `date_to`: inclusive date end

### Pagination

Support:

- `page`
- `per_page`

Default:

- `page = 1`
- `per_page = 25`

Maximum:

- `per_page = 100`

Order:

```sql
ORDER BY vc.created_at DESC, vc.id DESC
```

### Safety

Do not query:

- manager code
- code_hash
- password fields

Do not expose:

- manager codes
- manager hashes
- raw serialized auth state

Use `$wpdb->prepare()` for all dynamic values.

## Admin Tab Work

### 4. Add admin tab link

File:

- admin/views/admin-page.php

Add nav tab:

```php
<a href="?page=svdp-vouchers&tab=voucher-correction-audit" class="nav-tab <?php echo $active_tab === 'voucher-correction-audit' ? 'nav-tab-active' : ''; ?>">
    Voucher Correction Audit
</a>
```

Only show this tab if:

```php
SVDP_Permissions::user_can_view_audit_log()
```

Add switch case:

```php
case 'voucher-correction-audit':
    include 'tab-voucher-correction-audit.php';
    break;
```

If user lacks capability and directly accesses the tab, show a WordPress admin notice or `wp_die()` with permission denied.

## Admin View Work

### 5. Add audit tab view

Create:

- admin/views/tab-voucher-correction-audit.php

The view should:

1. Check `SVDP_Permissions::user_can_view_audit_log()`
2. Read GET filters
3. Call `SVDP_Voucher_Correction_Audit::get_rows($args)`
4. Call `SVDP_Voucher_Correction_Audit::count_rows($args)`
5. Render filters
6. Render table
7. Render pagination

### Filter form

Use method GET and preserve:

```html
<input type="hidden" name="page" value="svdp-vouchers" />
<input type="hidden" name="tab" value="voucher-correction-audit" />
```

Fields:

- Voucher ID
- Neighbor
- Field
- Manager
- Actor
- Reason
- Date From
- Date To

Buttons:

- Filter
- Clear Filters

Clear Filters should link to:

```text
?page=svdp-vouchers&tab=voucher-correction-audit
```

### Table columns

Columns:

1. Date
2. Voucher
3. Neighbor
4. Summary
5. Field
6. Manager
7. Actor
8. Reason
9. Conference

Primary line:

- `human_summary`

If `human_summary` is empty for legacy rows, display fallback:

```text
Voucher #X: field_name changed from "before_value" to "after_value".
```

Secondary metadata:

- Voucher #ID
- Neighbor name + DOB
- Manager name or "No override authority recorded"
- Actor display name or "Unknown actor"
- Reason text or "No reason recorded"
- Timestamp

### Voucher link behavior

Show:

```text
Voucher #341 - Jeremiah Otis
```

Only make it a link if a stable admin or cashier URL already exists in repo.

Do not invent a new route.

## Styling

Optional:

- Use existing WordPress classes:
  - `widefat`
  - `striped`
  - `tablenav`
  - `button`
  - `regular-text`

Avoid custom JS unless necessary.

## Acceptance Validation

Run:

```bash
git status --short
git log --oneline -n 12

find specs/active/slice-S4 -maxdepth 1 -type f -print | sort

grep -RIn "svdp_view_audit_log\|SVDP_Voucher_Correction_Audit\|voucher-correction-audit\|human_summary" \
  includes admin svdp-vouchers.php specs/active/slice-S4 | head -n 500

wp eval '
print_r(SVDP_Voucher_Correction_Audit::get_rows(["per_page" => 5]));
'

find includes public admin -name "*.php" -print0 | xargs -0 -n1 php -l
python3 scripts/check_required_doc_sections.py
find . -name ".DS_Store" -print
```

Manual validation:

1. Log in as administrator.
2. Open SVdP Vouchers admin page.
3. Confirm Voucher Correction Audit tab is visible.
4. Open tab.
5. Confirm rows display.
6. Confirm primary summary is readable.
7. Filter by voucher ID.
8. Filter by neighbor.
9. Filter by field.
10. Filter by manager.
11. Filter by reason.
12. Filter by date range.
13. Confirm pagination.
14. Confirm no manager code or code hash appears.
