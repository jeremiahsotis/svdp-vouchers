# Slice HG1 Bootstrap — Household Goods Total Requested Quantity Limit Fix

## Execution context
Execute only Slice HG1 in the `svdp-vouchers` WordPress plugin.

## Authoritative sources
The approved user plan, `AGENTS.md`, governance pack, this implementation brief, protected contracts, and canonical commands.

## Anti-drift rule
Do not add unrelated request, pricing, admin, fulfillment, cashier, accounting, analytics, or schema behavior.

## Protected surface guardrails
Only change protected surfaces named in the HG1 implementation brief and record acceptance.

## Dependency rule
Do not add dependencies.

## Environment fidelity rule
Use local DDEV settings of Maximum Selected Categories `10` and Maximum Total Requested Quantity `15` for targeted verification, then restore prior local values if changed for tests.

## Execution instructions
Update governance records first, then browser quantity enforcement, then server validation message/ordering, then validation commands.

## Stop condition
Stop when the browser and server paths enforce the voucher-wide Household Goods quantity max and automated checks have been recorded.
