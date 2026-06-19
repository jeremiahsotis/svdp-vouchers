# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

# Bootstrap - Slice C0 - Release C Contract Binding and Slice Rebaseline

## Authoritative Execution Sources

1. `release-c-product-contract.md`
2. `C0-release-contract-and-planning/implementation-brief.md`
3. `C0-release-contract-and-planning/codepack.md`
4. `C0-release-contract-and-planning/checkpoint-01.md`
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

Implement Slice C0 only.

## Starting Procedure

1. Confirm repo root.
2. Run:

```bash
git status --short
git log --oneline -n 12
```

3. Read the authoritative sources above.
4. Confirm no unrelated work is present.
5. Implement only this slice.

## Hard Stop Rules

Stop if:

- The repo is not clean except for intended work.
- Existing code contradicts the product contract in a way that changes slice scope.
- You need to implement `C1-request-groups-and-delivery-foundation` to finish this slice.
- A migration would rewrite or reinterpret historical voucher records.
- A change would introduce out-of-scope delivery logistics.
- A change would add unrestricted notes.
- You cannot preserve legacy Clothing/Furniture behavior.

## Required Implementation Behavior

- Release C contract exists in repo and is referenced by planning docs.
- S5 is explicitly rebaselined: design remains binding, single-voucher backend assumption is superseded.
- C1-C5 dependency order is documented.
- Protected contracts identify request group creation, Household Goods catalog, delivery snapshots, fulfillment entries, finalization notes, receipt/invoice behavior, and cashier card status.
- No PHP, JavaScript, template, or schema runtime behavior changes occur.

## Completion Procedure

1. Run validation commands.
2. Complete `checkpoint-01.md`.
3. Update relevant governance, data evolution, and change-impact docs.
4. Summarize changed files, acceptance results, and any variance.
5. Do not proceed to `C1-request-groups-and-delivery-foundation` until the checkpoint is accepted.
