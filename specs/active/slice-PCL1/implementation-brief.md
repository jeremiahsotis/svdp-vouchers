# Slice PCL1 - Pilot Catalog Loading REST Origin Fix

## Objective
Restore Furniture and Household Goods catalog loading on the pilot voucher pages by keeping browser REST requests on the same public origin that rendered the page.

## Scope / explicit non-scope
Includes browser-emitted REST base URLs, cashier HTMX REST URLs, and catalog-load retry diagnostics. Excludes schema changes, catalog data edits, route callback changes, voucher creation logic, permissions, cashier mutation behavior, admin AJAX URLs, deployment, and data repair.

## Architectural context
The pilot serves voucher request pages at `voucher.svdpfortwayne.org` while WordPress can still report the canonical REST URL as `svdpfortwayne.org`. Browser AJAX then asks the wrong host for `svdp/v1` routes and receives `rest_no_route`. Root-relative REST URLs preserve the active page origin without changing route registration or REST permissions.

## Affected systems
Public request form JavaScript, plugin frontend script localization, admin REST localization, and cashier HTMX detail/list refresh templates.

## Data model impact
No schema migration, no data mutation, no catalog seed or catalog row changes.

## Contracts
Public `svdp/v1/catalog-items` and `svdp/v1/household-goods/catalog` response shapes remain unchanged. `svdpVouchers.restUrl`, `svdpCashierShell.restUrl`, and `svdpAdmin.restUrl` become same-origin relative bases.

## Execution flow
Add a shared `svdp_vouchers_same_origin_rest_url()` helper around `rest_url()` and `wp_make_link_relative()`. Use it anywhere this plugin emits REST URLs into browser JavaScript or HTMX attributes. Keep server-only route registration unchanged. Add catalog AJAX retry buttons and console diagnostics for future load failures.

## State transitions
Catalog loading state remains `loading -> loaded` on success. On failure, the affected catalog stays unloaded, search remains disabled, and Retry restarts the existing load function.

## Failure modes
If REST loading fails again, the public form shows the same friendly error plus Retry, and the browser console records label, URL, status, status text, error, and response text.

## Idempotency
The helper and URL replacements are deterministic and safe across pilot, production, and merged domains. No persistent migration or scheduled process is introduced.

## Security / policy / permissions
No route permission callbacks, capabilities, nonces, or protected mutation authority change. Existing REST nonces remain attached to same-origin requests.

## Provider scope decision
No external provider integration is added or changed.

## Protected surface impact
Touches protected bootstrap, admin routing/localization, public request JavaScript, and cashier detail templates only for URL-origin behavior and catalog diagnostics.

## Protected contract impact
Preserves Furniture and Household Goods catalog payload, request-form stability, pricing display, request-group submission, and cashier fulfillment contracts.

## Guardrail registration impact
No new guardrail required. Existing protected-surface acceptance entries document the hotfix.

## Anti-drift mapping
Only REST URL origin handling and catalog-load diagnostics change. No catalog contents, pricing calculations, delivery behavior, accounting, analytics, or voucher lifecycle behavior changes.

## Observability
Adds client-side console diagnostics for catalog load failures. No audit log is required because no protected mutation occurs.

## Environment fidelity
Local static validation can prove syntax and URL generation call sites. Pilot browser verification after deployment must confirm page source emits `/wp-json/` and both catalogs render on the two pilot pages.

## Testing requirements
Run git status, PHP syntax checks, placeholder checks, scoped placeholder search on changed files, `git diff --check`, and targeted REST URL search. After deployment, verify both pilot catalog endpoints and both pilot pages in-browser.

## Version context
No schema version or plugin version change.

## Dependencies
No new dependencies.

## Finalization pass required before completion
Confirm no remaining browser-facing `rest_url()` use bypasses the same-origin helper and record validation results in the checkpoint.

## Non-goals
No deployment, database repair, catalog redesign, admin AJAX same-origin conversion, or live voucher submission.

## Future work
Consider converting browser-facing `admin_url('admin-ajax.php')` values to a same-origin helper in a separate scoped slice if pilot admin AJAX shows the same host split.

## Rollback
Revert the helper and call-site replacements. No data cleanup is required.
