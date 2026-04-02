You are executing inside the SVdP Vouchers WordPress plugin repo on branch `codex/slice-02-conference-coverage-and-approval`.

Your task is to implement **Slice 02 — Conference Coverage Rules + Approval Modal** and stop exactly at the required checkpoints.

## Objective
Replace fixed 50% assumption with catalog-driven Conference coverage rules and add a required approval modal before final furniture voucher submission.

## Authoritative Files
- `admin/views/tab-furniture-catalog.php`
- `admin/js/furniture-admin.js`
- `includes/class-furniture-catalog.php`
- `includes/class-voucher.php`
- `includes/class-database.php`
- `public/templates/voucher-request-form.php`
- `public/js/voucher-request.js`

## Locked Product Rules
- Coverage configured only in catalog/admin
- Default new items to 50 percent
- Allow:
  - percent coverage
  - fixed-dollar coverage
- Neighbor never pays anything
- Store absorbs remainder
- Request form shows:
  - Estimated item total
  - Estimated Conference portion
  - Delivery fee
- Approval modal must show:
  - Estimated item total
  - Estimated Conference portion
  - Delivery fee
  - Estimated total Conference commitment
- Submission is not complete until “I approve this amount” is selected
- Modal actions:
  - I approve this amount
  - Edit this voucher
  - Cancel this voucher

## Execution Instructions
1. Read the authoritative files first.
2. Implement only Checkpoint 1.
3. Stop after Checkpoint 1 is complete.
4. Summarize exactly what changed and why.
5. Do not continue unless explicitly instructed.

## Checkpoint 1
Implement schema/data support for:
- catalog coverage fields
- voucher-item snapshot fields

Do not implement UI or modal yet.

## Required Stop Condition
Stop immediately after schema/data contract work is complete.