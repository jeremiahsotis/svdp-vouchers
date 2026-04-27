# Slice 2 — Checkpoint 2.2: Interaction + Touch Target Model

## Objective

Make all category interactions:
- unambiguous
- consistent
- mobile-first usable
- free of accidental clicks

---

## Problem Statement

Current issues:
- unclear what is clickable (card vs pills vs text)
- inconsistent interaction zones
- pills visually compete with primary action
- mobile tap targets not optimized

---

## Interaction Model (Non-Negotiable)

### Rule 1 — The Card is the Button
- Entire category card = primary interaction target
- Clicking anywhere on card → opens category

### Rule 2 — Pills are Informational ONLY
- “X selected”
- “X items ready”

These:
- are NOT clickable
- must NOT intercept pointer events

### Rule 3 — No Competing Targets
Inside a card:
- there is ONE action only
- no nested clickable elements

### Rule 4 — Explicit State

Card must visually communicate:
- default
- hover (desktop only)
- active/pressed
- selected/open

---

## Accessibility (Required)

- Cards must be keyboard accessible
- Must support:
  - Enter
  - Space
- Must include:
  - role="button"
  - tabindex="0"
  - aria-expanded (if expandable)

---

## Touch Targets

- Minimum 44px height for interactive areas
- Entire card must be comfortably tappable
- No “precision tapping” required

---

## Visual Hierarchy Fixes

- Title = primary
- Description = secondary
- Pills = tertiary (de-emphasized)

---

## Scope

### Included
- card click handling
- event delegation cleanup
- pointer-events control
- keyboard support
- aria attributes

### Excluded
- layout (2.1)
- search (2.3)
- catalog data
- pricing logic

---

## Acceptance Criteria

- clicking anywhere on card triggers category open
- pills never intercept clicks
- no accidental mis-clicks
- keyboard navigation works
- mobile tap feels natural (no dead zones)

---

## Done When

- interaction is predictable without thinking
- no confusion about “what to tap”