# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Bootstrap - Slice C4 - Three-Voucher Assisted Builder UI and Request Submission

## Authoritative Execution Sources

1. `release-c-product-contract.md`
1. `C4-three-voucher-assisted-builder-ui/implementation-brief.md`
1. `C4-three-voucher-assisted-builder-ui/codepack.md`
1. `C4-three-voucher-assisted-builder-ui/checkpoint-01.md`
1. Current repo `AGENTS.md`
1. `MASTER-STANDARD.md`
1. `PROJECT-PROFILE.md`
1. `contracts/protected-surfaces.json`
1. `contracts/protected-contracts.json`
1. `contracts/protected-surface-acceptance.json`
1. `docs/governance/canonical-commands.json`
1. `docs/data-governance/migration-policy.md`
1. `docs/data-governance/backward-compatibility.md`
1. `docs/architecture/concurrency-model.md`
1. `docs/security/access-audit-model.md`

## Task

Implement Slice C4 only.

## Starting Procedure

1. Confirm repo root.
1. Run:

```bash
git status --short
git log --oneline -n 12
```

1. Read the authoritative sources above.
1. Confirm no unrelated work is present.
1. Implement only this slice.

## Hard Stop Rules

Stop if:

- The repo is not clean except for intended work.
- Existing code contradicts the product contract in a way that changes slice scope.
- You need to implement `C5-documents-regression-and-release-readiness` to finish this slice.
- A migration would rewrite or reinterpret historical voucher records.
- A change would introduce out-of-scope delivery logistics.
- A change would add unrestricted notes.
- You cannot preserve legacy Clothing/Furniture behavior.

## Required Implementation Behavior

- All seven voucher combinations render correct dynamic steps.
- No step numbering is hardcoded.
- Deselecting a voucher type with entered data requires confirmation and clears that type's data.
- Furniture and Household Goods search fields are high contrast and visibly labeled.
- Category/browse-group pills show dynamic overflow arrows correctly.
- Household Goods card values do not expose unit, retail, shelf, or spendable amounts.
- Review shows Estimated Conference / Partner Cost only.
- Delivery appears once only when selected types are delivery eligible.
- Delivery review shows Not selected or the configured fee, never $0.00.
- Stock message appears on Review and Confirmation when Furniture or Household Goods is selected.
- Submission creates one request group and the correct child vouchers atomically.

## Completion Procedure

1. Run validation commands.
1. Complete `checkpoint-01.md`.
1. Update relevant governance, data evolution, and change-impact docs.
1. Summarize changed files, acceptance results, and any variance.
1. Do not proceed to `C5-documents-regression-and-release-readiness` until the checkpoint is accepted.
