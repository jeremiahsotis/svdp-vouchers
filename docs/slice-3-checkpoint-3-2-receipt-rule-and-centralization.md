# Slice 3 — Checkpoint 3.2: Receipt Rule + Centralized Rule Renderer

## Objective

Ensure voucher redemption rules are:
1. Present on the furniture receipt
2. Defined in a single shared source to prevent drift

## Canonical Rule Text (LOCKED)

This voucher expires 30 days after issuance. It must be redeemed in one visit; remaining items cannot be saved for a later visit.

## Scope

Included:
- add rule to furniture receipt (top section)
- create shared rule helper
- update request form + approval modal to use helper

Excluded:
- multilingual
- neighbor voucher delivery system
- styling overhaul beyond basic prominence

## Acceptance Criteria

- receipt shows rule near top (not footer)
- request form uses shared helper
- approval modal uses shared helper
- only ONE canonical rule definition exists in codebase