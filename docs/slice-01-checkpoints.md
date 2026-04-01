# Slice 01 — Hard Stop Checkpoints

## Checkpoint 1 — Request Form Structure Only
### Goal
Render category-first furniture selection shell without changing submission behavior yet.

### Must Complete
- Add search input to furniture section
- Add four category cards/touch targets
- Add empty expandable containers or equivalent structure for category sections
- Preserve clothing form rendering

### Must Not Do Yet
- Do not wire full item add/remove behavior
- Do not change calculations
- Do not refactor unrelated submission logic

### Hard Stop
Stop after the structure renders correctly in the browser.

### Evidence To Review
- screenshot or local verification of category cards
- clothing flow still renders unchanged

### Commit
`feat(request-form): add category-first furniture selection shell`

---

## Checkpoint 2 — Item Selection Behavior
### Goal
Make category expansion and item add/remove work.

### Must Complete
- Expand/collapse category sections inline
- Render category items correctly
- Add/remove controls work
- Selected count updates per category

### Must Not Do Yet
- Do not add sticky summary calculation rewrites beyond what is necessary
- Do not add approval modal
- Do not change backend storage shape

### Hard Stop
Stop once users can browse categories and add/remove furniture items reliably.

### Evidence To Review
- selected count changes by category
- item rows render with fixed/range display

### Commit
`feat(request-form): implement category expansion and item selection`

---

## Checkpoint 3 — Search + Sticky Summary
### Goal
Add findability and persistent feedback.

### Must Complete
- Search filters across categories
- Clear search restores category browsing
- Sticky summary appears and updates
- Summary includes selected count, estimated item total, estimated Conference portion, and delivery fee line if selected

### Must Not Do Yet
- Do not implement Slice 02 coverage math
- Do not add modal approval
- Do not change document/email logic

### Hard Stop
Stop once search and sticky summary are working and stable.

### Evidence To Review
- search returns matching items
- sticky summary remains visible during scroll
- summary updates when items are added/removed

### Commit
`feat(request-form): add search and sticky summary for furniture vouchers`

---

## Checkpoint 4 — Delivery UX + Regression Pass
### Goal
Finalize this slice cleanly.

### Must Complete
- Delivery toggle reveals address fields inline
- Delivery fee line appears only when selected
- Regression-test clothing flow
- Regression-test existing furniture submission path

### Must Not Do Yet
- Do not start catalog discount rules
- Do not start approval modal
- Do not start printable/email voucher work

### Hard Stop
Stop after regression pass and before any next-slice work.

### Evidence To Review
- delivery fields behave correctly
- clothing flow unchanged on surface
- furniture submission still posts correctly

### Commit
`feat(request-form): finish furniture delivery UX and validate regressions`