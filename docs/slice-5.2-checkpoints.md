# Slice 5.2 — Address Verification (Hybrid Model B)

## Decision (LOCKED)

- Allow unverified addresses (warn only)
- Preserve user-entered address as canonical display
- Store normalized + lat/lng as verification metadata
- Do NOT overwrite user-entered address
- Verification must be visible to staff

---

## Checkpoint 1 — Display Logic (Backend)

### Goal
Expose verification state + normalized address without replacing user-entered address

### File
includes/class-voucher.php

### Required Changes

1. Modify delivery_address_display logic:
   - Base = formatted user-entered address
   - If verified AND normalized differs:
     - Append "(verified)" to display
   - NEVER replace raw address

2. Add fields to response payload:
   - delivery_address_verified (bool)
   - delivery_address_normalized (string|null)

### Pass Condition
- API response includes:
  - delivery_address_display
  - delivery_address_verified
  - delivery_address_normalized

---

## Checkpoint 2 — Cashier UI Warning

### Goal
Make verification state visible to staff

### File
public/templates/cashier/partials/voucher-detail-furniture.php

### Required Changes

- If NOT verified:
  - Show warning: "Address not verified"

### Pass Condition
- Verified address → no warning
- Unverified address → visible warning

---

## Checkpoint 3 — Receipt Warning

### Goal
Prevent false certainty in printed artifacts

### File
public/templates/documents/furniture-receipt.php

### Required Changes

- If NOT verified:
  - Add line: "Address not verified"

### Pass Condition
- Appears on receipt only when unverified

---

## Checkpoint 4 — Frontend State Integrity

### Goal
Prevent stale verification data

### File
public/js/voucher-request.js

### Required Behavior (verify, not rebuild)

- Editing ANY of:
  - line1
  - city
  - state
  - zip
→ clears:
  - lat/lng
  - verified
  - normalized

- Editing line2 DOES NOT clear verification

### Pass Condition
- Verified state drops on meaningful edits
- Persists on apt/unit changes

---

## Checkpoint 5 — Suggestion Selection

### Goal
Ensure verification is explicitly set

### File
public/js/voucher-request.js

### Required Behavior

On suggestion select:
- Set lat/lng
- Set verified = 1
- Set normalized string
- Hide suggestions

### Pass Condition
- Verified = 1 only when suggestion selected

---

## Checkpoint 6 — UX Signal (Optional but Recommended)

### Goal
Make verification visible during entry

### File
public/js/voucher-request.js

### Add
- Inline “Address verified” message after selection
- Remove on edit

### Pass Condition
- User sees confirmation after selecting suggestion

---

## Checkpoint 7 — No Blocking Behavior

### Goal
Maintain low-friction submission

### Validation

- Unverified address DOES NOT:
  - block submit
  - trigger error
  - prevent approval modal

### Pass Condition
- Form submits with verified = 0

---

## Done Definition

Slice 5.2 is complete when:

- Address verification is:
  - stored
  - visible
  - non-blocking

- No silent data mutation occurs

- Staff can distinguish:
  - verified vs unverified addresses