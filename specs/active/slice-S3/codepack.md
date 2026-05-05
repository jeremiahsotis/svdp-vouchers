# S3 Codepack — Cashier Correction Modal + Recent Audit Display

## Intent

Implement cashier-facing correction UI using the S2 backend correction endpoint.

## Files Expected To Change

Likely:

- includes/class-voucher.php
- public/templates/cashier/partials/voucher-detail.php
- public/templates/cashier/partials/voucher-detail-furniture.php
- public/js/cashier-shell.js
- svdp-vouchers.php
- specs/active/slice-S3/\*

Do not modify `public/js/cashier-station.js` unless evidence proves it is active.

## Backend Work

### 1. Add recent correction summaries to cashier voucher payload

In `includes/class-voucher.php`, add a helper:

- Fetch newest 2 rows from `wp_svdp_voucher_corrections`
- Filter by voucher_id
- Order by created_at DESC, id DESC
- Return:
  - id
  - field_name
  - human_summary
  - manager_name_snapshot
  - reason_text_snapshot
  - created_at

Add the result to formatted cashier voucher payload as:

```php
'recent_corrections' => self::get_recent_corrections((int) $voucher->id, 2),
```

Do not expose raw code hashes or manager codes.

## Template Work

### 2. Add correction entry point to clothing voucher detail

File:

- `public/templates/cashier/partials/voucher-detail.php`

Add a "Correct Voucher" panel/action near the top-level voucher information.

The form must include:

- current adults
- current children
- current DOB
- current status
- current voucher_created_date
- manager name
- manager code
- reason dropdown
- inline error area
- submit button

Use:

```text
data-cashier-action="voucher-correct"
data-voucher-id="<voucher id>"
```

Use field names matching the S2 endpoint payload:

- adults
- children
- dob
- status
- voucher_created_date

### 3. Add correction entry point to furniture voucher detail

File:

- `public/templates/cashier/partials/voucher-detail-furniture.php`

Add the same correction action plus delivery fields if delivery exists:

- delivery_address_line_1
- delivery_address_line_2
- delivery_city
- delivery_state
- delivery_zip

Do not add dispatch, driver, route, delivery attempts, or RouteShyft language.

### 4. Add recent corrections display

Both detail templates should show newest 2 correction summaries if present.

Display section:

Heading: `Recent Corrections`

Each item should show:

- human_summary
- created_at

Do not require users to interpret raw before/after fields.

## JavaScript Work

### 5. Handle correction submit

File:

- `public/js/cashier-shell.js`

Add handler in `handleSubmit()`:

```js
if (action === "voucher-correct") {
  submitVoucherCorrection(form);
  return;
}
```

### 6. Implement submitVoucherCorrection(form)

Flow:

1. Read voucher ID.
2. Collect correction fields.
3. Drop blank fields only if they are optional and not intended to change.
4. Validate manager code format:
   - 4 characters
   - uppercase
   - `A-Z` and `2-9`

5. Require manager name.
6. Require reason.
7. Call `/svdp/v1/managers/validate`.
8. If invalid, show inline error.
9. If valid, call `/svdp/v1/vouchers/{id}/correct`.
10. On success:
    - show success flash
    - refresh voucher detail

11. On error:
    - show inline error

Use existing helpers where possible:

- requestJSON
- showInlineError
- showFlash
- refreshShell
- extractErrorMessage
- setButtonState

### 7. Payload shape

Send camelCase authority keys:

```js
{
  changes: {
    adults,
    children,
    dob,
    status,
    voucher_created_date,
    delivery_address_line_1,
    delivery_address_line_2,
    delivery_city,
    delivery_state,
    delivery_zip
  },
  authority: {
    managerId,
    managerName,
    reasonId,
    reasonText
  }
}
```

If current S2 endpoint expects flattened payload, adapt only inside `buildVoucherCorrectionPayload()`, not scattered through submit logic.

## Validation

Run:

```bash
find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l
python3 scripts/check_no_placeholders.py
python3 scripts/check_required_doc_sections.py
find . -name ".DS_Store" -print
```

Manual UI validation:

1. Open clothing voucher detail.
2. Click Correct Voucher.
3. Change children.
4. Enter manager name, valid code, reason.
5. Submit.
6. Confirm detail refreshes.
7. Confirm recent correction appears.
8. Try protected DOB correction with invalid code.
9. Confirm blocked.
10. Try protected DOB correction with valid code.
11. Confirm success and audit summary appears.
12. Repeat on furniture voucher with delivery fields if available.
