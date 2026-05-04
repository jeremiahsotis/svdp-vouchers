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
