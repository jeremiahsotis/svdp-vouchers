# UI Screen Map

## Public request form
One entry point. Two branches.

### Section 1 - household basics
Keep current clothing-facing surface familiar.

Fields remain familiar where relevant:
- first name
- last name
- DOB
- adults
- children
- conference
- vincentian/requestor fields

### Section 2 - voucher type
- Clothing
- Furniture / Household Goods

### Clothing branch
- preserve current visible behavior

### Furniture branch
#### Categories
- Used Furniture
- Handmade Furniture
- Mattresses & Frames
- Household Goods

#### Item rows
Each row shows:
- item name
- range or fixed price display
- add/remove affordance

#### Sticky mobile summary
Always visible on small screens:
- selected item count
- estimated total range
- requestor portion range
- delivery toggle

#### Delivery
If selected:
- show delivery address fields
- show $50 delivery fee in summary

## Cashier shell
Replace current cashier page entirely.

### Desktop layout
- left: voucher list
- right: voucher detail

### Mobile layout
- list first
- detail opens inline or as a full-height drawer/sheet
- item actions stay thumb-friendly

## Voucher list cards
### Clothing card
Keep familiar surface behavior.

### Furniture card
Show:
- type badge
- name
- DOB
- delivery yes/no
- completion status
- quick progress summary

Example summary:
- `5 items • 3 completed • 1 cancelled • 1 remaining`

## Furniture voucher detail
### Header block
- name
- DOB
- conference
- delivery badge
- address if delivery
- current progress

### Item list
Sorted by store-walk order.

Each item card shows:
- requested item name
- requested price range or fixed price
- substitution state if applicable
- current status badge
- action controls

## Item action model
### Requested state
Actions:
- enter price
- add photo
- mark unavailable

### Unavailable branch
Actions:
- substitute from catalog
- substitute with free text
- cancel item

### Completed state
Show:
- actual price
- photo count
- substitution info if used

### Cancelled state
Show:
- cancellation reason
- notes if present

## Voucher completion state
Only show the final completion control when all items are resolved:
- all items `completed` or `cancelled`
- zero unresolved `requested` items

## Admin screens
### Furniture Catalog
Table/form UI for:
- name
- category
- pricing type
- min/max/fixed price
- sort order
- active/inactive

### Furniture Settings
Manage:
- cancellation reasons
- delivery fee if ever configurable later
- document numbering settings if needed

### Invoices
Search/filter list for stored invoices.

### Statements
Generate statement:
- conference selector
- default previous-month date range
- preview eligible invoices
- generate and store statement
