# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Bootstrap - Slice C2 - Household Goods Catalog, Limits, and Administration

## Authoritative Execution Sources

1. `release-c-product-contract.md`
2. `C2-household-goods-catalog-and-limits/implementation-brief.md`
3. `C2-household-goods-catalog-and-limits/codepack.md`
4. `C2-household-goods-catalog-and-limits/checkpoint-01.md`
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

Implement Slice C2 only.

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
- You need to implement `C3-shared-fulfillment-and-cashier-status` to finish this slice.
- A migration would rewrite or reinterpret historical voucher records.
- A change would introduce out-of-scope delivery logistics.
- A change would add unrestricted notes.
- You cannot preserve legacy Clothing/Furniture behavior.

## Required Implementation Behavior

- Authorized admin can add, edit, archive, restore, and reorder browse groups.
- Authorized admin can add, edit, archive, restore, and reorder Household Goods categories.
- Catalog categories have estimated Conference / Partner cost per unit, quantity max, active/archive, guidance, and sort order.
- Archived categories do not appear for new requests but remain visible historically.
- 0 means no limit for category and voucher-wide limits.
- Negative cost and quantity values are rejected.
- Duplicate active category names within the same browse group are rejected.
- Configuration changes are audited and do not alter issued snapshots.

## Completion Procedure

1. Run validation commands.
2. Complete `checkpoint-01.md`.
3. Update relevant governance, data evolution, and change-impact docs.
4. Summarize changed files, acceptance results, and any variance.
5. Do not proceed to `C3-shared-fulfillment-and-cashier-status` until the checkpoint is accepted.
