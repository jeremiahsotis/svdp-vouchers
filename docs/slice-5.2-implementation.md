# Slice 5.2 — Address Verification (Hybrid Model B)

## Scope (LOCKED)

- Preserve user-entered address as canonical
- Store verification metadata (lat/lng, normalized, verified)
- Surface verification state in UI
- DO NOT block submission for unverified addresses

---

## Files in Scope (AUTHORITATIVE)

- includes/class-voucher.php
- public/templates/cashier/partials/voucher-detail-furniture.php
- public/templates/documents/furniture-receipt.php
- public/js/voucher-request.js

NO OTHER FILES MAY BE MODIFIED

---

## Step 1 — Backend Display Logic

### File
includes/class-voucher.php

### Locate
Return payload where `delivery_address_display` is defined

### Replace

```php
'delivery_address_display' => $is_furniture ? self::format_delivery_address($delivery_address_parts) : '',
```

### With

```php
$raw_display = self::format_delivery_address($delivery_address_parts);

$normalized = $voucher->delivery_normalized_address ?? '';
$verified = !empty($voucher->delivery_verified);

$delivery_display = $raw_display;

if ($verified && !empty($normalized)) {
    if (strcasecmp(trim($normalized), trim($raw_display)) !== 0) {
        $delivery_display = $raw_display . ' (verified)';
    }
}

'delivery_address_display' => $is_furniture ? $delivery_display : '',
'delivery_address_verified' => $is_furniture ? (bool) $verified : false,
'delivery_address_normalized' => $is_furniture ? ($normalized ?: null) : null,
```

---

## Step 2 — Cashier UI Warning

### File
public/templates/cashier/partials/voucher-detail-furniture.php

### Locate
Delivery address display block

### Modify

```php
<p><?php echo esc_html($voucher['delivery_address_display']); ?></p>
```

### Replace with

```php
<p>
    <?php echo esc_html($voucher['delivery_address_display']); ?>
    <?php if (empty($voucher['delivery_address_verified'])): ?>
        <br><small style="color:#b45309;">Address not verified</small>
    <?php endif; ?>
</p>
```

---

## Step 3 — Receipt Warning

### File
public/templates/documents/furniture-receipt.php

### Locate
Delivery address output

### Add BELOW existing line

```php
<?php if (empty($voucher['delivery_address_verified'])): ?>
    <p style="margin:0;color:#b45309;">Address not verified</p>
<?php endif; ?>
```

---

## Step 4 — Frontend Verification Reset (VERIFY ONLY)

### File
public/js/voucher-request.js

### Confirm

- `clearAddressVerificationFields()` is triggered by:
  - deliveryLine1
  - deliveryCity
  - deliveryState
  - deliveryZip

- AND NOT triggered by:
  - deliveryLine2

NO CODE CHANGE unless broken

---

## Step 5 — Suggestion Selection (VERIFY ONLY)

### File
public/js/voucher-request.js

### Confirm

`selectAddressSuggestion()` sets:

- deliveryLat
- deliveryLng
- deliveryVerified = 1
- deliveryNormalized

NO CODE CHANGE unless broken

---

## Step 6 — Optional UX Signal (SAFE ADD)

### File
public/js/voucher-request.js

### After suggestion selection, add:

```js
form.find('[name="deliveryLine1"]').after(
    '<div class="svdp-help-text svdp-address-verified">Address verified</div>'
);
```

### In clearAddressVerificationFields(), also add:

```js
form.find('.svdp-address-verified').remove();
```

---

## Constraints

- DO NOT overwrite user-entered address
- DO NOT block submission
- DO NOT introduce new validation rules
- DO NOT modify REST routes
- DO NOT modify schema

---

## Done

- Verification state stored
- Verification state visible
- Submission unaffected