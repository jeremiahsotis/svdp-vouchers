# REST Endpoint Map

All routes below assume the existing namespace pattern is retained.

Base namespace:
- `/wp-json/svdp/v1/`

## Existing routes to preserve
These already exist and should remain working for clothing:
- `GET /vouchers`
- `POST /vouchers/check-duplicate`
- `POST /vouchers/create`
- `PATCH /vouchers/{id}/status`
- `PATCH /vouchers/{id}/coat`
- `GET /conferences`

## Reliability routes
### `POST /cashier/ping`
Purpose:
- keep session warm
- confirm cashier shell is still authenticated

Permission:
- cashier viewer access

Response:
- auth status
- optional refreshed nonce/session metadata if needed

## Cashier shell routes
### `GET /cashier/vouchers`
Purpose:
- return cashier list data or rendered fragment for all visible vouchers
- support type-aware filtering and search

Permission:
- cashier viewer access

Query params:
- `search`
- `filter`
- `sort`
- `page`
- `voucher_type`

### `GET /cashier/vouchers/{id}`
Purpose:
- return cashier detail payload or rendered fragment for a specific voucher

Permission:
- cashier viewer access

## Public request form routes
### `GET /catalog-items`
Purpose:
- return active catalog items for public request form selection

Permission:
- public

### `POST /vouchers/create`
Purpose:
- extend existing create route to branch on `voucher_type`
- clothing behavior remains intact
- furniture behavior persists root voucher + meta + items

Permission:
- public

Payload additions for furniture:
- `voucherType`
- `deliveryRequired`
- `deliveryAddress`
- `items[]`

## Furniture redemption routes
All mutation routes below require:
- cashier access
- `svdp_redeem_furniture_vouchers` if voucher type is furniture

### `POST /cashier/vouchers/{id}/start`
Purpose:
- optional route to mark furniture voucher as in progress

### `POST /cashier/vouchers/{id}/items/{item_id}/photo`
Purpose:
- upload and attach one photo to a voucher item

Validation:
- authenticated user with furniture redeem capability
- voucher belongs to furniture type
- file type and size accepted
- normalize and store file

### `DELETE /cashier/vouchers/{id}/items/{item_id}/photo/{photo_id}`
Purpose:
- remove photo before voucher completion if needed

### `POST /cashier/vouchers/{id}/items/{item_id}/complete`
Purpose:
- mark item completed

Required payload:
- `actualPrice`
- `completionNotes` optional

Validation:
- at least one photo attached
- `actualPrice > 0`

### `POST /cashier/vouchers/{id}/items/{item_id}/substitute`
Purpose:
- record substitution on same item row

Payload options:
- `substitutionType = catalog | free_text`
- `substituteCatalogItemId` when catalog
- `substituteItemName` when free_text

### `POST /cashier/vouchers/{id}/items/{item_id}/cancel`
Purpose:
- cancel item

Required payload:
- `cancellationReasonId`
- `cancellationNotes` optional

### `POST /cashier/vouchers/{id}/complete`
Purpose:
- complete furniture voucher
- generate receipt
- generate invoice

Validation:
- all furniture voucher items must be `completed` or `cancelled`
- no `requested` items remaining

Side effects:
- set furniture voucher completion metadata
- generate and store receipt
- generate and store invoice
- update root voucher status/workflow state

## Admin catalog/settings routes
### `GET /admin/catalog-items`
### `POST /admin/catalog-items`
### `PATCH /admin/catalog-items/{id}`
### `POST /admin/catalog-items/{id}/archive`
Purpose:
- manage selectable furniture catalog rows

Permissions:
- `manage_options` or dedicated `svdp_manage_furniture_catalog`

### `GET /admin/furniture-cancellation-reasons`
### `POST /admin/furniture-cancellation-reasons`
### `PATCH /admin/furniture-cancellation-reasons/{id}`
### `POST /admin/furniture-cancellation-reasons/{id}/archive`
Purpose:
- manage cancellation reasons

## Invoice and statement routes
### `GET /admin/invoices`
Purpose:
- filter invoices by conference/date/status

### `GET /admin/statements/default-range`
Purpose:
- return first and last day of previous month for UI defaults

### `POST /admin/statements/generate`
Purpose:
- generate a statement for a given conference and date range
- attach all unstatemented invoices in range to new statement

Payload:
- `conferenceId`
- `periodStart`
- `periodEnd`

Validation:
- only invoices with `statement_id IS NULL` are eligible

### `GET /admin/statements/{id}`
Purpose:
- view/download statement details
