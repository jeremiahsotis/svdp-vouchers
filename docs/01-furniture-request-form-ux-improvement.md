# Slice 01 — Furniture Request Form UX Improvement

## OBJECTIVE
Replace flat item list with category-driven, mobile-first selection UX:
- Category touch targets
- Expandable sections
- Search
- Sticky summary

## AUTHORITATIVE FILES
- public/templates/voucher-request-form.php
- public/js/voucher-request.js
- includes/class-furniture-catalog.php
- includes/class-shortcodes.php

---

## REQUIREMENTS

### UI STRUCTURE

Replace flat list with:

1. Search bar (top)
2. Category cards:
   - Used Furniture
   - Handmade Furniture
   - Mattresses & Frames
   - Household Goods
3. Expandable category sections
4. Sticky summary footer

---

### CATEGORY BEHAVIOR

- Tap category → expand inline
- Multiple categories may remain open
- Show selected count per category

---

### ITEM ROW

Each item:
- Name
- Price display (range or fixed)
- Add/remove toggle

NO checkboxes.

---

### SEARCH

- Filters across all categories
- Replaces category view when active
- Clearing search restores categories

---

### STICKY SUMMARY

Always visible:

- Selected item count
- Estimated item total (range)
- Estimated Conference portion (range)
- Delivery toggle

---

### DELIVERY TOGGLE

If enabled:
- Show address fields inline
- Add $50 to Conference estimate

---

## DATA LOGIC

- Use catalog snapshot values
- DO NOT re-fetch pricing later

---

## NON-NEGOTIABLE UX RULES

- No page reloads
- Max 2 taps to add item
- No hidden math
- No modal interruptions

---

## CHECKPOINTS

### Checkpoint 1
- Category cards render
- Expand/collapse works
- Commit

### Checkpoint 2
- Item add/remove works
- Selected counts update
- Commit

### Checkpoint 3
- Sticky summary calculates correctly
- Delivery toggle works
- Commit

### Checkpoint 4
- Search filters items correctly
- Commit

---

## DONE WHEN

- No flat list exists
- Categories + search fully functional
- Mobile flow requires no explanation