# Codepack — Slice S1

## 1. DATABASE

### Modify managers table

File: includes/class-database.php

Add columns:

ALTER TABLE wp_svdp_managers
ADD COLUMN failed_attempts INT DEFAULT 0,
ADD COLUMN locked_until DATETIME NULL,
ADD COLUMN last_used_at DATETIME NULL;

---

### Create audit table

CREATE TABLE wp_svdp_override_audit (
id BIGINT AUTO_INCREMENT PRIMARY KEY,
voucher_id BIGINT NULL,
manager_id BIGINT NULL,
manager_name_snapshot VARCHAR(200),
actor_user_id BIGINT,
success TINYINT(1),
reason_id BIGINT NULL,
reason_text_snapshot VARCHAR(255),
context VARCHAR(100),
created_at DATETIME NOT NULL,
KEY manager_id (manager_id),
KEY created_at (created_at)
);

---

## 2. MANAGER CLASS

File: includes/class-manager.php

### Replace generate_code()

```php
private static function generate_code($manual = null) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    if ($manual !== null) {
        $code = strtoupper(trim($manual));

        if (!preg_match('/^[A-Z2-9]{4}$/', $code)) {
            return new WP_Error('invalid_code_format', 'Code must be 4 characters A-Z, 2-9');
        }

        return $code;
    }

    do {
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[wp_rand(0, strlen($chars) - 1)];
        }
    } while (self::code_exists($code));

    return $code;
}
```

### Add uniqueness check

```php
private static function code_exists($code) {
    global $wpdb;
    $table = $wpdb->prefix . 'svdp_managers';

    $managers = $wpdb->get_results("SELECT code_hash FROM $table");

    foreach ($managers as $manager) {
        if (wp_check_password($code, $manager->code_hash)) {
            return true;
        }
    }

    return false;
}
```

---

### Add validation with audit

```php
public static function validate_code_with_audit($code, $manager_name, $reason_id, $context = null) {
    global $wpdb;

    $result = self::validate_code($code);
    $actor_user_id = get_current_user_id();

    $audit_table = $wpdb->prefix . 'svdp_override_audit';

    $wpdb->insert($audit_table, [
        'manager_id' => $result['id'] ?? null,
        'manager_name_snapshot' => sanitize_text_field($manager_name),
        'actor_user_id' => $actor_user_id,
        'success' => $result['valid'] ? 1 : 0,
        'reason_id' => intval($reason_id),
        'context' => sanitize_text_field($context),
        'created_at' => current_time('mysql')
    ]);

    return $result;
}
```

---

### Add lockout handling (inside validate_code)

```php
if ($manager->locked_until && strtotime($manager->locked_until) > time()) {
    continue;
}
```

On failure increment:

```php
$wpdb->update($table, [
    'failed_attempts' => $manager->failed_attempts + 1
]);
```

If threshold exceeded:

```php
$wpdb->update($table, [
    'locked_until' => date('Y-m-d H:i:s', time() + 900)
]);
```

---

## 3. REST ENDPOINT

File: svdp-vouchers.php

Modify:

```php
register_rest_route('svdp/v1', '/managers/validate', [
```

Handler:

```php
public static function validate_code_endpoint($request) {
    return SVDP_Manager::validate_code_with_audit(
        $request->get_param('code'),
        $request->get_param('managerName'),
        $request->get_param('reasonId'),
        $request->get_param('context')
    );
}
```

---

## 4. FRONTEND

File: public/js/cashier-shell.js

Modify request payload:

```javascript
await requestJSON(config.restUrl + "svdp/v1/managers/validate", {
  method: "POST",
  body: JSON.stringify({
    code: enteredCode,
    managerName: enteredName,
    reasonId: selectedReason,
    context: "override_validation",
  }),
});
```

---

## 5. GOVERNANCE

Update:

contracts/protected-contracts.json

Add rule:

"Override validation must create audit entry for both success and failure"

---

## 14. Slice Size Justification

Slice S1 touches multiple protected surfaces because override authority spans schema, manager validation, REST payloads, cashier UI, and governance contracts. The file count is expected for this slice and remains bounded to the override authority foundation described in the implementation brief.
