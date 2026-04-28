# Slice 6 - Data + Edge Cases Implementation Brief

## Purpose

Slice 6 is the quiet stability pass for the voucher request form. The visible feature work is already complete. This slice protects the system from confusing UI state, duplicate rendering, unstable quantity controls, and edge-case behavior that can make the form feel unreliable.

## Scope

Slice 6 includes:

1. Category state stability
2. Search and browse state integrity
3. Quantity control stability
4. Duplicate render prevention
5. Regression protection for selected items during filtering, clearing, and voucher-type switching

## Out of Scope

Do not implement or modify:

- Delivery tracking
- Delivery attempts
- RouteShyft dispatch logic
- Service area enforcement
- Neighbor delivery notification system
- Address provider changes
- Pricing logic
- Receipt generation
- Cashier completion logic
- Database schema unless a checkpoint explicitly requires it

## Current System State

The voucher request form already includes:

- Furniture catalog category cards
- Search behavior with singular/plural normalization
- Pricing clarity copy
- Conference maximum commitment language
- Address verification fields and provider selection behavior
- Redemption rule messaging
- Optional cashier photo uploads
- Neighbor receipt rule messaging

Slice 6 must preserve that work.

## Design Principle

The request form must never make the user wonder whether the catalog is broken.

When the user is browsing, category cards should behave like stable entry points.

When the user is searching, the catalog should behave like filtered results.

When search is cleared, the form must return cleanly to browse mode.

## Implementation Rules

- Treat `public/js/voucher-request.js` as the primary execution file.
- Treat `public/templates/voucher-request-form.php` as the shell contract.
- Do not duplicate template markup in JavaScript.
- Do not append category sections dynamically.
- Do not rebuild the category shell.
- Only replace section body contents when rendering catalog items.
- Preserve `state.selectedItems` through search, clear, category toggles, and voucher-type switching.
- Keep search mode and browse mode logically separate.
- Do not mutate `state.openCategories` during search mode.

## Required Validation Themes

Every Slice 6 checkpoint must validate:

- No duplicate category cards
- No duplicate category sections
- No duplicate item rows
- Search results appear once
- Clearing search restores browse mode
- Selected item quantities persist
- Quantity controls do not go negative
- Voucher submission still works