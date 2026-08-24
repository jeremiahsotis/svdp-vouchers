# Slice PCL1 Bootstrap - Pilot Catalog Loading REST Origin Fix

## Execution context
Execute only Slice PCL1 in the `svdp-vouchers` WordPress plugin.

## Authoritative sources
Approved user plan, `AGENTS.md`, governance pack, PCL1 implementation brief, protected contracts, and canonical commands.

## Anti-drift rule
Do not alter catalog data, schema, request payload shape, pricing, delivery, accounting, analytics, permissions, route callbacks, or voucher lifecycle behavior.

## Protected surface guardrails
Only touch protected surfaces needed to emit same-origin REST URLs and improve catalog load diagnostics. Record protected-surface acceptance for each touched protected surface.

## Dependency rule
Do not add dependencies.

## Environment fidelity rule
Local checks validate code shape. Pilot runtime verification must be done after deployment on the two named pilot pages.

## Execution instructions
Add the same-origin REST helper, replace browser-emitted REST URLs, add catalog retry diagnostics, update acceptance/governance records, then run validation commands.

## Stop condition
Stop when browser-emitted REST URLs use the same-origin helper, catalog load failures include Retry and console diagnostics, and validation results are recorded.
