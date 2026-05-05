# S3 — Cashier Correction Modal + Recent Audit Display

## Objective

Add a cashier-facing correction workflow for existing vouchers using the S2 correction endpoint and audit model.

S3 makes voucher corrections operationally usable without changing the S2 backend contract.

## Authoritative Prior Work

- specs/active/slice-S1/implementation-brief.md
- specs/active/slice-S2/implementation-brief.md
- specs/active/slice-S2/codepack.md
- specs/active/slice-S2/checkpoint.md

## Scope

Add cashier UI support for controlled voucher corrections.

In scope:

- Add "Correct Voucher" action to cashier voucher detail views.
- Add correction form/modal/panel in the active cashier shell.
- Allow editing only S2-approved correction fields:
  - adults
  - children
  - dob
  - status
  - voucher_created_date
  - delivery_address_line_1
  - delivery_address_line_2
  - delivery_city
  - delivery_state
  - delivery_zip
- Require manager name, 4-character override code, and reason before submitting correction.
- Validate manager code through existing `/svdp/v1/managers/validate`.
- Submit corrections to `/svdp/v1/vouchers/{id}/correct`.
- Refresh voucher detail after successful correction.
- Show newest 2 voucher correction `human_summary` entries on voucher detail.

## Out of Scope

- Full admin audit explorer.
- Catalog edit audit.
- Multi-user conflict UI.
- Delivery dispatch, delivery attempts, RouteShyft behavior.
- Editing denied vouchers unless already permitted by S2 endpoint.
- Bulk correction.
- Direct database editing.

## Protected Surfaces

- public/js/cashier-shell.js
- public/templates/cashier/partials/voucher-detail.php
- public/templates/cashier/partials/voucher-detail-furniture.php
- includes/class-voucher.php
- svdp-vouchers.php

## Protected Contracts Impacted

- voucher_identity
- voucher_expiration
- voucher_correction_authority
- manager_override_authority
- audit_logging
- delivery_address_verification

## Design Decisions

1. Use the active cashier shell, not legacy `cashier-station.js`.
2. Use one correction workflow for clothing and furniture vouchers.
3. Display recent audit history directly in voucher detail.
4. Keep full audit search/reporting deferred to S4.
5. Use S2 endpoint as the source of truth for authority and field validation.

## Acceptance Criteria

S3 is complete when:

- Cashier user can open correction UI from voucher detail.
- Correction UI pre-fills current voucher values.
- User can submit non-protected correction with reason and manager validation flow.
- Protected corrections require valid manager authority.
- Invalid manager code blocks submission.
- Successful correction refreshes voucher detail.
- Voucher detail shows newest 2 correction summaries.
- No delivery routing/dispatch scope is introduced.
- PHP syntax passes.
- Governance checks pass or known recon-file placeholder noise is documented.