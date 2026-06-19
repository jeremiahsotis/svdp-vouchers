# ADR-0001: Release C Contract Binding and S5 Rebaseline

## Status

Accepted

## Date

2026-06-19

## Context

Slice C0 binds the Release C Product Contract into the repository before runtime implementation begins.

Release C introduces planned architectural changes for voucher request groups, Household Goods as a distinct voucher type, configurable delivery eligibility, group-level delivery snapshots, shared fulfillment rows, internal finalization notes, cashier status display labels, receipt and invoice behavior, migration safety, and backward compatibility.

S5 previously defined the Assisted Builder visual and interaction direction, but its single-voucher backend assumption is no longer valid for Release C.

## Decision

Release C is governed by `docs/product/release-c-product-contract.md`.

The S5 visual and interaction decisions remain binding, including the assisted builder layout, search visibility, category pill behavior, household and requestor separation, review flow, and mobile and desktop behavior.

The old S5 single-voucher backend assumption is superseded. Release C will use a request-group model that can create one, two, or three linked child vouchers for Clothing, Furniture, and Household Goods.

Slice C0 is documentation and contract binding only. Runtime implementation begins in later slices.

## Alternatives considered

1. Implement original S5 as written, then add Household Goods and multi-voucher support later.
   Rejected because it would deliberately build a temporary one-voucher backend flow that Release C already supersedes.

2. Treat Household Goods as Furniture.
   Rejected because Household Goods has a different catalog, quantity, pricing-estimate, fulfillment, and reporting model.

3. Keep delivery hardcoded to Furniture.
   Rejected because Release C requires delivery eligibility to be configurable by voucher type.

4. Leave status language as Active.
   Rejected for cashier-facing UI because READY TO REDEEM communicates the actionable state more clearly.

## Consequences

Future Release C slices must not contradict the Release C Product Contract.

Schema changes, delivery behavior, cashier fulfillment, Household Goods catalog management, status display, receipt and invoice behavior, and migration behavior must follow the bound contract.

If implementation discovers a conflict with the contract, work must stop for clarification rather than silently changing the product behavior.

## Contracts affected

- `contracts/protected-contracts.json`
- `contracts/protected-surfaces.json`
- `contracts/protected-surface-acceptance.json`
- Voucher identity and eligibility contracts
- Voucher type behavior
- Delivery configuration and snapshots
- Furniture and Household Goods fulfillment
- Receipt and invoice generation
- Audit and correction behavior
- Migration and backward compatibility
- Cashier-facing status display

## Docs affected

- `docs/product/release-c-product-contract.md`
- `docs/decisions/release-c-s5-rebaseline.md`
- `docs/architecture/concurrency-model.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/data-governance/migration-policy.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `planning/release-c-slice-map.md`
- `specs/active/slice-C0/implementation-brief.md`
- `specs/active/slice-C0/codepack.md`
- `specs/active/slice-C0/checkpoint-01.md`
- `specs/active/slice-C0/bootstrap.md`

## Superseded by

Not superseded.
