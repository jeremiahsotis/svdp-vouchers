# Migration Policy

## Rule
Any schema change must include:
- migration script
- rollback strategy
- invariant updates

## Required
- forward migration
- backward migration, if feasible
- data preservation strategy
- compatibility impact assessment

## Forbidden
- silent schema changes
- schema changes without migration
- schema changes that assume demo or seed data is permanent

## Release C Planning Rule

Slice C0 makes no schema change. Future Release C schema work must follow this policy and the Release C Product Contract.

Release C migrations must not rewrite, reinterpret, or destructively migrate historical voucher records. In particular, legacy `household` voucher values remain Furniture history and must not become the new `household_goods` voucher type.
