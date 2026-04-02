# Slice 03 — Neighbor-Facing Voucher PDF/Email + EN/ES/Burmese

## OBJECTIVE
Create always-on printable + emailable voucher documents for ALL voucher types.

---

## AUTHORITATIVE FILES
- includes/class-furniture-receipt.php
- includes/class-voucher.php
- public/templates/cashier/partials/voucher-detail.php
- public/templates/cashier/partials/voucher-detail-furniture.php
- public/templates/documents/
- includes/

---

## LOCKED RULES

- No admin toggle
- All vouchers support print + email
- Email must send PDF
- Languages: EN, ES, Burmese
- Neighbor sees ONLY approved amount

---

## NEW COMPONENT

Create:

includes/class-neighbor-voucher-document.php

Responsibilities:
- build data
- render HTML
- generate PDF
- send email

---

## PDF DEPENDENCY

Add TCPDF inside plugin.

DO NOT assume external PDF service.

---

## TEMPLATE

Create:

public/templates/documents/neighbor-voucher.php

Single template + language dictionary.

---

## LANGUAGE SYSTEM

Create:

includes/class-voucher-i18n.php

Provide:
- English
- Spanish
- Burmese

Use simple key-value arrays.

---

## DOCUMENT CONTENT

Show:

- Name
- DOB
- Items requested
- Delivery included (yes/no)
- Estimated amount approved for this voucher

DO NOT show:
- totals
- delivery fee
- pricing
- invoice logic

---

## EMAIL

Use wp_mail()

Attach generated PDF.

---

## STORAGE

Save PDF to:

uploads/svdp-vouchers/{voucher_id}/documents/

---

## UI

Cashier view:

Add:
- Open Voucher
- Email Voucher
- Language selector

---

## CHECKPOINTS

### Checkpoint 1
- TCPDF added
- PDF generation works
- Commit

### Checkpoint 2
- Template renders correctly
- Commit

### Checkpoint 3
- Language switching works
- Commit

### Checkpoint 4
- Email sends PDF
- Commit

### Checkpoint 5
- UI buttons available for all vouchers
- Commit

---

## DONE WHEN

- Every voucher has printable + emailable PDF
- Works in EN / ES / Burmese
- No admin toggle exists