# S2 Bootstrap — Voucher Corrections

## You are implementing Slice S2

This slice introduces controlled voucher corrections with field-level audit.

## Authoritative Sources (READ IN THIS ORDER)

1. implementation-brief.md
   → Defines scope, constraints, and intent

2. codepack.md
   → Defines exact code to implement

3. checkpoint.md
   → Defines validation requirements

## Rules

- DO NOT expand scope
- DO NOT modify protected contracts beyond defined impact
- DO NOT introduce UI
- DO NOT batch update fields without per-field audit

## Execution Order

1. Create database table
2. Add service method
3. Add authority enforcement
4. Add REST endpoint
5. Run validation commands
6. Verify checkpoint

## Completion Criteria

All checkpoint items must pass before marking complete.
