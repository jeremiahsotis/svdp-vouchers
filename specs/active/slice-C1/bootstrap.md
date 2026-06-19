# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Bootstrap - Slice C1 - Request Groups, Multi-Voucher Foundation, and Delivery Capabilities

## Authoritative Execution Sources

1. `release-c-product-contract.md`
2. `C1-request-groups-and-delivery-foundation/implementation-brief.md`
3. `C1-request-groups-and-delivery-foundation/codepack.md`
4. `C1-request-groups-and-delivery-foundation/checkpoint-01.md`
5. Current repo `AGENTS.md`
6. `MASTER-STANDARD.md`
7. `PROJECT-PROFILE.md`
8. `contracts/protected-surfaces.json`
9. `contracts/protected-contracts.json`
10. `contracts/protected-surface-acceptance.json`
11. `docs/governance/canonical-commands.json`
12. `docs/data-governance/migration-policy.md`
13. `docs/data-governance/backward-compatibility.md`
14. `docs/architecture/concurrency-model.md`
15. `docs/security/access-audit-model.md`

## Task

Implement Slice C1 only.

## Starting Procedure

1. Confirm repo root.
2. Run:

```bash
git status --short
git log --oneline -n 12
```

1. Read the authoritative sources above.
2. Confirm no unrelated work is present.
3. Implement only this slice.

## Hard Stop Rules

Stop if:

- The repo is not clean except for intended work.
- Existing code contradicts the product contract in a way that changes slice scope.
- You need to implement `C2-household-goods-catalog-and-limits` to finish this slice.
- A migration would rewrite or reinterpret historical voucher records.
- A change would introduce out-of-scope delivery logistics.
- A change would add unrestricted notes.
- You cannot preserve legacy Clothing/Furniture behavior.

## Required Implementation Behavior

- Request groups can be created with one, two, or three child vouchers in backend tests/manual harness.
- Database prevents duplicate voucher types within one request group.
- Legacy vouchers without request_group_id still display and function.
- Delivery eligibility is read from configuration, not hardcoded to furniture.
- Delivery is stored once per request group and snapshots fee, address, eligible voucher types, and selected voucher types.
- Changing delivery settings affects future requests only.
- Creation failure in any child voucher prevents the whole group from being committed.

## Completion Procedure

1. Run validation commands.
2. Complete `checkpoint-01.md`.
3. Update relevant governance, data evolution, and change-impact docs.
4. Summarize changed files, acceptance results, and any variance.
5. Do not proceed to `C2-household-goods-catalog-and-limits` until the checkpoint is accepted.
