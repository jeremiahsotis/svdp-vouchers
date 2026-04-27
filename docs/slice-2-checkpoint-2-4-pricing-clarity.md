# Slice 2 - Checkpoint 2.4: Pricing Clarity Systemization

## Objective

Make furniture voucher pricing language consistent across the request form summary and approval surface while keeping all pricing, catalog, API, and submission behavior unchanged.

## Render Classification

The pricing render path is hybrid:

- PHP renders static request-form containers, placeholder labels, pricing help text, and the approval modal shell.
- JavaScript calculates and renders live selected-item totals, live Conference commitment totals, delivery fee display, and approval totals.

For this checkpoint, JavaScript is the live pricing display source and PHP is the static copy/container source.

## Scope

Included:

- Replace ambiguous pricing labels and visible estimate-style copy.
- Use "Maximum Conference commitment" for the live Conference total concept.
- Show the pricing explanation near the live request summary and approval modal.
- Keep the same explanation and pricing rule across summary and approval surfaces.

Excluded:

- Pricing calculations.
- Catalog schema or catalog logic.
- Backend REST API shape.
- Voucher submission payloads.
- Database field names.
- Cashier redemption behavior.

## Locked User-Facing Language

Label:

> Maximum Conference commitment

Explanation:

> The amount shown is the maximum Conference cost for this voucher. Final fulfilled pricing may be lower based on the items chosen.

Pricing rule:

> Most items are calculated at 50% of the retail prices shown. Mattress/Frame Bundles use the exact price shown.

## Placement Requirements

Request form summary:

- Shows selected item retail maximum.
- Shows Maximum Conference commitment.
- Shows delivery fee when selected.
- Shows total maximum Conference commitment.
- Places the locked explanation and pricing rule near the live totals.

Approval modal:

- Shows selected item retail maximum.
- Shows Maximum Conference commitment.
- Shows delivery fee.
- Shows total maximum Conference commitment.
- Repeats the locked explanation and pricing rule before approval.

## Acceptance Criteria

- Request form summary uses "Maximum Conference commitment".
- Approval modal uses "Maximum Conference commitment".
- Pricing rule appears near the live totals and approval action.
- No neighbor-payment copy is introduced.
- No pricing calculations are changed.
- No catalog data or logic is changed.
- No submission payload is changed.

## Done When

- User-facing pricing language is consistent.
- Maximum-cost meaning is clear.
- Approval screen reinforces the same pricing model.
- All changes are presentation-only.
