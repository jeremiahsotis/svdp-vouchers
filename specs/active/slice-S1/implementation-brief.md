# Slice S1 — Override Authority Foundation

## Purpose
Establish a secure, auditable override authority system independent of logged-in user identity.

## Problem
Current override system:
- uses 6-digit numeric codes
- lacks audit trail for validation attempts
- does not support manual code assignment
- does not enforce rate limiting or lockout
- does not capture authority identity at time of use

## Outcome
A validated override system that:
- uses 4-character codes (human usable)
- supports manual or auto assignment
- stores only hashed codes
- logs all override attempts (success + failure)
- captures manager identity at time of use
- enforces basic abuse protection

## Scope

### In Scope
- Manager code model refactor (4-char)
- Manual + auto assignment
- Hash-only storage
- Validation service with audit logging
- Override audit table
- Rate limiting / lockout
- Frontend modal payload expansion

### Out of Scope
- Voucher editing
- Catalog editing
- UI redesign beyond modal fields
- Historical audit backfill

## Constraints
- Override authority must remain independent of logged-in user
- All validation attempts must be auditable
- No plaintext code storage
- Must not break existing voucher flows

## Protected Surfaces
- includes/class-manager.php
- includes/class-database.php
- svdp-vouchers.php
- public/js/cashier-shell.js

## Data Impact
- New table: svdp_override_audit
- Extended managers table (lockout fields)

## Concurrency
- Validation must be idempotent
- No duplicate audit rows per request
- Lockout must be respected across concurrent attempts

## Security
- Codes hashed via wp_hash_password
- No code retrieval after creation
- Failed attempts tracked

## Done When
- Codes are 4-character and validated
- Manual + auto assignment works
- Audit table logs all attempts
- Lockout triggers correctly
- Frontend sends required fields
- Validation returns structured authority result