# Slice 2 — Voucher Request Form UI Refactor (Authoritative Implementation Brief)

## Objective

Stabilize and improve the Voucher Request Form so it is:
- structurally stable across all breakpoints
- clearly navigable (especially on mobile)
- predictable in interaction
- aligned with pricing and catalog expectations

This slice is UI/UX only. No business logic changes.

---

## Scope

### Included
1. Layout contract (grid, cards, summary column)
2. Category card interaction model
3. Touch/click target clarity
4. Catalog browse usability improvements
5. Search normalization (singular/plural handling)
6. Pricing messaging placement (UI only)

### Excluded
- voucher pricing logic
- catalog data model changes
- backend APIs
- delivery system (explicitly deferred)
- snapshot/delivery integration

---

## Constraints (Non-Negotiable)

- Mobile-first design
- No regression in existing voucher creation flow
- No change to calculation logic
- No removal of catalog completeness
- No reliance on delivery system

---

## Architectural Principles

### 1. Layout is a contract, not decoration
Grid defines structure. Cards must conform.

### 2. Cards are atomic
Each category card must:
- contain all elements
- never overflow
- have a single clear interaction model

### 3. Summary must never break the system
- never compress grid
- must stack before it breaks layout

### 4. Browsing > Searching
Search is assistive, not primary.

---

## Slice Breakdown

### Checkpoint 2.1 — Layout Contract Fix
- grid stabilization
- card containment
- summary responsiveness

### Checkpoint 2.2 — Interaction + Touch Targets
- fix click areas
- remove ambiguous zones
- unify card behavior

### Checkpoint 2.3 — Browse & Search Improvements
- singular/plural normalization
- improved scanning
- optional category refinements

---

## Dependencies

- Existing voucher request form template
- Existing catalog rendering logic
- Existing pricing display logic

---

## Risks

- breaking mobile layout
- unintended CSS cascade effects
- summary column behavior regression

---

## Acceptance Criteria (Slice Complete)

- no overlapping UI elements
- clean, predictable interaction zones
- search works for singular and plural
- pricing messaging clearly visible at decision points
- mobile usability improved without losing desktop efficiency

---

## Done When

- all 3 checkpoints pass acceptance criteria
- no regression in voucher creation
- UI is stable across screen sizes