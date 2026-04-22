# Slice 1 — Copy + Policy Language

## Objective
Clarify voucher pricing expectations and redemption rules without modifying any pricing logic, catalog behavior, or workflow.

---

## Scope

### Included
1. Replace confusing “final pricing may vary” language  
2. Add 30-day expiration rule  
3. Add single-visit redemption rule  
4. Add clear pricing explanation (50% rule + mattress/frame exception)

### Excluded
- Pricing calculations
- Catalog structure
- Cashier workflows
- Approval logic
- Delivery system
- Address verification
- UI redesign

---

## Locked Wording

### Maximum Conference Cost
> The amount shown here is the maximum Conference cost for this voucher. Final fulfilled pricing may be lower based on the items chosen.

---

### Redemption Rule
> This voucher expires 30 days after issuance. It must be redeemed in one visit; remaining items cannot be saved for a later visit.

---

### Pricing Explanation
> Conference prices are 50% of the retail prices shown, except Mattress/Frame Bundles, which use the exact price shown.

---

## Placement Requirements

### Voucher Request Form
- Replace existing “Final fulfilled pricing may vary…” sentence
- Add redemption rule in visible location during furniture selection
- Add pricing explanation near pricing summary (NOT buried)

---

### Approval Summary (later checkpoint)
- Reinforce maximum Conference cost language
- Reinforce pricing rule
- Optionally repeat redemption rule if not already visible

---

### Neighbor Voucher Document
- Include redemption rule
- Ensure language is neutral (no “owed” language)
- Keep pricing phrasing consistent with request form

---

## Constraints

- No changes to:
  - totals
  - calculations
  - JS logic (unless strictly needed for display)
- No new data fields
- No schema changes
- No behavioral side effects

---

## Acceptance Criteria

- Old wording removed completely
- New max-cost wording visible on request form
- Redemption rule visible on request form
- Pricing explanation visible near summary
- No change in totals or calculations
- No regression in form behavior

---

## Risks

- Accidental modification of pricing display logic
- Copy added in wrong location (too hidden)
- Duplicate/conflicting wording across surfaces

---

## Done When

- All three wording blocks are implemented
- Placement is user-visible and intuitive
- No logic changes occurred
- UI renders cleanly on mobile and desktop

---