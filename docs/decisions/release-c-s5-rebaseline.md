# Release C S5 Rebaseline

## Status

Accepted for Release C planning.

## Date

2026-06-19

## Context

Release C is governed by [Release C Product Contract](../product/release-c-product-contract.md). The contract keeps the locked S5 Assisted Builder visual and interaction decisions, but changes the backend model from a single voucher submission to a request group with one to three independently redeemable child vouchers.

## Decision

S5 remains binding for the Assisted Builder design language:

- Option 2 Assisted Builder visual system.
- Large readable voucher and item cards.
- No cart, checkout, retail-shopping, or order language.
- Strong visible search fields for Furniture and Household Goods.
- Dynamic filter-pill overflow controls.
- Persistent request summary behavior.
- Mobile-friendly step navigation and review behavior.

S5's former single-voucher backend assumption is superseded by the Release C Product Contract:

- One submission creates one Voucher Request Group.
- The group may contain Clothing, Furniture, Household Goods, or any valid combination of those types.
- Each child voucher remains independently redeemable, expirable, auditable, and correctable.
- Delivery is group-level and appears once when selected voucher types are delivery eligible.
- Household Goods is a distinct root voucher type, not a Furniture category and not the legacy `household` value.

## Implementation Boundary

This rebaseline is documentation-only in Slice C0. It does not change PHP, JavaScript, templates, schema, REST behavior, cashier behavior, or the public request form.

## Superseded Assumption

The superseded assumption is only the S5 backend simplification that one builder submission creates one voucher. No S5 visual or interaction requirement is weakened by this rebaseline.
