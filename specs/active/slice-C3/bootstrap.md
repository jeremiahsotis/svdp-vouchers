# SVdP Voucher System - Release C

**Authority:** `release-c-product-contract.md` is the product contract for Release C.  
**Rule:** Do not implement behavior that contradicts the contract. When code and contract appear to conflict, stop and ask for direction.  
**Repository:** WordPress plugin, `svdp-vouchers`.  
**Execution model:** Slice-based implementation with explicit checkpoints before proceeding.

## Bootstrap - Slice C3 - Shared Fulfillment Workspace, Voucher Finalization, and Cashier Card Status

## Authoritative Execution Sources

1. `release-c-product-contract.md`
1. `C3-shared-fulfillment-and-cashier-status/implementation-brief.md`
1. `C3-shared-fulfillment-and-cashier-status/codepack.md`
1. `C3-shared-fulfillment-and-cashier-status/checkpoint-01.md`
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

Implement Slice C3 only.

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
- You need to implement `C4-three-voucher-assisted-builder-ui` to finish this slice.
- A migration would rewrite or reinterpret historical voucher records.
- A change would introduce out-of-scope delivery logistics.
- A change would add unrestricted notes.
- You cannot preserve legacy Clothing/Furniture behavior.

## Required Implementation Behavior

- Cashier cards show READY TO REDEEM, REDEEMED, and EXPIRED as large, high-contrast labels with non-color cues.
- Redeemed vouchers remain REDEEMED after expiration date passes.
- Expired unredeemed vouchers cannot be redeemed through ordinary workflow.
- Furniture and Household Goods fulfillment can be completed on one screen.
- Cashier can add multiple price rows inline without modal/page transitions.
- Line totals calculate automatically.
- Unavailable quantity requires structured reason.
- Save Progress does not redeem voucher or generate final documents.
- Finalize is blocked until all requested lines are resolved.
- Internal Finalization Note is voucher-level only and hidden from receipt/invoice.
- Legacy Furniture vouchers retain historical behavior.

## Completion Procedure

1. Run validation commands.
1. Complete `checkpoint-01.md`.
1. Update relevant governance, data evolution, and change-impact docs.
1. Summarize changed files, acceptance results, and any variance.
1. Do not proceed to `C4-three-voucher-assisted-builder-ui` until the checkpoint is accepted.
