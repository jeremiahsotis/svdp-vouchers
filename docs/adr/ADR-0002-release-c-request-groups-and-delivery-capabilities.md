# ADR-0002: Release C Request Groups and Delivery Capabilities

## Status

Accepted

## Date

2026-06-19

## Context

Slice C1 implements the first runtime foundation for Release C. The Release C Product Contract requires request groups, one to three linked child vouchers, a distinct `household_goods` root voucher type, voucher-type delivery capability settings, and group-level delivery snapshots.

Before C1, voucher creation was primarily standalone. Furniture and legacy `household` behavior were historically tied together. Delivery behavior was also effectively Furniture-centered.

## Decision

C1 introduces backend request-group foundation tables and services.

C1 adds:

- request-group storage;
- nullable `request_group_id` linkage on voucher rows;
- database uniqueness protection for one voucher of each type per request group;
- voucher-type delivery capability storage;
- default delivery capability settings for Clothing, Furniture, and Household Goods;
- group-level delivery snapshot storage;
- backend-only atomic request-group creation with rollback behavior.

C1 recognizes `household_goods` as a new Release C root voucher type for grouped requests.

Legacy `household` remains Furniture history and must not be reinterpreted as `household_goods`.

C1 does not expose the three-voucher public Assisted Builder UI and does not implement the Household Goods catalog or shared cashier fulfillment workspace.

## Consequences

Future Release C slices can build on request groups without changing historical standalone voucher behavior.

Delivery eligibility is no longer hardcoded only to Furniture. It is read from voucher-type capability settings and snapshotted for future request groups.

Existing vouchers remain valid because `request_group_id` is nullable.

The request-group service must use transaction behavior so partial groups are not committed if child voucher creation or delivery snapshot creation fails.

## Protected Surfaces Affected

- `includes/class-database.php`
- `includes/class-settings.php`
- `includes/class-voucher.php`
- `includes/class-voucher-request-group.php`
- `includes/class-voucher-type-settings.php`
- `svdp-vouchers.php`
- `contracts/protected-contracts.json`
- `contracts/protected-surfaces.json`
- `contracts/protected-surface-acceptance.json`
- `docs/architecture/concurrency-model.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `specs/active/slice-C1/implementation-brief.md`
- `specs/active/slice-C1/codepack.md`
- `specs/active/slice-C1/checkpoint-01.md`
- `specs/active/slice-C1/bootstrap.md`

## Superseded by

Not superseded.

## Alternatives considered

1. Keep all voucher creation standalone until the Assisted Builder UI is implemented.
   Rejected because later Release C slices need a stable backend request-group foundation before the UI can submit multi-voucher requests safely.

2. Store delivery independently on each child voucher.
   Rejected because the Release C Product Contract requires one group-level delivery selection, one delivery fee, and one delivery attempt per request group.

3. Treat `household_goods` as another Furniture value.
   Rejected because Household Goods is a distinct Release C root voucher type and legacy `household` must remain Furniture history.

4. Seed delivery capability later in the UI slice.
   Rejected because delivery eligibility is a backend capability and must be available before later request-building work depends on it.

## Contracts affected

- `contracts/protected-contracts.json`
- `contracts/protected-surfaces.json`
- `contracts/protected-surface-acceptance.json`
- Release C request-group creation contract
- Release C delivery snapshot contract
- Release C Household Goods root-type contract
- Backward compatibility contract for standalone vouchers and legacy `household` records

## Docs affected

- `docs/adr/ADR-0002-release-c-request-groups-and-delivery-capabilities.md`
- `docs/architecture/concurrency-model.md`
- `docs/data-governance/backward-compatibility.md`
- `docs/data-governance/data-evolution-log.md`
- `docs/roadmap/v2/roadmap-state.md`
- `docs/security/access-audit-model.md`
- `docs/state/change-impact-log.md`
- `specs/active/slice-C1/bootstrap.md`
- `specs/active/slice-C1/checkpoint-01.md`
- `specs/active/slice-C1/codepack.md`
- `specs/active/slice-C1/implementation-brief.md`
