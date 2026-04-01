You are executing inside the SVdP Vouchers WordPress plugin repo on branch `codex/slice-01-furniture-request-form-ux`.

Your task is to implement **Slice 01 — Furniture Request Form UX Improvement** and stop exactly at the required checkpoints.

## Objective
Improve the furniture voucher request form UX without breaking the clothing voucher flow or changing the overall form entry point.

Implement:
- category touch targets
- inline expandable category sections
- search
- sticky summary
- delivery toggle with inline address reveal

Do not implement:
- Conference coverage rule changes
- approval modal
- printable/email voucher docs
- cashier-side redemption changes
- React/Vue/build tooling changes

## Authoritative Files
- `public/templates/voucher-request-form.php`
- `public/js/voucher-request.js`
- `includes/class-shortcodes.php`
- `includes/class-furniture-catalog.php`
- `includes/class-voucher.php`

## Locked Product Rules
- Furniture categories:
  - Used Furniture
  - Handmade Furniture
  - Mattresses & Frames
  - Household Goods
- Category cards expand inline
- Multiple categories may stay open
- Search filters across all furniture items
- Sticky summary must show:
  - selected item count
  - estimated item total
  - estimated Conference portion
  - delivery fee only when selected
- Delivery toggle reveals address fields inline
- Clothing path must remain visually and functionally unchanged on the surface
- No page reloads
- No new framework

## Execution Instructions
1. Read the authoritative files first.
2. Implement only Checkpoint 1.
3. Stop after Checkpoint 1 is complete.
4. Summarize exactly what changed, which files changed, and any assumptions.
5. Do not continue to later checkpoints unless explicitly instructed.

## Checkpoint 1
Render category-first furniture selection shell:
- add search input
- add four category cards
- add expandable section containers
- preserve clothing flow

## Required Stop Condition
After Checkpoint 1 renders correctly, stop.
Do not implement item add/remove yet.