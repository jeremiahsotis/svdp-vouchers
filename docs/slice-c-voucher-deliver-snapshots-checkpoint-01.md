# Slice 1 — Checkpoint 1: Request Form Copy

## Objective
Update the furniture voucher request form to reflect correct pricing expectations and redemption rules.

---

## Tasks

### 1. Replace Existing Language
Find and replace:

"Final fulfilled pricing may vary from the estimate shown here."

With:

"The amount shown here is the maximum Conference cost for this voucher. Final fulfilled pricing may be lower based on the items chosen."

---

### 2. Add Redemption Rule
Add visible text:

"This voucher expires 30 days after issuance. It must be redeemed in one visit; remaining items cannot be saved for a later visit."

Placement:
- Within furniture request flow
- Visible during item selection (not hidden)

---

### 3. Add Pricing Explanation
Add near pricing summary:

"Conference prices are 50% of the retail prices shown, except Mattress/Frame Bundles, which use the exact price shown."

Placement:
- Near Estimated Total / Conference Portion
- Not buried in help text

---

## Files Likely Modified

- public/templates/voucher-request-form.php  
- public/css/voucher-forms.css (only if spacing needed)

---

## Constraints

- DO NOT modify pricing logic
- DO NOT modify totals calculation
- DO NOT modify JS behavior
- DO NOT redesign layout
- DO NOT touch cashier or delivery systems

---

## Validation

- Pricing numbers remain identical before/after
- Text appears in correct locations
- Mobile layout still readable
- No overlapping UI issues introduced

---

## Stop Condition

Stop when:
- Old wording removed
- New wording present in all required locations
- No behavior changes occurred