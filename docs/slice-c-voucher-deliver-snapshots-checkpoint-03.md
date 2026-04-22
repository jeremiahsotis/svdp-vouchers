# Slice 1 — Checkpoint 3: Neighbor Voucher Document Copy

## Objective
Update the neighbor-facing voucher document so it clearly communicates the voucher use rules without introducing pricing confusion or any “neighbor owes” language.

This checkpoint closes Slice 1 by making the actual voucher/receipt surface match the request form and approval flow.

---

## Scope

### Included
- Add the 30-day expiration rule to the neighbor-facing voucher document
- Add the one-visit / no partial redemption rule
- Confirm language remains neighbor-safe and dignity-aligned
- Keep approved amount language as-is unless a minor copy adjustment is needed for clarity

### Excluded
- Secure delivery/token logic
- Email/SMS delivery behavior
- Pricing calculations
- Conference-facing pricing explanations
- Cashier workflow changes
- Request form or approval modal changes

---

## Locked Wording

### Redemption Rule
> This voucher expires 30 days after issuance. It must be redeemed in one visit; remaining items cannot be saved for a later visit.

---

## Language Rules

### Allowed
- redemption deadline
- single-visit redemption requirement
- approved amount phrasing already intended for neighbor-facing use
- calm, direct, non-punitive language

### Not Allowed
- “Neighbor owes”
- Conference percentage explanations
- retail vs discounted pricing explanations
- internal operational jargon
- punitive or shaming phrasing

---

## Placement Requirements

The redemption rule must be visible on the neighbor-facing voucher document in a place where the neighbor is likely to see it before arriving.

Best placement:
- near the approved amount and delivery/use instructions
- or as a distinct note section before requested items
- or in the footer only if clearly visible and not buried

Do NOT hide it in tiny print.

---

## Likely Files

- `public/templates/documents/neighbor-voucher.php`

Potentially, only if needed:
- `includes/class-neighbor-voucher-document.php`

---

## Rendering Contract Constraints

This document must continue to follow the current Slice C contract:

### Allowed sources
- `delivery_view` for live/current fields
- `$document[...]` for frozen/render-time fields

### Disallowed
- reintroducing raw `$voucher[...]` fallback into `neighbor-voucher.php`

---

## Implementation Notes

- This is primarily a template copy change
- Prefer inserting the redemption rule as a visible note block or instruction block
- Do not alter approved amount calculations or formatting
- Do not alter requested items rendering
- Do not alter delivery status rendering

---

## Acceptance Criteria

- The redemption rule appears on the neighbor-facing voucher document
- The wording exactly matches the locked language
- No pricing confusion is introduced
- No “neighbor owes” language appears
- No raw `$voucher[...]` fallback is reintroduced
- Document still renders correctly on mobile, browser, and PDF output

---

## Risks

- burying the rule where it will be missed
- reintroducing raw voucher access in the template
- accidentally mixing Conference-facing pricing language into neighbor-facing copy

---

## Done When

- the neighbor-facing voucher document includes the redemption rule
- the wording is visible and clear
- the rendering contract remains intact
- no logic or delivery behavior changed

---