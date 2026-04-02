# Slice 02 — Hard Stop Checkpoints

## Checkpoint 1 — Schema + Data Contract
### Goal
Add the minimum schema/data support for Conference coverage rules and voucher-item snapshot amounts.

### Must Complete
- Add/finalize catalog coverage fields
- Add/finalize voucher-item snapshot fields
- Update schema/version/install path as needed
- Keep migrations conservative and compatible with current repo state

### Must Not Do Yet
- Do not wire admin UI
- Do not change request form math
- Do not add modal

### Hard Stop
Stop after schema changes are implemented and reviewed.

### Evidence To Review
- touched schema code
- migration/version changes
- no UI changes yet

### Commit
`feat(schema): add conference coverage fields for furniture vouchers`

---

## Checkpoint 2 — Catalog Admin Coverage UI
### Goal
Allow admin to manage Conference coverage rules at catalog-item level.

### Must Complete
- Add coverage type control
- Add coverage value control
- Default new items to 50 percent
- Persist values through existing catalog save path

### Must Not Do Yet
- Do not change request-form calculation logic yet
- Do not add approval modal
- Do not alter document/email code

### Hard Stop
Stop after catalog admin create/edit/save works.

### Evidence To Review
- admin form renders controls
- save path persists values correctly
- new items default correctly

### Commit
`feat(catalog): add conference coverage configuration to furniture catalog`

---

## Checkpoint 3 — Request Form Coverage Math
### Goal
Replace estimate display logic with coverage-aware calculations.

### Must Complete
- Update request-form estimate logic
- Show Estimated item total
- Show Estimated Conference portion
- Show Delivery fee
- Remove neighbor-payment language from touched form UI

### Must Not Do Yet
- Do not add approval modal
- Do not start document/pdf work

### Hard Stop
Stop after estimates render correctly for percent and fixed coverage.

### Evidence To Review
- percent example works
- fixed-dollar example works
- range example works
- no neighbor-owes language

### Commit
`feat(request-form): apply conference coverage rules to furniture estimates`

---

## Checkpoint 4 — Approval Modal + Submission Gate
### Goal
Require explicit approval before submission.

### Must Complete
- Add approval modal
- Show combined total Conference commitment
- Approve path submits
- Edit path returns to form state
- Cancel path abandons submission without creating final voucher

### Must Not Do Yet
- Do not start PDF/email work
- Do not expand into cashier doc changes

### Hard Stop
Stop once approval gating is working end-to-end.

### Evidence To Review
- submit blocked until approval
- cancel does not submit
- edit returns to form
- combined total displayed

### Commit
`feat(request-form): require conference approval before furniture voucher submission`