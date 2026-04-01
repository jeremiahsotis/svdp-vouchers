# Slice 01 — Furniture Request Form UX Improvement

## Branch
codex/slice-01-furniture-request-form-ux

## Objective
Improve the furniture voucher request form UX without changing the overall public form entry point or breaking clothing voucher behavior.

This slice must replace the current flat furniture item selection experience with a category-first, mobile-first interaction model that includes:
- category touch targets
- inline expandable category sections
- cross-category search
- sticky summary footer
- inline delivery toggle + delivery address reveal

Clothing voucher behavior must remain visually and functionally unchanged on the surface.

## Authoritative Execution Sources
- public/templates/voucher-request-form.php
- public/js/voucher-request.js
- includes/class-shortcodes.php
- includes/class-furniture-catalog.php
- includes/class-voucher.php
- repo recon outputs already reviewed for this slice

## Locked Product Decisions
- Keep the same overall request form entry point
- Do not create a separate page or separate flow for furniture selection
- Use category touch targets:
  - Used Furniture
  - Handmade Furniture
  - Mattresses & Frames
  - Household Goods
- Category touch targets expand inline
- Multiple categories may remain open at once
- Search filters across all categories
- Sticky summary must remain visible during item selection
- Summary must show:
  - selected item count
  - estimated item total
  - estimated Conference portion
  - delivery fee if selected
- Do not show neighbor payment language
- No full page reloads
- No modal interruptions in this slice

## In Scope
- Restructure furniture item selection UI in the public request form
- Add inline category expansion/collapse behavior
- Add search
- Add sticky summary calculations using existing catalog values
- Add delivery toggle and inline delivery address reveal
- Preserve form submission compatibility with existing furniture voucher request handling

## Out of Scope
- Conference coverage rule changes
- approval modal
- printable/email voucher docs
- cashier-side fulfillment changes
- clothing voucher redesign
- inventory/stock tracking

## Existing Repo Reality To Respect
- This is a WordPress plugin with server-rendered PHP templates and JS behavior layered on top
- Current request form logic already exists; do not rewrite unrelated form behavior
- Furniture catalog data already exists; use that source, do not invent a second catalog path
- Do not introduce React, Vue, build tooling, or unrelated front-end architecture changes

## Required UI Behavior

### Search
- Search input appears above category cards in furniture mode
- Search filters across all furniture catalog items
- While search is active, matching results may be shown outside category grouping if that is simpler
- Clearing search restores category-card browsing

### Category Cards
Render four mobile-friendly touch targets:
- Used Furniture
- Handmade Furniture
- Mattresses & Frames
- Household Goods

Each card shows:
- category label
- optional short helper label if already supported cleanly
- selected count for that category

### Expanded Category Section
When a category is opened, render item rows for that category.

Each item row must show:
- item name
- price display
  - fixed: single amount
  - range: min-max
- clear add/remove control

Do not use long checkbox lists.

### Sticky Summary
The summary must remain visible while browsing/scrolling furniture items.

It must show:
- selected items count
- estimated item total
- estimated Conference portion
- delivery fee line only when delivery is selected

This slice may continue using current estimate math until Slice 02 replaces it with catalog-driven Conference coverage rules.

### Delivery Toggle
If delivery is toggled on:
- reveal delivery address fields inline
- include $50 in displayed delivery fee line

## Data / Calculation Rules For This Slice
Until Slice 02 lands, preserve current estimate behavior unless a minimal adapter is required for the new UI.
Do not invent new pricing policy in this slice.
Use existing catalog values already available to the request form.

## Files Expected To Change
- public/templates/voucher-request-form.php
- public/js/voucher-request.js

May change if needed:
- includes/class-shortcodes.php
- includes/class-furniture-catalog.php

Avoid changing:
- clothing-only request behavior
- unrelated cashier files
- unrelated admin files

## Acceptance Criteria
- Furniture request flow no longer renders as one giant flat item list
- Category cards exist and are touch-friendly on mobile
- Categories expand inline
- Search works
- Sticky summary works
- Delivery toggle reveals address fields inline
- Clothing request flow still looks and behaves the same on the surface
- No page reloads introduced
- No new framework/toolchain introduced

## Testing Expectations
- Manual browser test on mobile-width viewport
- Manual browser test on desktop viewport
- Verify adding/removing items updates summary correctly
- Verify search finds items across categories
- Verify clearing search returns to category view
- Verify delivery toggle reveals/hides address fields correctly
- Verify existing furniture form submission still works
- Verify clothing path still works