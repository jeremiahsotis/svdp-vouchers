# S4 - Admin Voucher Correction Audit Visibility

## Objective

Add full admin visibility into voucher correction audit history.

S4 makes the S2/S3 correction audit trail usable by administrators without adding new mutation behavior.

## Authoritative Prior Work

- specs/active/slice-G0/implementation-brief.md
- specs/active/slice-S1/implementation-brief.md
- specs/active/slice-S2/implementation-brief.md
- specs/active/slice-S2/codepack.md
- specs/active/slice-S3/implementation-brief.md
- specs/active/slice-S3/codepack.md
- contracts/protected-surfaces.json
- contracts/protected-contracts.json
- contracts/protected-surface-acceptance.json
- docs/governance/canonical-commands.json

## Scope

Add a read-only admin audit tab inside the existing SVdP Vouchers admin page.

In scope:

- Add new capability: `svdp_view_audit_log`
- Grant the capability to administrator/system admin roles through the existing permissions registration path
- Add a new admin tab inside the existing SVdP Vouchers admin page
- Display rows from `wp_svdp_voucher_corrections`
- Join correction rows to voucher, conference, and actor user context where available
- Show primary line as `human_summary`
- Show secondary metadata:
  - voucher ID
  - neighbor name
  - field changed
  - manager authority
  - logged-in actor
  - reason
  - timestamp
  - conference
- Add filters:
  - voucher ID
  - neighbor name
  - field changed
  - manager name
  - actor user
  - reason text
  - date from
  - date to
- Add pagination
- Escape all output
- Sanitize all filters

## Out of Scope

- CSV export
- Editing vouchers
- Reverting corrections
- Catalog audit
- Manager code display
- Manager code hash display
- Multi-user conflict handling
- Delivery dispatch, routing, driver assignment, delivery attempts, or RouteShyft behavior
- Any new correction/mutation endpoint

## Locked Decisions

1. Admin placement: new tab inside the existing SVdP Vouchers admin page.
2. Permission model: new capability `svdp_view_audit_log`.
3. CSV export: deferred.
4. Voucher link behavior: show voucher ID + neighbor name. Link only if there is already a stable admin/cashier URL.
5. Audit display: primary line is `human_summary`; secondary metadata is voucher, field, manager, actor, reason, timestamp.

## Protected Surfaces

- includes/class-permissions.php
- includes/class-admin.php
- includes/class-voucher.php
- includes/class-database.php
- admin/views/admin-page.php
- admin/views/tab-voucher-correction-audit.php
- svdp-vouchers.php, only if a new class require is needed

## Protected Contracts Impacted

- audit_logging
- voucher_correction_authority
- manager_override_authority
- voucher_identity
- voucher_expiration

## Design Direction

Use a plain WordPress admin tab rendered server-side.

Default implementation:

- Add `includes/class-voucher-correction-audit.php`
- Add `SVDP_Voucher_Correction_Audit::get_rows($args)`
- Add `SVDP_Voucher_Correction_Audit::get_filter_options()` only if needed
- Add `admin/views/tab-voucher-correction-audit.php`
- Add a new nav tab in `admin/views/admin-page.php`
- Add capability support in `includes/class-permissions.php`

Use GET query parameters for filters and pagination.

Do not expose raw manager codes or code hashes.

## Acceptance Criteria

S4 is complete when:

- A user with `svdp_view_audit_log` can see the Voucher Correction Audit tab.
- A user without `svdp_view_audit_log` cannot see audit content.
- Audit rows show `human_summary` as the primary line.
- Audit rows show secondary metadata.
- Filters work for voucher ID, neighbor name, field, manager, actor, reason, and date range.
- Pagination works.
- Manager code hashes are not queried or displayed.
- No mutation behavior is added.
- No catalog audit behavior is added.
- No delivery dispatch or RouteShyft behavior is introduced.
- PHP syntax validation passes.
- Required governance doc checks pass.
