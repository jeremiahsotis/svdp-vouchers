# S2 — Voucher Corrections with Field-Level Audit

## Objective

Introduce controlled voucher corrections that:

- preserve original values
- log before/after per field
- require authority for sensitive changes
- remain compliant with protected contracts

## Scope (Locked)

Allowed fields:

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

## Non-Scope

- No UI
- No bulk edits
- No deletion
- No mutation of historical audit rows

## Protected Contracts Impacted

- voucher_identity
- voucher_expiration
- voucher_correction_authority
- audit_logging

## Design Decisions

1. Field-level audit (not row snapshot)
2. Append-only audit table
3. Authority required for:
   - dob
   - voucher_created_date
   - status

## Risks

- Partial audit if loop breaks → mitigated by pre-write audit
- Data drift if bypassed → endpoint restricted to admin

## Rollback

- Disable endpoint
- Leave table intact (non-destructive)

## Done When

- Table exists
- Endpoint works
- Audit rows created correctly
- Authority enforcement verified
