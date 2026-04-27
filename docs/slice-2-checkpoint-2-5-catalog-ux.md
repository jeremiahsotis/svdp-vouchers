# Slice 2 — Checkpoint 2.5: Catalog UX Polish

## Objective

Reduce cognitive load when browsing and selecting catalog items.

Make it easier to:
- scan items quickly
- understand pricing at a glance
- select items without hesitation

No structural or logic changes.

---

## Scope

### Included
- item row/card visual hierarchy
- spacing and grouping
- price visibility improvements
- mobile scan optimization

### Excluded
- catalog data
- pricing calculations
- search logic
- category structure
- interaction model (cards remain as defined in 2.2)

---

## Design Principles (Non-Negotiable)

### 1. Scan > Read
Users should understand:
- what the item is
- what it costs
- how to act

without reading full text blocks.

---

### 2. Price is a primary signal

Each item must clearly communicate:
- retail price
- Conference cost (50% rule)

without requiring interpretation

---

### 3. Reduce vertical fatigue

- tighter grouping of related info
- eliminate unnecessary whitespace
- keep touch targets intact

---

### 4. Preserve simplicity

Do NOT:
- add filters
- add sorting controls
- add new UI components

---

## Required Improvements

### Item Layout

Each item should visually separate:

- Name (primary)
- Pricing (secondary but prominent)
- Controls (tertiary but accessible)

---

### Pricing Presentation

For each item:

- Retail price clearly visible
- Conference cost clearly visible
- Must visually communicate relationship (not just numbers)

---

### Spacing

- consistent vertical rhythm
- no cramped stacking
- no excessive gaps

---

### Mobile Behavior

- items easy to scan while scrolling
- no horizontal overflow
- quantity controls remain easy to tap

---

## Acceptance Criteria

- users can scan items without reading descriptions
- pricing is understandable at a glance
- item rows feel consistent and predictable
- mobile scrolling feels smooth and efficient
- no regression in selection behavior

---

## Done When

- catalog feels faster to use
- fewer visual decisions required per item
- no new UI complexity introduced