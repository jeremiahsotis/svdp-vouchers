# Slice 1 — Checkpoint 2: Approval Summary Copy

## Objective
Ensure the voucher approval / confirmation surface clearly communicates:
- the displayed total is the **maximum Conference cost**
- how pricing works (50% vs mattress/frame exception)
- (optionally) a short redemption reminder

This is the **decision surface** where the voucher is approved.

---

## Scope

### Included
- Approval modal / confirmation block copy updates
- Pricing explanation near totals
- Maximum cost clarification
- Optional short redemption reminder

### Excluded
- Pricing calculations
- Approval logic
- Catalog behavior
- Request form copy (already handled in Checkpoint 1)
- Neighbor-facing document (Checkpoint 3)

---

## Locked Wording

### Maximum Conference Cost
> The amount shown here is the maximum Conference cost for this voucher. Final fulfilled pricing may be lower based on the items chosen.

---

### Pricing Explanation
> Conference prices are 50% of the retail prices shown, except Mattress/Frame Bundles, which use the exact price shown.

---

### Optional Short Redemption Reminder
> Voucher must be redeemed in one visit within 30 days.

---

## Placement Requirements

The wording must appear:

- **Adjacent to final totals**
  - Estimated Total
  - Estimated Conference Portion

- **Before or near the approval action**
  - Approve / Confirm / Submit button

- Must be visible **without scrolling in typical viewport**

---

## Likely Files

- public/js/voucher-request.js  
- public/templates/voucher-request-form.php  

---

## Implementation Notes

- This is a **presentation-only change**
- Do NOT modify:
  - totals calculations
  - discount logic
  - item aggregation
- If modal content is built in JS:
  - inject content in render function
- If server-rendered:
  - modify template output

---

## Constraints

- No behavioral changes
- No new data fields
- No schema changes
- No refactoring of approval flow
- No UI redesign

---

## Acceptance Criteria

- Approval surface clearly states:
  - total is maximum Conference cost
  - pricing rule is visible
- Optional redemption rule present (if space allows cleanly)
- Totals remain unchanged before/after
- No regression in approval flow

---

## Risks

- Copy placed too far from totals → ignored
- Accidentally modifying calculation logic
- Duplicating conflicting wording from request form

---

## Done When

- Approval UI includes required wording
- Wording is clearly visible near totals and action
- No changes to pricing or logic occurred
- UI renders cleanly on mobile and desktop

---