# S2 Codepack — Voucher Corrections

## Changed Files

- includes/class-database.php
- includes/class-voucher.php
- svdp-vouchers.php

## 1. Database

Add to class-database.php:

```sql
public static function create_voucher_corrections_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'svdp_voucher_corrections';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        voucher_id bigint(20) NOT NULL,
        field_name varchar(100) NOT NULL,
        before_value text NULL,
        after_value text NULL,
        actor_user_id bigint(20) NULL,
        manager_id bigint(20) NULL,
        manager_name_snapshot varchar(200) NULL,
        reason_id bigint(20) NULL,
        reason_text_snapshot varchar(255) NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY voucher_id (voucher_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

Ensure it is called in install routine:
self::create_voucher_corrections_table();

---

## 2. Service Layer

Add to class-voucher.php:

Function:
apply_corrections()

```php
public static function apply_corrections($voucher_id, $changes, $authority = []) {
    global $wpdb;

    $voucher_id = intval($voucher_id);
    $table = $wpdb->prefix . 'svdp_vouchers';
    $audit_table = $wpdb->prefix . 'svdp_voucher_corrections';

    $voucher = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $voucher_id)
    );

    if (!$voucher) {
        return new WP_Error('not_found', 'Voucher not found');
    }

    $allowed_fields = [
        'adults',
        'children',
        'dob',
        'status',
        'voucher_created_date',
        'delivery_address_line_1',
        'delivery_address_line_2',
        'delivery_city',
        'delivery_state',
        'delivery_zip'
    ];

    $update_data = [];
    $actor_user_id = get_current_user_id();

    foreach ($changes as $field => $new_value) {

        if (!in_array($field, $allowed_fields)) {
            continue;
        }

        $old_value = $voucher->$field;

        if ((string)$old_value === (string)$new_value) {
            continue;
        }

        // Write audit row BEFORE mutation
        $wpdb->insert($audit_table, [
            'voucher_id' => $voucher_id,
            'field_name' => $field,
            'before_value' => maybe_serialize($old_value),
            'after_value' => maybe_serialize($new_value),
            'actor_user_id' => $actor_user_id,
            'manager_id' => $authority['manager_id'] ?? null,
            'manager_name_snapshot' => $authority['manager_name'] ?? null,
            'reason_id' => $authority['reason_id'] ?? null,
            'reason_text_snapshot' => $authority['reason_text'] ?? null,
            'created_at' => current_time('mysql')
        ]);

        $update_data[$field] = $new_value;
    }

    if (empty($update_data)) {
        return ['success' => true, 'message' => 'No changes'];
    }

    $wpdb->update(
        $table,
        $update_data,
        ['id' => $voucher_id]
    );

    return ['success' => true];
}
```

---

## 3. Authority Enforcement

Insert inside loop BEFORE audit write:

```php
$requires_authority = [
    'dob',
    'voucher_created_date',
    'status'
];

if (in_array($field, $requires_authority)) {
    if (empty($authority['manager_id'])) {
        return new WP_Error('authority_required', 'Manager authorization required');
    }
}
```

---

## 4. REST Endpoint

Add in svdp-vouchers.php:

Route:
POST /svdp/v1/vouchers/{id}/correct

Handler:
apply_corrections_endpoint()

```php
register_rest_route('svdp/v1', '/vouchers/(?P<id>\d+)/correct', [
    'methods' => 'POST',
    'callback' => ['SVDP_Voucher', 'apply_corrections_endpoint'],
    'permission_callback' => function() {
        return current_user_can('manage_options'); // admin only for now
    }
]);
```

Endpoint handler:

```php
public static function apply_corrections_endpoint($request) {
    $voucher_id = intval($request['id']);
    $params = $request->get_json_params();

    $changes = $params['changes'] ?? [];
    $authority = $params['authority'] ?? [];

    return self::apply_corrections($voucher_id, $changes, $authority);
}
```

---

## 5. Validation

Run after deploy:

```bash
wp db query "SHOW TABLES LIKE 'wp_svdp_voucher_corrections';"

wp db query "
SELECT field_name, before_value, after_value
FROM wp_svdp_voucher_corrections
ORDER BY id DESC
LIMIT 10;
"
```

---

## 6. Test Cases

1. Change adults → success
2. Change dob without authority → fail
3. Change dob with authority → success
4. No-op → no audit row
