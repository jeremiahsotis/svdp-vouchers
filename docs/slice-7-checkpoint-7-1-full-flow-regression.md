# Slice 7.1 - Full Flow Regression

## Goal

Confirm the voucher system is safe to use after Slices 1 through 6.

## Scope

Validate:

- clothing voucher creation
- furniture voucher creation
- catalog browse/search
- pricing display
- quantity state
- delivery optional path
- address verification optional path
- cashier fulfillment
- receipt generation
- invoice generation
- no neighbor-delivery system dependency

## Out of Scope

Do not implement:

- delivery attempts
- dispatch tracking
- RouteShyft logic
- service area enforcement
- neighbor notification delivery
- OTP/token delivery access

## Required Repo-State Commands

```bash
git status --short
git log --oneline -n 10

grep -RIn "delivery attempt\|route\|dispatch\|otp\|token\|neighbor delivery" includes public/templates public/js docs | head -n 200

grep -RIn "deliveryRequired\|delivery_verified\|delivery_address_verified\|deliveryLat\|deliveryLng" includes public/templates public/js | head -n 300

grep -RIn "get_redemption_rule_text\|expires 30 days\|redeemed in one visit" includes public/templates public/js docs | head -n 200

grep -RIn "Maximum Conference commitment\|Conference cost\|Final fulfilled pricing\|may vary\|estimate" public/templates public/js includes docs | head -n 300

grep -RIn "photo_required_for_completion\|Upload at least one photo\|Add Photo \*" includes public/templates public/js | head -n 100

grep -RIn "data-category-card\|data-category-section\|data-catalog-adjust\|selectedItems" public/templates public/js | head -n 300
```

## Manual Test Matrix

### A. Clothing request flow

- Submit a standard clothing voucher.
- Confirm voucher creates successfully.
- Confirm duplicate check still works.
- Confirm confirmation message displays.
- Confirm cashier station can view voucher.
- Confirm clothing voucher can be redeemed.
- Confirm coat logic still behaves as expected.

Pass when:

- no furniture-only fields block clothing submission
- no delivery fields appear or validate
- no JS errors appear
- no pricing language intended for furniture leaks into clothing flow

### B. Furniture request without delivery

- Select Furniture / Household Goods.
- Open category.
- Select at least two items.
- Search for an item.
- Clear search.
- Confirm selected quantities persist.
- Submit voucher.
- Approve maximum Conference commitment.

Pass when:

- category cards do not duplicate
- selected item count is accurate
- totals are accurate
- approval modal totals match summary panel
- voucher creates successfully
- delivery address is not required

### C. Furniture request with unverified delivery address

- Select Furniture / Household Goods.
- Select at least one item.
- Add Delivery.
- Manually type address.
- Do not select provider suggestion.
- Submit voucher.
- Approve maximum Conference commitment.

Pass when:

- form allows submission
- voucher creates successfully
- cashier view shows address
- cashier view warns address is not verified
- receipt warns address is not verified
- no hard validation blocks submission

### D. Furniture request with verified delivery address

- Select Furniture / Household Goods.
- Select at least one item.
- Add Delivery.
- Type address until suggestions appear.
- Select provider suggestion.
- Confirm Address 1, City, State, ZIP populate.
- Submit voucher.
- Approve maximum Conference commitment.

Pass when:

- lat/lng hidden fields populate
- verified flag populates
- normalized address populates
- submitted voucher shows verified address
- no unverified warning appears

### E. Search and quantity stability

- Select one item.
- Search `bed`.
- Increment quantity.
- Search `beds`.
- Decrement quantity.
- Clear search.
- Reopen category.

Pass when:

- quantity never goes negative
- selected count stays accurate
- visible quantity matches selected count
- approval total matches quantity
- item rows do not duplicate

### F. Cashier furniture fulfillment

- Open furniture voucher in cashier station.
- Complete one item without photo.
- Complete one item with photo, if available.
- Substitute one item.
- Cancel one item.
- Complete voucher once all items are resolved.

Pass when:

- photo is optional
- actual price is required
- item state updates correctly
- voucher cannot complete until all items resolved
- completion generates receipt
- completion generates invoice
- voucher status becomes Redeemed / completed

### G. Receipt and invoice validation

- Open Neighbor Receipt.
- Open Conference Invoice.

Pass when:

- receipt shows no pricing to neighbor
- receipt shows redemption rule
- receipt shows delivery address when delivery selected
- receipt shows verification warning only when unverified
- invoice uses actual fulfilled prices
- invoice includes delivery fee when delivery selected
- invoice does not use confusing “may vary” language

### H. Negative / failure checks

Test:

- Submit furniture voucher with zero items.
- Submit delivery voucher with missing required address fields.
- Try completing furniture item with no actual price.
- Try completing voucher before all items resolved.
- Try using nonsense search.
- Clear nonsense search.

Pass when:

- each failure gives clear user-facing error
- no PHP fatal errors
- no JS console errors
- no broken partially-created voucher state

## Done When

Slice 7.1 passes when:

- all manual tests above pass
- repo grep shows no accidental delivery tracking / RouteShyft scope creep
- no required photo behavior remains
- no “may vary” language remains in user-facing UI
- neighbor-facing receipt rules are present
- clothing flow still works
- furniture flow works with and without delivery
- unverified delivery addresses warn but do not block