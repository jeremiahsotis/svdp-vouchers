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

## Release C Request Group Rule

Slice C0 adds planning constraints only.

Future Release C request group creation must be atomic. Retries must not create duplicate child vouchers inside a request group, and no partial request group may remain visible as a valid request.

Future cashier fulfillment work must define conflict behavior for saving requested-line and fulfillment-entry changes before finalization.

## Slice C1 Implementation Note

The C1 backend request-group service wraps request-group creation, child voucher insertion, and group-level delivery snapshot insertion in one database transaction. The voucher table also has a unique `(request_group_id, voucher_type)` key so duplicate child voucher types cannot exist in one committed group.

## Slice C2 Implementation Note

Household Goods catalog administration uses last-write-wins updates through WordPress admin AJAX, guarded by capability checks and validation immediately before each database write. Duplicate active browse group names and duplicate active category names within a browse group are checked at mutation time.

C2 does not add asynchronous processing, public request submission, or cashier fulfillment concurrency. Later request creation slices must snapshot current catalog values at issuance and must not recalculate issued snapshots after concurrent configuration changes.
