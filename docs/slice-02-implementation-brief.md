# Slice 02 — Conference Coverage Rules + Approval Modal

## Branch
codex/slice-02-conference-coverage-and-approval

## Objective
Replace the hardcoded/default assumption that furniture vouchers are always simple 50% calculations with catalog-driven Conference coverage rules, and add a required Vincentian approval modal before voucher submission.

This slice must support:
- default 50% Conference coverage on new catalog items
- per-catalog-item override by percent or fixed dollar amount
- request-form estimate display using Conference coverage rules
- approval modal shown before submission when Conference portion or delivery fee is greater than zero
- no neighbor-owes language anywhere

## Authoritative Execution Sources
- admin/views/tab-furniture-catalog.php
- admin/js/furniture-admin.js
- includes/class-furniture-catalog.php
- includes/class-voucher.php
- includes/class-database.php
- public/templates/voucher-request-form.php
- public/js/voucher-request.js
- schema recon already reviewed for touched tables

## Locked Product Decisions
- Conference coverage is defined at catalog/admin level only
- Default for new catalog items: 50% percent-based Conference coverage
- Coverage can be configured as:
  - percent
  - fixed dollar amount
- Neighbor never pays anything
- Store absorbs the remainder
- Neighbor-facing language must never imply payment by the neighbor
- Request form must show:
  - Estimated item total
  - Estimated Conference portion
  - Delivery fee
  - combined total Conference commitment in approval modal
- If Conference portion > 0 or delivery fee > 0, submission must be blocked by a confirmation modal
- Voucher is not submitted until user explicitly selects “I approve this amount”
- Confirmation modal actions:
  - I approve this amount
  - Edit this voucher
  - Cancel this voucher

## In Scope
- DB/schema updates needed for coverage rules and voucher-item snapshots
- Furniture catalog admin UI updates for coverage configuration
- Estimate calculation updates on the request form
- Approval modal on request submission
- Submission gating logic
- Remove neighbor-payment language from touched surfaces

## Out of Scope
- always-on printable/email voucher docs
- multilingual PDF work
- cashier-side final document generation
- clothing visual redesign
- inventory tracking

## Required Data Changes

### Catalog-Level Fields
Add or finalize:
- discount_type (`percent` | `fixed`)
- discount_value (decimal, default `50`)

These represent Conference coverage configuration, even if legacy internal naming still says "discount".

### Voucher Item Snapshot Fields
Add or finalize:
- discount_type_snapshot
- discount_value_snapshot
- conference_share_amount
- store_share_amount

If existing table/field naming differs, preserve compatibility carefully and document it in code comments or migration notes.

## Calculation Rules

### Percent-Based Coverage
conference_share = round(actual_or_estimated_price * (discount_value / 100), 2)

### Fixed-Dollar Coverage
conference_share = min(discount_value, actual_or_estimated_price)

### Store Share
store_share = price - conference_share

### Range Estimates
For range-priced items:
- calculate min and max Conference share separately from min and max price
- aggregate totals across selected items

## Request Form Display Rules
Show:
- Estimated item total
- Estimated Conference portion
- Delivery fee

Do not show:
- neighbor share
- store share
- "requester pays"
- "neighbor owes"

## Approval Modal Rules
Trigger modal if:
- estimated Conference portion > 0
- OR delivery fee > 0

Modal must display:
- Estimated item total
- Estimated Conference portion
- Delivery fee
- Estimated total Conference commitment

Modal text must clearly state:
- voucher is not submitted until approval is clicked

## Submission Rules
- If modal approval is required, do not persist the final voucher before explicit approval
- “Edit this voucher” returns user to current form state
- “Cancel this voucher” abandons submission and does not create a final voucher

## Files Expected To Change
- includes/class-database.php
- includes/class-furniture-catalog.php
- admin/views/tab-furniture-catalog.php
- admin/js/furniture-admin.js
- public/templates/voucher-request-form.php
- public/js/voucher-request.js
- includes/class-voucher.php

## Acceptance Criteria
- New catalog items default to 50% Conference coverage
- Admin can switch coverage to percent or fixed dollar
- Request-form estimates use coverage rules correctly
- Neighbor payment language is absent from touched UI
- Approval modal blocks final submission until explicitly approved
- Cancel path does not create a submitted voucher
- Edit path returns to form state
- No unrelated cashier/doc/email work lands in this slice

## Testing Expectations
- Create/edit catalog item with percent coverage
- Create/edit catalog item with fixed-dollar coverage
- Verify estimate math for fixed-price items
- Verify estimate math for range-priced items
- Verify modal appears when it should
- Verify modal does not allow silent submission
- Verify cancel/edit behavior
- Verify no neighbor-owes language remains in touched surfaces