# Checkpoint S1 — Override Authority

## Functional

- [ ] 4-character codes generated
- [ ] Manual code accepted and validated
- [ ] Duplicate code prevented
- [ ] Code stored only as hash
- [ ] Validation returns correct manager

## Audit

- [ ] Successful validation logged
- [ ] Failed validation logged
- [ ] Manager name captured
- [ ] Actor user captured
- [ ] Timestamp recorded

## Security

- [ ] No plaintext code stored
- [ ] Lockout triggers after repeated failures
- [ ] Locked manager cannot validate

## API

- [ ] Endpoint accepts: code, managerName, reasonId, context
- [ ] Response format consistent

## Frontend

- [ ] Modal sends all required fields
- [ ] Validation failure handled cleanly
- [ ] Validation success continues flow

## Validation Commands

- manual code test
- brute force attempt test
- lockout test
- audit row inspection

## Done

All checks pass with no console errors and correct DB state.