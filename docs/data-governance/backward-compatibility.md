# Backward Compatibility

## Rules
Define for each affected surface:
- what can break immediately
- what must be phased
- what must remain stable

## Default
- API contracts: versioned or stable by explicit rule
- DB: must not corrupt existing data
- tenant data: must remain valid when demo or seed data is removed
