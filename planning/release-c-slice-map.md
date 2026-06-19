# Release C Slice Map

## Authority

Release C implementation is governed by [Release C Product Contract](../docs/product/release-c-product-contract.md). Slice briefs must not contradict that contract. When an implementation detail conflicts with the product contract, stop and ask for direction before coding.

## C0 Baseline

C0 binds the contract into the repository, records the S5 rebaseline, updates protected planning contracts, and establishes dependency order. It intentionally makes no runtime, schema, template, PHP, or JavaScript behavior changes.

## Dependency Order

1. C0 - Release Contract Binding and Slice Rebaseline
2. C1 - Request Groups and Delivery Foundation
3. C2 - Household Goods Catalog, Limits, and Administration
4. C3 - Assisted Builder Multi-Voucher Submission
5. C4 - Shared Furniture and Household Goods Fulfillment
6. C5 - Receipts, Invoices, Cashier Status, and Release Regression

## Dependency Rules

- C1 must establish request group creation, child-voucher linkage, delivery snapshots, and historical compatibility foundations before any builder or fulfillment feature depends on them.
- C2 depends on C1 and introduces Household Goods catalog, limits, administration, and configuration audit without reinterpreting legacy `household` records.
- C3 depends on C1 and C2 because the public builder must submit atomic request groups and use the configured Household Goods and delivery-capability data.
- C4 depends on C1 through C3 because cashier fulfillment must operate on issued child vouchers and snapshotted requested lines.
- C5 depends on C1 through C4 because receipt, invoice, status display, and full regression acceptance require the complete request-group, catalog, delivery, and fulfillment model.

## Cross-Slice Hard Stops

- Do not reinterpret legacy `household` records as `household_goods`.
- Do not add unrestricted Vincentian Notes or item-level fulfillment Notes.
- Do not duplicate delivery fees or delivery records across child vouchers in the same request group.
- Do not introduce POS integration, live inventory, SKU tracking, barcode scanning, dispatch, routing, driver scheduling, delivery attempts, or RouteShyft behavior.
- Do not implement a later slice to complete an earlier slice unless the checkpoint has been accepted and the next slice is explicitly authorized.
