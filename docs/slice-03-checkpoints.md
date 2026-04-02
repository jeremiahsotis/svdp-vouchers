# Slice 03 — Hard Stop Checkpoints

## Checkpoint 1 — Internal PDF Dependency
### Goal
Add plugin-internal PDF generation dependency cleanly.

### Must Complete
- Add TCPDF into plugin in a maintainable way
- Wire bootstrap/loading path needed for use by the plugin
- Confirm a minimal PDF can be generated in development

### Must Not Do Yet
- Do not build full shared document service
- Do not alter cashier UI
- Do not add email sending yet

### Hard Stop
Stop after internal PDF dependency is installed and proven usable.

### Evidence To Review
- dependency files added
- plugin can instantiate/use TCPDF
- minimal PDF generation proof

### Commit
`feat(pdf): add internal tcpdf dependency for voucher documents`

---

## Checkpoint 2 — Shared Neighbor Voucher Document Service
### Goal
Create reusable document generation architecture.

### Must Complete
- Add `class-neighbor-voucher-document.php`
- Add `class-voucher-i18n.php`
- Add shared `neighbor-voucher.php` template
- Support English first
- Generate neighbor-facing PDF for at least one voucher type through shared service

### Must Not Do Yet
- Do not add cashier UI actions
- Do not add email sending
- Do not finish all languages yet if English-first helps prove architecture

### Hard Stop
Stop after shared service exists and can generate a basic English PDF.

### Evidence To Review
- service class
- i18n class
- shared template
- English PDF output

### Commit
`feat(documents): add shared neighbor voucher pdf generation service`

---

## Checkpoint 3 — Multilingual Rendering
### Goal
Add Spanish and Burmese support to the shared voucher document path.

### Must Complete
- Add EN/ES/Burmese dictionaries
- Render PDFs in all three languages
- Verify Unicode-safe Burmese output

### Must Not Do Yet
- Do not add cashier email actions
- Do not refactor unrelated receipt/invoice templates beyond necessity

### Hard Stop
Stop after all three language variants render successfully.

### Evidence To Review
- sample output for EN/ES/Burmese
- no broken glyph rendering in Burmese

### Commit
`feat(i18n): add multilingual neighbor voucher pdf rendering`

---

## Checkpoint 4 — Cashier UI Actions
### Goal
Expose always-on document actions in voucher detail UI.

### Must Complete
- Add Open Voucher action
- Add Email Voucher action
- Add language selector
- Remove/bypass admin toggle gating for these actions across all voucher types

### Must Not Do Yet
- Do not over-refactor cashier UI
- Do not change invoice/statement behavior

### Hard Stop
Stop after UI actions appear correctly for all voucher types.

### Evidence To Review
- clothing voucher detail shows actions
- furniture voucher detail shows actions
- toggle no longer blocks visibility

### Commit
`feat(cashier): expose always-on voucher print and email actions`

---

## Checkpoint 5 — Email PDF Delivery
### Goal
Finish end-to-end neighbor-facing voucher emailing.

### Must Complete
- Generate PDF in selected language
- Attach PDF to `wp_mail()`
- Send email from voucher action flow
- Handle errors clearly enough for cashier/admin use

### Must Not Do Yet
- Do not broaden into unrelated messaging features

### Hard Stop
Stop after end-to-end email with PDF attachment works.

### Evidence To Review
- successful email flow
- attached PDF correct language/content
- failure path surfaced reasonably

### Commit
`feat(email): send multilingual neighbor voucher pdf attachments`