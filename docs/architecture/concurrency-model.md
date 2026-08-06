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

## Slice C3 Implementation Note

Shared fulfillment Save Progress uses last-write-wins replacement of unfinalized fulfillment entries for each requested line. Fulfilled quantities are capped against requested quantities in the cashier workflow and rechecked on the server; not-fulfilled quantities are derived from the saved entries. The finalization endpoint refuses already redeemed, denied, dynamically expired, line-less, or over-fulfilled vouchers, and finalization locks ordinary editing by changing the voucher to `Redeemed`.

Retries after successful finalization are guarded by the redeemed status and existing invoice uniqueness. C3 does not add asynchronous processing, dispatch, inventory, POS, or delivery-attempt behavior.

## Slice P1 Implementation Note

Furniture category updates carry the last observed `updated_at` value and reject stale writes. Household Goods configuration retains last-write-wins updates with validation immediately before writes. Issued request snapshots isolate committed requests from later catalog changes.

## Slice C4 Implementation Note

Public Assisted Builder submission calls the Release C request-group service, which validates selected voucher types, duplicate eligibility, type-specific selections, requested-line snapshots, and group-level delivery before committing. Request-group rows, child voucher rows, Furniture/Household Goods requested lines, and the delivery snapshot are written in one transaction.

Duplicate child types remain guarded by the unique `(request_group_id, voucher_type)` key. C4 does not add asynchronous processing, saved drafts, dispatch, inventory, POS, or delivery-attempt behavior.

## Slice A1 Implementation Note

Monthly accounting uses a unique monthly batch key, conditional attachment of unstatemented invoices, and one nullable export-batch owner per statement. Cron retries reuse existing state, do not recreate completed batches, and do not resend statements already marked sent. Manual cycle execution uses the same monthly key and guards.
