# Concurrency Model

## Rules

### Idempotency
All external mutations must be:
- idempotent
OR
- explicitly guarded

### Retry Behavior
- retries must not create duplicates
- side effects must be controlled

### Ordering
- define ordering guarantees where required

### Conflict Handling
- last-write-wins or explicit conflict resolution
