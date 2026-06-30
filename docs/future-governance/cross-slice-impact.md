# Cross-Slice Impact

## Slice C4 - Three-Voucher Assisted Builder UI and Request Submission

Impacts C5 release-readiness verification:

- C5 should regression-test all seven Assisted Builder voucher combinations against the new `POST /svdp/v1/vouchers/request-group` endpoint.
- C5 should verify that new grouped Furniture and Household Goods vouchers open in the C3 shared fulfillment workspace because C4 now writes requested-line snapshots at issuance.
- C5 should verify receipt and invoice behavior for grouped Furniture plus Household Goods requests, especially that the group-level delivery fee is billed once.
- C5 should verify that existing standalone Clothing/Furniture vouchers and legacy `household` values still follow historical behavior.
- C5 should verify that existing non-store organizations saved with the old Clothing plus Furniture default expose Household Goods for future public requests without rewriting historical configuration records.
