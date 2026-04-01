# Architecture Handoff

## Current codebase findings
Relevant existing structure:
- `svdp-vouchers.php` already registers REST routes and has heartbeat/auth handling
- `includes/class-database.php` owns table creation and migrations
- `includes/class-voucher.php` owns core voucher CRUD and business logic
- `public/templates/cashier-station.php` is the current cashier page shell
- `public/js/cashier-station.js` is a large jQuery-based cashier workflow and currently contains the stale-page/session-expired behavior that needs to die
- `public/templates/voucher-request-form.php` and `public/js/voucher-request.js` power the public form

## Core architectural move
Do **not** continue building on the current cashier script.

Build a new cashier shell that:
- loads once
- keeps the operator inside one persistent interface
- updates voucher list, detail, and item states through fragment swaps and async calls
- never depends on a hard refresh for normal recovery

## Recommended component layout

### Keep
- WordPress plugin bootstrap
- current shortcode model
- current PHP partial/template approach
- current database-first design

### Replace / add
- replace current cashier interaction layer with HTMX + Alpine
- add furniture-specific tables
- add furniture-specific REST endpoints
- add furniture-specific admin screens for catalog, cancellation reasons, invoices, and statements

## Cashier shell design

### Shell structure
Single screen with these regions:
1. top utility bar
2. voucher list pane
3. voucher detail pane
4. mobile drawers / sheets for action flows where needed

### Behavior
- voucher list can update without losing current selection
- voucher detail can update without reloading the list
- item updates should update only the affected card and summary blocks when possible
- no modal stacking hell
- no full-screen blocking loaders unless absolutely necessary

## Session strategy

### Problem in current code
The current plugin already tries to preserve sessions, but the UI still expects refresh/error recovery patterns. That creates the operational line-holding problem.

### Required strategy
- extend auth cookie lifetime
- cashier shell keepalive every 60 seconds
- for cashier fragment/API requests, rely on authenticated session continuity rather than forcing the operator into refresh flows
- if session is truly gone, show one explicit re-auth state, not repeated expiry messages

## Data model strategy
The existing `wp_svdp_vouchers` table remains the root record.

Furniture complexity should be normalized into related tables instead of bloating the root voucher row.

### Voucher root additions
Add minimally necessary root columns to support type-aware behavior and cleaner status control. Recommended additions:
- `voucher_type` (`clothing`, `furniture`)
- `workflow_status` for internal flow state if needed

### Furniture support tables
- `wp_svdp_catalog_items`
- `wp_svdp_furniture_voucher_meta`
- `wp_svdp_voucher_items`
- `wp_svdp_voucher_item_photos`
- `wp_svdp_furniture_cancellation_reasons`
- `wp_svdp_invoices`
- `wp_svdp_invoice_statements`

## Snapshot rule
When a furniture item is selected, store:
- the `catalog_item_id`
- snapshot name
- snapshot category
- snapshot pricing type
- snapshot price min/max/fixed
- snapshot store walk sort order

This is not optional. Historical vouchers must not drift when catalog rows change.

## Furniture request form architecture
The public form remains one voucher form entry point.

### Branching
- clothing path keeps the current surface behavior
- furniture path introduces category-based item selection and delivery capture

### Furniture request UX rules
- do not dump the entire catalog into one long list without structure
- group by category:
  - used furniture
  - handmade furniture
  - mattresses & frames
  - household goods
- keep a sticky selected-items summary on mobile
- show estimate range and requestor portion range simply

## Cashier furniture fulfillment architecture

### Voucher card level
Furniture vouchers must be visually distinct from clothing vouchers.

Card must show at minimum:
- name
- DOB
- voucher type badge
- delivery yes/no
- redemption/completion status
- item progress summary

### Detail level
Furniture voucher detail must show:
- request summary
- delivery details
- item cards sorted by store-walk order
- progress state
- completion affordance only when all items are resolved

### Item state model
Recommended statuses:
- `requested`
- `completed`
- `cancelled`

Use substitution fields on the same item row rather than shadow rows. That keeps the mobile workflow and reporting simpler.

### Item completion rule
An item cannot move to `completed` unless:
- `actual_price` exists and is > 0
- at least one photo exists

This must be enforced:
- in UI state
- in endpoint validation
- in service/model logic

## Receipt and invoice generation

### Receipt
Audience: neighbor
- no prices
- show requested items
- show fulfilled/substituted/cancelled outcomes
- include delivery note if relevant

### Invoice
Audience: conference/accounting
- stored only, not emailed automatically
- amount = 50% of actual fulfilled item total + $50 delivery fee if selected
- cancelled items contribute $0

## Statements
Statements are generated from invoices.

### Query behavior
Default range:
- first day of previous month
- last day of previous month

### Exclusion behavior
Invoices already attached to a statement are excluded from future statement generation by `statement_id IS NOT NULL`.

## File-level implementation guidance

### Files likely to change heavily
- `svdp-vouchers.php`
- `includes/class-database.php`
- `includes/class-voucher.php`
- `includes/class-admin.php`
- `public/templates/cashier-station.php`
- `public/templates/voucher-request-form.php`

### Files likely to be replaced or deprecated
- `public/js/cashier-station.js`

### New recommended files
- `includes/class-furniture-catalog.php`
- `includes/class-furniture-voucher.php`
- `includes/class-invoice.php`
- `includes/class-statement.php`
- `includes/class-permissions.php`
- `admin/views/tab-furniture-catalog.php`
- `admin/views/tab-furniture-settings.php`
- `admin/views/tab-invoices.php`
- `admin/views/tab-statements.php`
- `public/templates/cashier/partials/*.php`
- `public/js/cashier-shell.js` or equivalent minimal shell initializer

## Constraint to preserve
Current clothing vouchers may be refactored underneath, but the public-facing and cashier-facing clothing UX should remain familiar and functionally unchanged on the surface.
