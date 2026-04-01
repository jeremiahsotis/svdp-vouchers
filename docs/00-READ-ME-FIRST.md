# SVdP Vouchers Furniture Expansion - Developer Pack

This pack is the locked implementation handoff for the next major upgrade of the SVdP Vouchers WordPress plugin.

## Primary goal
Add **Furniture / Household Goods Vouchers** to the existing plugin without changing the surface behavior of the current clothing voucher workflows.

## Non-negotiables
- The current clothing voucher request form must continue to **look and function the same on the surface**.
- The current clothing redemption behavior in the cashier station must continue to **look and function the same on the surface**.
- The cashier experience must become a **single persistent interface** with **no full page refreshes**.
- The cashier page must not enter a stale or expired state during normal use.
- Logged-in cashier users must remain logged in unless they explicitly log out.
- Furniture and household goods are one voucher type.
- Furniture voucher redemption and editing require a new elevated capability.
- Furniture voucher viewing does **not** require the elevated capability if the user already has cashier view access.
- No real-time inventory. No stock tracking. No complex pricing engine.
- Mobile-first is required for furniture fulfillment.
- Usability beats cleverness. Minimize taps, minimize decisions, minimize hidden state.

## Existing codebase reality
The current plugin is a server-rendered WordPress plugin with REST routes already present and a large jQuery cashier script. The current cashier station has partial session keepalive work but still falls back to page-expiration behavior. This upgrade replaces that cashier interaction model.

## Required implementation direction
- Use **HTMX + Alpine.js** for the new cashier shell.
- Keep the plugin in its WordPress/PHP architecture.
- Keep custom database tables.
- Do not introduce React.
- Do not turn the plugin into a SPA build-pipeline project.
- Do not patch the existing `public/js/cashier-station.js` into a bigger jQuery app.

## Files in this pack
- `01-LOCKED-DECISIONS.md`
- `02-ARCHITECTURE-HANDOFF.md`
- `03-DATABASE-SCHEMA.sql`
- `04-REST-ENDPOINT-MAP.md`
- `05-UI-SCREEN-MAP.md`
- `06-IMPLEMENTATION-SLICES-CODEX.md`
- `07-CODEX-BOOTSTRAP-PROMPT.md`
- `08-ACCEPTANCE-CHECKLIST.md`

## Definition of done
The work is done only when:
1. Clothing vouchers still behave the same on the surface.
2. Furniture vouchers can be requested, viewed, fulfilled, completed, receipted, invoiced, and statemented.
3. Cashiers do not need to refresh the page during normal operations.
4. Furniture fulfillment is practical on a phone while walking the store.
5. Permissions are enforced server-side, not just hidden in the UI.
