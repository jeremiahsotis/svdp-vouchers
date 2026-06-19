# Access Audit Model

## Rule
All auth and policy decisions must be traceable and explainable.

## Required fields
- decision: allow or deny
- reason
- actor
- resource
- timestamp

## Release C Audit Rule

Slice C0 adds planning constraints only.

Future Release C protected mutations must preserve auditability for request group creation, delivery snapshots, Household Goods catalog changes, fulfillment entries, finalization, finalization notes, receipt and invoice generation, and configuration changes.

Vincentians must not receive cashier or administrator authority through the Assisted Builder.
