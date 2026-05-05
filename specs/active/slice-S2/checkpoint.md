# S2 Checkpoint — Voucher Corrections

## Database

- [ ] svdp_voucher_corrections table exists
- [ ] indexes present (voucher_id, created_at)

## Behavior

- [ ] correction writes audit row BEFORE update
- [ ] no-op produces no audit row
- [ ] multiple field changes create multiple rows

## Authority

- [ ] dob requires authority
- [ ] voucher_created_date requires authority
- [ ] status requires authority

## Integrity

- [ ] original voucher data preserved in audit
- [ ] no overwrite without audit

## API

- [ ] endpoint restricted to admin
- [ ] invalid field ignored (not error)

## Validation Commands

- `wp db query "SHOW TABLES LIKE 'wp_svdp_voucher_corrections';"`
- `wp db query "SELECT * FROM wp_svdp_voucher_corrections ORDER BY id DESC LIMIT 10;"`
