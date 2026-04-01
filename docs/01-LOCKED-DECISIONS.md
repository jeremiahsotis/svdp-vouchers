# Locked Decisions

## Product scope
- Add one new voucher type: `furniture`
- Treat furniture and household goods as the same voucher type
- Keep clothing vouchers intact on the surface
- Replace the cashier UI entirely

## Front end
- Use **HTMX** for server-rendered fragment swaps
- Use **Alpine.js** for local UI state, inline validation state, drawers, upload previews, and small interactions
- Avoid React
- Avoid expanding the jQuery cashier app

## Backend
- Keep WordPress plugin structure
- Keep custom DB tables
- Use plugin REST endpoints under the existing `svdp/v1` namespace or a sub-group under it
- Enforce permissions in the backend for all furniture mutation actions

## Session / reliability
- Extend WordPress auth cookie duration for cashier users
- Add a keepalive ping every 60 seconds from the cashier shell
- Remove cashier dependency on full page refresh and stale-page recovery behavior
- The cashier shell loads once and updates fragments only

## Photos
- Store furniture redemption photos on the same server as WordPress
- Do **not** use the general WordPress Media Library as the primary operational storage model
- Store in a plugin-managed uploads subtree:
  - `/wp-content/uploads/svdp-vouchers/{voucher_id}/{item_id}/`
- Normalize images on upload:
  - strip metadata
  - resize long edge to 1600px
  - convert to JPEG at ~80 quality
  - generate one thumbnail only

## Catalog and substitutions
- No stock tracking
- No live inventory
- No reserved quantities
- Catalog is selection-only
- Substitutions should prefer an existing catalog item
- Free-text substitute item name override is allowed
- Voucher items must snapshot catalog details at request time so future catalog changes do not alter historical vouchers

## Pricing
- Range pricing allowed for used furniture and household goods
- Fixed pricing allowed for handmade furniture and mattresses/frames
- Public request form shows estimate range and requestor portion range
- Invoice uses actual fulfilled prices, not estimated prices
- Delivery adds a flat $50 fee when selected

## Permissions
- Existing cashier viewers can see all vouchers, including furniture vouchers
- New elevated capability required to edit or redeem furniture vouchers:
  - `svdp_redeem_furniture_vouchers`
- Separate management capability for catalog/settings is recommended:
  - `svdp_manage_furniture_catalog`

## Statements
- Generate statements from invoices, not directly from vouchers
- Default statement date range is the first day to the last day of the previous month
- An invoice is excluded from future statements by linking it to a `statement_id`
- Do not add payment tracking inside this plugin
