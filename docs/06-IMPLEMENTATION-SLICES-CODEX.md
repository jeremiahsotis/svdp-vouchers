# Implementation Slices - Exact Order

This file is intentionally written in a way that can be copied into an agent workflow. Do not reorder slices.

---

## Slice 1 - Cashier shell foundation and session reliability

### Goal
Replace the current cashier interaction model with a persistent shell that does not require full page reloads and does not drift into stale-page behavior.

### Scope
- keep current clothing voucher functionality available
- do not add furniture features yet
- replace cashier shell scaffolding only

### Files likely touched
- `svdp-vouchers.php`
- `public/templates/cashier-station.php`
- `public/js/cashier-station.js` (deprecate/replace)
- new partial templates under `public/templates/cashier/partials/`
- optional minimal new JS shell file

### Required work
1. Build a new cashier page shell using HTMX + Alpine.
2. Add keepalive route and client ping every 60 seconds.
3. Remove any cashier UX that tells users to refresh due to stale state.
4. Preserve current clothing voucher list/detail actions inside the new shell.
5. Ensure current clothing redemption still works on the surface.

### Hard rules
- no React
- no full page reload dependency
- no session-expired normal flow

### Checkpoint
Stop when:
- cashier page loads once and stays live
- clothing voucher list and clothing redemption still work
- no furniture-specific functionality added yet

---

## Slice 2 - Database expansion and permissions

### Goal
Add furniture-ready data structures and capability model without exposing unfinished UI.

### Scope
- add/alter tables
- add capability registration
- add helper/service methods
- no public furniture request UI yet

### Files likely touched
- `includes/class-database.php`
- `svdp-vouchers.php`
- new `includes/class-permissions.php`
- `includes/class-voucher.php`
- admin role/capability wiring

### Required work
1. Add root voucher type support.
2. Create furniture-related tables from the schema handoff.
3. Add `svdp_redeem_furniture_vouchers` capability.
4. Add optional `svdp_manage_furniture_catalog` capability.
5. Ensure existing cashier viewers can still view all vouchers once furniture exists.

### Hard rules
- no breaking migration for existing clothing vouchers
- clothing rows default cleanly to `voucher_type = clothing`

### Checkpoint
Stop when:
- migrations run cleanly
- capabilities exist
- no visible furniture workflow exposed yet

---

## Slice 3 - Furniture catalog and cancellation reason admin

### Goal
Provide admin-managed source data for furniture request and redemption flows.

### Scope
- catalog CRUD
- cancellation reason CRUD

### Files likely touched
- `includes/class-admin.php`
- new catalog/cancellation reason classes
- admin tab views
- admin JS only where needed

### Required work
1. Add admin tab(s) for furniture catalog.
2. Add admin tab(s) for furniture cancellation reasons.
3. Support category, pricing type, price fields, sort order, active state.
4. Support archive/inactive behavior rather than destructive deletes where practical.

### Checkpoint
Stop when:
- admins can manage catalog items
- admins can manage cancellation reasons
- no public furniture request flow yet

---

## Slice 4 - Public furniture request flow

### Goal
Extend the public voucher form so it can create furniture vouchers while keeping clothing familiar on the surface.

### Scope
- public form branching
- furniture item selection
- delivery capture
- estimate display
- persistence of furniture vouchers/items/meta

### Files likely touched
- `public/templates/voucher-request-form.php`
- `public/js/voucher-request.js`
- `includes/class-voucher.php`
- new furniture voucher class/service if created
- catalog read endpoints

### Required work
1. Add voucher type choice.
2. Preserve clothing branch behavior on the surface.
3. Add furniture category-based selection UI.
4. Add sticky mobile summary.
5. Add delivery toggle and address fields.
6. Persist voucher root + furniture meta + voucher items with snapshots.

### Hard rules
- no inventory counts
- no stock logic
- no exact-price promises for range-priced items

### Checkpoint
Stop when:
- furniture voucher can be requested successfully
- furniture voucher appears in cashier list in read-only form
- fulfillment not yet implemented

---

## Slice 5 - Furniture cashier fulfillment workflow

### Goal
Implement the mobile-first furniture redemption workflow with strong validation.

### Scope
- furniture voucher detail UI
- item completion
- item photo upload
- substitution
- cancellation reason flow

### Files likely touched
- cashier partials
- new furniture mutation endpoints
- photo storage service
- furniture voucher model/service

### Required work
1. Make furniture vouchers visually distinct in the cashier list.
2. Add furniture voucher detail pane.
3. Sort items by snapshot sort order.
4. Add actual price entry.
5. Add photo upload with normalization/storage.
6. Add substitute-from-catalog and free-text substitution.
7. Add cancellation flow with required preset reason.
8. Enforce completion rule: no completed item without actual price and at least one photo.
9. Enforce elevated capability on all furniture mutation endpoints.

### Checkpoint
Stop when:
- elevated user can fully resolve all item states from a phone
- viewer-only user can see furniture outcomes but cannot mutate them

---

## Slice 6 - Voucher completion, receipt, and invoice generation

### Goal
Complete the furniture voucher lifecycle.

### Scope
- voucher completion
- receipt generation
- invoice generation/storage

### Files likely touched
- new invoice/receipt generator classes
- furniture completion endpoints/services
- template files for printable documents if used

### Required work
1. Allow voucher completion only when all items are resolved.
2. Generate neighbor receipt with no prices.
3. Generate stored conference invoice using actual fulfilled prices * 50% + delivery fee.
4. Link generated file paths to voucher/invoice records.

### Checkpoint
Stop when:
- completed furniture voucher produces both documents and stores invoice row

---

## Slice 7 - Statement generation and reporting

### Goal
Add organized accounting export support without payment tracking.

### Scope
- invoice listing
- default previous-month range
- statement generation
- invoice-to-statement linking

### Files likely touched
- admin invoice/statements views
- invoice/statement classes
- statement routes

### Required work
1. Add invoice list/filter UI.
2. Add statement generation UI.
3. Default to first/last day of previous month.
4. Include only invoices where `statement_id IS NULL`.
5. Generate and store statement file.
6. Attach invoices to statement.

### Checkpoint
Stop when:
- statements can be generated reliably and excluded from future runs

---

## Slice 8 - Final polish and regression pass

### Goal
Harden UX, permissions, and regressions.

### Required work
1. Regression test clothing request/redeem flows.
2. Confirm no full page reloads are required in cashier normal flow.
3. Confirm mobile ergonomics for furniture fulfillment.
4. Confirm photo storage cleanup and path handling are safe.
5. Confirm access control for viewers vs elevated redeemers.
6. Confirm invoices/statements do not duplicate.

### Final stop condition
Only mark done when the acceptance checklist passes in full.
