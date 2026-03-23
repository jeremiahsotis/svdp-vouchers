You are implementing the next major upgrade of the existing WordPress plugin “SVdP Vouchers”.

Read these files first and treat them as the controlling implementation brief:
- docs/00-READ-ME-FIRST.md
- docs/01-LOCKED-DECISIONS.md
- docs/02-ARCHITECTURE-HANDOFF.md
- docs/03-DATABASE-SCHEMA.sql
- docs/04-REST-ENDPOINT-MAP.md
- docs/05-UI-SCREEN-MAP.md
- docs/06-IMPLEMENTATION-SLICES-CODEX.md
- docs/08-ACCEPTANCE-CHECKLIST.md

Implementation rules:
1. Do not drift from the locked decisions.
2. Do not introduce React, Vite, Webpack, or a SPA build pipeline.
3. Use HTMX + Alpine for the new cashier shell.
4. Keep the plugin in WordPress/PHP architecture.
5. Keep custom DB tables.
6. Preserve the current clothing voucher UX on the surface in both the public request form and cashier station.
7. Replace the cashier UI entirely, but keep clothing behavior familiar on the surface.
8. Do not add inventory tracking, stock counts, reservation logic, or a complex pricing engine.
9. Furniture and household goods are one voucher type.
10. Furniture voucher mutations must be protected by a new elevated capability.
11. Furniture voucher viewing must still be available to users with cashier view access.
12. Historical furniture voucher items must store catalog snapshots so catalog edits do not mutate history.
13. Furniture item completion must require actual price plus at least one photo.
14. Use plugin-managed uploads storage, not the general Media Library workflow.
15. Generate invoices from actual fulfilled prices, not estimated prices.
16. Generate statements from invoices, and exclude previously statemented invoices by `statement_id`.

Working style rules:
- Implement one slice at a time in the exact order listed in `docs/06-IMPLEMENTATION-SLICES-CODEX.md`.
- At the end of each slice, stop and summarize:
  - files changed
  - migration impact
  - open risks
  - regression checks completed
- Do not start the next slice until the current slice checkpoint is satisfied.
- Favor small, reviewable commits.
- Prefer additive refactors over destabilizing rewrites, except for the cashier UI shell which is intentionally being replaced.

Codebase-specific notes:
- Existing relevant files include `svdp-vouchers.php`, `includes/class-database.php`, `includes/class-voucher.php`, `public/templates/cashier-station.php`, `public/js/cashier-station.js`, `public/templates/voucher-request-form.php`, and `public/js/voucher-request.js`.
- The current plugin already contains REST routes and partial session keepalive logic. Reuse what is useful, but remove the stale-page/refresh dependency from the cashier experience.

Definition of success:
- Current clothing flows still look and behave the same on the surface.
- Furniture vouchers can be requested, fulfilled on mobile, completed, receipted, invoiced, and statemented.
- Cashiers do not need to refresh the page during normal work.
- Viewer vs elevated redeemer permissions work correctly.
