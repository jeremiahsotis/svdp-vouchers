# Slice 02 — Conference Coverage Rules + Approval Modal

## OBJECTIVE
Replace fixed 50% assumption with catalog-driven Conference coverage rules and add required approval step.

---

## AUTHORITATIVE FILES
- includes/class-furniture-catalog.php
- includes/class-voucher.php
- includes/class-database.php
- admin/views/tab-furniture-catalog.php
- public/templates/voucher-request-form.php
- public/js/voucher-request.js

---

## DATA MODEL

### Add to catalog
- discount_type (percent | fixed)
- discount_value (default 50)

### Add to voucher_items snapshot
- discount_type_snapshot
- discount_value_snapshot
- conference_share_amount
- store_share_amount

---

## CALCULATION RULES

### Percent
conference_share = price * (percent / 100)

### Fixed
conference_share = min(value, price)

### Store share
store_share = price - conference_share

---

## REQUEST FORM DISPLAY

Show ONLY:

- Estimated item total
- Estimated Conference portion
- Delivery fee

NEVER show:
- neighbor pays
- store share

---

## APPROVAL MODAL

Trigger if:
- Conference portion > 0 OR
- Delivery fee > 0

---

### Modal content

- Estimated item total
- Estimated Conference portion
- Delivery fee
- Total Conference commitment

Text:
"This voucher is not submitted until you approve this amount."

---

### Actions

- I approve this amount → submit
- Edit → return to form
- Cancel → discard

---

## SUBMISSION RULE

Voucher is NOT persisted until approval is confirmed.

---

## ADMIN UX

Catalog editor:

- Discount type selector
- Discount value field

Label:
"Amount the Conference will cover"

---

## CHECKPOINTS

### Checkpoint 1
- DB schema updated
- Commit

### Checkpoint 2
- Catalog UI supports discount rules
- Commit

### Checkpoint 3
- Request form calculates correctly
- Commit

### Checkpoint 4
- Approval modal blocks submission
- Commit

---

## DONE WHEN

- No hardcoded 50% remains
- All math comes from catalog rules
- Approval is required before submission