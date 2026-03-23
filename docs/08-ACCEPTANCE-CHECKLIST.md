# Acceptance Checklist

## Clothing regressions
- [ ] Clothing voucher request form still looks familiar and behaves the same on the surface.
- [ ] Clothing voucher duplicate checks still work.
- [ ] Clothing emergency voucher creation still works.
- [ ] Clothing voucher redemption still works on the surface.
- [ ] Coat issuance flow still works.

## Cashier reliability
- [ ] Cashier page loads once and does not require routine refreshes.
- [ ] No normal-flow stale-page or expired-page UI appears.
- [ ] Keepalive works across long cashier sessions.
- [ ] Logged-in users are not involuntarily logged out during normal active cashier use.

## Furniture public request flow
- [ ] User can choose furniture voucher type from the public request form.
- [ ] User can select items from grouped categories.
- [ ] Range-priced and fixed-priced items display correctly.
- [ ] Request summary shows estimated total range and requestor portion range.
- [ ] Delivery toggle adds address capture and $50 fee display.
- [ ] Submitted furniture voucher persists correctly with item snapshots.

## Furniture cashier visibility and permissions
- [ ] Cashier viewers can see furniture vouchers in the cashier station.
- [ ] Cashier viewers can see redemption/completion outcomes for furniture items.
- [ ] Cashier viewers cannot mutate furniture vouchers without elevated capability.
- [ ] Elevated users can mutate furniture vouchers.
- [ ] Server-side permission checks block unauthorized mutation attempts.

## Furniture fulfillment workflow
- [ ] Furniture vouchers are visually distinct from clothing vouchers.
- [ ] Furniture item list is sorted by store-walk order.
- [ ] Elevated user can enter actual price for an item.
- [ ] Elevated user can upload one or more photos for an item.
- [ ] Completed item cannot be saved without actual price and at least one photo.
- [ ] Unavailable item can be substituted from catalog.
- [ ] Unavailable item can be substituted with free-text item name override.
- [ ] Unavailable item can be cancelled only with a preset reason.
- [ ] Voucher cannot be completed while unresolved requested items remain.

## Documents and accounting
- [ ] Completing a furniture voucher generates a receipt with no prices.
- [ ] Completing a furniture voucher generates and stores an invoice.
- [ ] Invoice amount uses actual fulfilled prices x 50% plus delivery fee where applicable.
- [ ] Cancelled items do not contribute to invoice total.
- [ ] Statements default to the first and last day of the previous month.
- [ ] Statement generation includes only invoices not already attached to a statement.
- [ ] Generated statement excludes previously statemented invoices from future runs.

## Photo storage
- [ ] Photos are stored under plugin-managed uploads paths.
- [ ] Photos are normalized on upload.
- [ ] Photos do not flood the general media workflow.
- [ ] File paths are linked back to voucher item records correctly.

## General UX
- [ ] Mobile furniture fulfillment is practical one-handed or close to it.
- [ ] Actions are clear and require minimal taps.
- [ ] No hidden critical state.
- [ ] Voucher progress is obvious.
- [ ] The interface does not make the cashier think.
