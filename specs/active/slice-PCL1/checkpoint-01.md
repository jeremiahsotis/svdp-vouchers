# Slice PCL1 Checkpoint 01 - Pilot Catalog Loading REST Origin Fix

## Objective
Verify that browser-facing REST URLs no longer hard-code WordPress' canonical host and that catalog load failures are easier to diagnose.

## Files changed
`svdp-vouchers.php`, `includes/class-admin.php`, `public/js/voucher-request.js`, cashier HTMX templates, `scripts/check_no_placeholders.py`, `contracts/protected-surface-acceptance.json`, and the PCL1 execution packet.

## Code changes
Added `svdp_vouchers_same_origin_rest_url()` and switched public request, cashier shell, admin REST config, cashier list, cashier card, and cashier detail HTMX URLs to it. Added Furniture and Household Goods catalog retry controls and console diagnostics on AJAX failure.

Hardened the canonical placeholder checker so it scans source-like text files and skips generated render artifacts under `tmp/pdfs/**`.

## Data changes
No schema migration, catalog data edit, or persistent data mutation.

## Contract changes
REST payload contracts are unchanged. Browser-facing REST base URLs are now same-origin relative.

## Security / policy changes
No capability, nonce, authentication, or permission callback changes.

## Protected surface verification
PASS - touched protected surfaces are named in the PCL1 implementation brief and acceptance registry.

## AST contract validation
Not registered for this repo's canonical commands.

## Guardrail auto-detection
No new guardrail required; targeted REST URL search confirms browser-facing REST URLs use the same-origin helper.

## Anti-drift enforcement
PASS - no schema, catalog data, pricing, delivery, accounting, analytics, route callback, permission, or voucher lifecycle behavior changed.

## Observability enforcement
PASS - catalog AJAX failures now write URL/status/response diagnostics to `console.warn` and expose a Retry control to the user.

## Logging / audit
No server audit event is required because the change does not introduce or modify protected mutation behavior.

## Environment fidelity validation
Local checks completed. Pilot page-source and browser catalog rendering verification remain post-deployment checks.

## Dependency validation
No dependencies added.

## Testing
PASS - `git status --short`, full PHP syntax over `includes public admin`, `php -l svdp-vouchers.php`, `node --check public/js/voucher-request.js`, `python3 scripts/check_no_placeholders.py`, targeted REST URL search, scoped changed-file placeholder search, and `git diff --check`.

## Verification block
`find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l`: PASS. `php -l svdp-vouchers.php`: PASS. `node --check public/js/voucher-request.js`: PASS. `python3 scripts/check_no_placeholders.py`: PASS. `python3 -m py_compile scripts/*.py`: PASS. Targeted REST URL search: PASS with only route registration, helper, and intended same-origin call sites. Scoped changed-file placeholder search: PASS with no matches. `git diff --check`: PASS.

## Editorial check
No placeholder markers in changed source or governance files.

## Stop condition
Complete pending deployment and pilot-browser verification.
