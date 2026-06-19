# Release C Product Contract

**Project:** SVdP Voucher System  
**Release:** Release C  
**Status:** Consolidated product contract for developer handoff  
**Compiled:** 2026-06-19

## Document Purpose

This document consolidates the previously drafted Release C contract sections into one authoritative product contract.

It defines:

- the core architecture and data model;
- Assisted Builder request flows;
- cashier fulfillment workflows;
- administration and configuration behavior;
- acceptance criteria;
- migration and backward-compatibility requirements;
- audit, security, accessibility, and non-goal boundaries.

The document is intended to become the source of truth for implementation planning, slice generation, developer execution, QA, and release acceptance.

## Source Sections Consolidated

- Part 1: Core Architecture and Data Model
- Part 2A: Assisted Builder Public Request Flow
- Part 2B: Cashier Cards, Fulfillment, Finalization, and Request-Group Context
- Part 2C: Administration Screens and Configuration Behavior
- Part 3: Acceptance Criteria, Migration, Backward Compatibility, Audit, and Non-Goals

## Contract Hierarchy

When implementing Release C, apply this hierarchy:

1. This consolidated Release C Product Contract.
2. The locked S5 Assisted Builder design and layout decisions, except where expressly superseded by this contract.
3. Existing protected contracts and production behavior for legacy Clothing and Furniture vouchers.
4. Current repository implementation details, only where they do not conflict with the contract above.

---

## Part 1: Core Architecture and Data Model

The current code snapshot confirms two important realities:

- The system currently recognizes only `clothing` and `furniture` as root voucher types. It also normalizes the old value `household` into `furniture`. Release C must introduce a distinct new root type: `household_goods`.
- Furniture delivery, pricing, completion, invoices, and item notes are currently furniture-specific. Release C needs to move the shared concepts into structures that support both Furniture and Household Goods without breaking historical furniture vouchers.

This section defines the core architecture and data model for Release C.

---

### 1. Voucher Types

Release C supports exactly three root voucher types:

| Technical value   | Display label           | Request model                                         |
| ----------------- | ----------------------- | ----------------------------------------------------- |
| `clothing`        | Clothing Voucher        | Household-based clothing voucher                      |
| `furniture`       | Furniture Voucher       | Catalog items with requested quantities               |
| `household_goods` | Household Goods Voucher | Granular catalog categories with requested quantities |

### Legacy normalization rule

Existing historical data remains intact.

```text
Existing legacy value: household
Historical interpretation: furniture
New Release C value: household_goods
```

The migration must **not** reinterpret old `household` records as Household Goods. Old records remain Furniture history. All new Household Goods vouchers use the explicit value:

```text
household_goods
```

---

### 2. Request Group Model

A Vincentian can select any combination of Clothing, Furniture, and Household Goods in one submission.

That requires a new parent record:

```text
Voucher Request Group
├── Clothing Voucher, when selected
├── Furniture Voucher, when selected
└── Household Goods Voucher, when selected
```

### Request group rules

- A request group contains one, two, or three child vouchers.
- A request group may contain no more than one voucher of each type.
- Each child voucher remains independently redeemable, independently expirable, independently auditable, and independently correctable.
- The request group is not itself redeemed or expired.
- A group exists to connect the shared request, household, requestor, Conference, delivery decision, and submission event.

### Proposed request-group table

```text
wp_svdp_voucher_request_groups
```

| Field                       | Purpose                                                |
| --------------------------- | ------------------------------------------------------ |
| `id`                        | Primary identifier                                     |
| `conference_id`             | Conference or organization responsible for the request |
| `first_name`                | Household snapshot                                     |
| `last_name`                 | Household snapshot                                     |
| `dob`                       | Household identity snapshot                            |
| `adults`                    | Household snapshot                                     |
| `children`                  | Household snapshot                                     |
| `requestor_name`            | Requestor snapshot                                     |
| `requestor_email`           | Requestor snapshot                                     |
| `created_by`                | Submission source / actor                              |
| `submitted_at`              | Submission timestamp                                   |
| `created_at` / `updated_at` | Standard audit timestamps                              |

### Child-voucher link

Add this nullable field to the existing voucher table:

```text
request_group_id
```

New Release C vouchers created through the builder must have a request group. Historical vouchers may leave this blank.

Required database rule:

```text
UNIQUE(request_group_id, voucher_type)
```

That prevents accidental creation of two Furniture vouchers or two Household Goods vouchers in one request group.

### Snapshot rule

The group holds the shared original request snapshot.

Each child voucher also retains the values needed for its existing independent voucher behavior and historical documents.

That redundancy is intentional. A Furniture voucher should still render and be auditable even if it is viewed outside the context of the request group.

---

### 3. Voucher-Type Capability Settings

Delivery must be configuration-driven, not hardcoded into Furniture or Household Goods.

### Required setting

Each root voucher type has:

```text
Delivery available for this voucher type: On / Off
```

Initial configuration:

| Voucher type    | Delivery available |
| --------------- | -----------------: |
| Clothing        |                Off |
| Furniture       |                 On |
| Household Goods |                 On |

This is a capability setting for future requests. It does not alter already-issued vouchers.

### Recommended configuration structure

```text
wp_svdp_voucher_type_capabilities
```

| Field                | Purpose                                                |
| -------------------- | ------------------------------------------------------ |
| `voucher_type`       | `clothing`, `furniture`, or `household_goods`          |
| `delivery_available` | Whether the request form offers delivery for that type |
| `updated_at`         | Configuration audit                                    |
| `updated_by_user_id` | Configuration audit                                    |

The existing global “available voucher types” setting and Conference-level allowed-voucher-type settings remain in place. This capability setting answers a different question:

```text
Can this voucher type participate in delivery?
```

### Delivery visibility rule

The builder shows one Delivery step when at least one selected voucher type has delivery enabled.

| Request selection           | Delivery step |
| --------------------------- | ------------- |
| Clothing only               | Hidden        |
| Furniture only              | Shown         |
| Household Goods only        | Shown         |
| Clothing + Furniture        | Shown         |
| Clothing + Household Goods  | Shown         |
| Furniture + Household Goods | Shown         |
| All three                   | Shown once    |

The Delivery step must identify the eligible types clearly:

```text
Delivery is available for: Furniture and Household Goods
```

---

### 4. Group-Level Delivery

Delivery belongs to the request group, not to each voucher separately.

Locked rule:

> There is one delivery fee and one delivery attempt for the entire voucher request group.

That means a request containing Furniture and Household Goods can create one delivery record, not two independent delivery charges or two apparent delivery jobs.

### Proposed delivery table

```text
wp_svdp_voucher_request_group_delivery
```

| Field                             | Purpose                                           |
| --------------------------------- | ------------------------------------------------- |
| `request_group_id`                | One-to-one link to the request group              |
| `delivery_requested`              | Whether delivery was selected                     |
| `delivery_fee_snapshot`           | One fee for the whole group                       |
| `eligible_voucher_types_snapshot` | Which selected types were eligible when submitted |
| `address_line_1`                  | Delivery address snapshot                         |
| `address_line_2`                  | Delivery address snapshot                         |
| `city`                            | Delivery address snapshot                         |
| `state`                           | Delivery address snapshot                         |
| `zip`                             | Delivery address snapshot                         |
| `lat` / `lng`                     | Existing verification support                     |
| `verified`                        | Existing verification support                     |
| `verification_source`             | Existing verification support                     |
| `verification_confidence`         | Existing verification support                     |
| `normalized_address`              | Existing verification support                     |
| `created_at` / `updated_at`       | Audit timestamps                                  |

### Important boundary

Release C extends delivery eligibility and delivery request data.

It does **not** add:

- route planning
- driver scheduling
- delivery dispatch
- delivery calendar logic
- multiple delivery attempts
- RouteShyft integration
- a new logistics workflow

The group-level delivery record represents one possible delivery event under the existing delivery mechanism.

### Snapshot rule

Changing a voucher type’s delivery setting later affects only future requests.

For issued request groups, the system must preserve:

- whether delivery was available
- which selected types qualified
- whether delivery was selected
- delivery address
- verified-address data
- delivery fee

---

### 5. Household Goods as an Editable Catalog

Household Goods is not a hardcoded list and is not a Furniture catalog category.

It is an editable operational catalog with its own administration area.

### Browse groups

Browse groups organize the request form’s filter pills.

Examples:

```text
Kitchen
Bed & Bath
Window Coverings
Cleaning & Home Basics
Small Appliances
Storage & Organization
```

### Granular request categories

These are the actual selectable needs.

Examples:

```text
Pots & Pans
Plates & Bowls
Cups & Glasses
Silverware
Cooking Utensils
Bedding
Pillows
Bath Towels
Washcloths
Curtains
Curtain Rods
Laundry Basket
Trash Can
Broom
Mop
Toaster
Coffee Maker
Storage Bins
```

Furniture is never a Household Goods category.

### Proposed browse-group table

```text
wp_svdp_household_goods_browse_groups
```

| Field                       | Purpose                       |
| --------------------------- | ----------------------------- |
| `id`                        | Primary identifier            |
| `name`                      | Displayed pill/group label    |
| `slug`                      | Stable internal identifier    |
| `sort_order`                | Request-form order            |
| `active`                    | Available in the request form |
| `created_at` / `updated_at` | Audit timestamps              |

### Proposed Household Goods catalog table

```text
wp_svdp_household_goods_catalog
```

| Field                       | Purpose                                                |
| --------------------------- | ------------------------------------------------------ |
| `id`                        | Primary identifier                                     |
| `browse_group_id`           | Parent browse group                                    |
| `name`                      | Requestable category name                              |
| `slug`                      | Stable internal identifier                             |
| `estimated_conference_partner_cost_per_unit`  | Request-time projected Conference / Partner cost per fulfilled unit            |
| `quantity_max`              | Per-category limit; `0` means no limit                 |
| `cashier_guidance`          | Optional managed guidance, never a voucher Notes field |
| `sort_order`                | Display order                                          |
| `active`                    | Available for future requests                          |
| `created_at` / `updated_at` | Audit timestamps                                       |

### Price semantics

`estimated_conference_partner_cost_per_unit` is not the final redeemed price.

It exists so the system can estimate projected Conference / Partner exposure at request time:

```text
Requested Quantity × Estimated Conference / Partner Cost Per Unit
```

Example:

| Category    | Requested quantity | Estimated max unit price | Projected Conference / Partner cost |
| ----------- | -----------------: | -----------------------: | ----------------: |
| Bath Towels |                  6 |                    $4.00 |            $24.00 |
| Bedding     |                  4 |                   $12.00 |            $48.00 |
| Pots & Pans |                  1 |                   $10.00 |            $10.00 |

This lets the system predict upper exposure in the same general way the Furniture catalog does.

At redemption, actual values come from the cashier-entered fulfillment rows.

### Catalog snapshots

When a Household Goods voucher is issued, the request line stores immutable snapshots of:

- category name
- browse group
- estimated Conference / Partner cost per unit
- category quantity cap
- sort order

Later edits to the catalog must not rewrite older vouchers, reports, projected values, invoices, or receipts.

---

### 6. Household Goods Limits

The following are locked:

| Limit                       | Rule                                                       |
| --------------------------- | ---------------------------------------------------------- |
| Selected-category limit     | Maximum 10 distinct categories per Household Goods voucher |
| Category quantity limit     | Configurable by category                                   |
| Voucher-wide quantity limit | Configurable for Household Goods vouchers                  |
| Value of `0`                | No limit                                                   |

### Household Goods global settings

```text
Maximum selected categories: 10
Voucher-wide requested quantity maximum: configurable
```

The maximum selected-category count is a hard product rule. The other limits are configurable.

### Validation rules

The request form must validate immediately, not only at final submission.

```text
Selected categories must be 10 or fewer.

Requested quantity for a category must be greater than 0.

Requested quantity must not exceed the category maximum,
unless that maximum is 0.

Total requested quantity must not exceed the voucher-wide maximum,
unless that maximum is 0.
```

The user should see the running total and any remaining allowance while entering quantities.

---

### 7. Shared Fulfillment Model for Furniture and Household Goods

Furniture and Household Goods have different catalogs and request behavior, but cashiers should use the same redemption pattern.

The cashier opens one voucher and sees every requested line on one screen.

No repeated item-completion screens. No normal-use modal workflow. No calculator work.

### New requested-line model

For all new Release C Furniture and Household Goods vouchers, each requested item/category becomes a requested line.

```text
wp_svdp_voucher_requested_lines
```

| Field                               | Purpose                                                    |
| ----------------------------------- | ---------------------------------------------------------- |
| `id`                                | Primary identifier                                         |
| `voucher_id`                        | Parent voucher                                             |
| `line_type`                         | `furniture` or `household_goods`                           |
| `source_catalog_id`                 | Furniture or Household Goods catalog source, if applicable |
| `requested_name_snapshot`           | Immutable display name                                     |
| `requested_group_snapshot`          | Furniture category or Household Goods browse group         |
| `requested_quantity`                | Quantity requested                                         |
| `estimated_conference_partner_cost_per_unit_snapshot` | Request-time Conference / Partner cost estimate                                |
| `sort_order_snapshot`               | Display order                                              |
| `unavailable_quantity`              | Quantity ultimately unavailable                            |
| `unavailable_reason_id`             | Structured reason                                          |
| `unavailable_reason_snapshot`       | Reason text snapshot                                       |
| `resolution_status`                 | Requested, partially fulfilled, unavailable, resolved      |
| `created_at` / `updated_at`         | Audit timestamps                                           |

### Fulfillment-entry model

Each requested line may have one or more fulfillment entries.

```text
wp_svdp_voucher_fulfillment_entries
```

| Field                       | Purpose                                             |
| --------------------------- | --------------------------------------------------- |
| `id`                        | Primary identifier                                  |
| `requested_line_id`         | Parent requested line                               |
| `unit_price`                | Actual price each                                   |
| `fulfilled_quantity`        | Number fulfilled at that price                      |
| `line_total`                | System-calculated `unit_price × fulfilled_quantity` |
| `entered_by_user_id`        | Cashier or authorized actor                         |
| `created_at` / `updated_at` | Audit timestamps                                    |

### Fulfillment invariant

For every requested line:

```text
requested quantity
=
sum of fulfilled quantities across all fulfillment entries
+
unavailable quantity
```

The system must prevent finalization until that statement is true for every requested line.

### Example

```text
Bath Towels
Requested: 6

$3.00 × 2 = $6.00
$4.00 × 2 = $8.00
Unavailable: 2

Resolved: 6 of 6
Actual redeemed value: $14.00
```

This is why the visible cashier field must be **Price Each**, not one total-price field.

It lets the cashier record different prices within the same category without leaving the screen or calculating anything manually.

---

### 8. Furniture Compatibility Rule

The current furniture tables and records remain valid historical records.

Release C must not rewrite or reinterpret old furniture vouchers.

For new Release C Furniture vouchers:

- use the shared requested-line and fulfillment-entry model;
- preserve the existing Furniture catalog as the request source;
- preserve Furniture pricing snapshots;
- preserve current invoice, receipt, correction, and audit expectations;
- create compatibility adapters so old furniture vouchers still display and print correctly.

The existing `wp_svdp_voucher_items` table should be treated as legacy fulfillment history for pre-Release-C Furniture vouchers.

We should not force Household Goods into that table, and we should not require a destructive migration of every existing Furniture item.

---

### 9. Notes and Finalization

### No general voucher Notes fields

No unrestricted Notes field may appear in:

- Clothing request flow
- Furniture request flow
- Household Goods request flow
- Furniture item completion
- Household Goods category fulfillment
- future voucher request types

Existing historical furniture `completion_notes` data is retained for record integrity, but it is no longer written for new actions and should not appear in the normal new fulfillment workflow.

### Limited exception: Internal Finalization Note

Furniture and Household Goods vouchers may have one optional internal voucher-level finalization note.

It is available immediately before finalization.

| Rule                              | Requirement                            |
| --------------------------------- | -------------------------------------- |
| Required?                         | No                                     |
| Who may enter it?                 | Cashier or manager                     |
| Visible to Vincentian?            | No                                     |
| Printed on neighbor receipt?      | No                                     |
| Printed on Conference invoice?    | No                                     |
| Stored with author and timestamp? | Yes                                    |
| Editable after finalization?      | No, ordinary workflow does not edit it |

Recommended voucher fields:

```text
finalization_note
finalization_note_by_user_id
finalization_note_at
```

A later correction process can record a correction audit if a serious issue needs to be addressed. It should not reopen normal editing of finalization notes.

---

### 10. Cashier Status Contract

The underlying technical status may remain stable.

The cashier-facing status language is locked:

| Technical condition          | Cashier display     |
| ---------------------------- | ------------------- |
| Valid, unredeemed, unexpired | **READY TO REDEEM** |
| Finalized / redeemed         | **REDEEMED**        |
| Unredeemed and expired       | **EXPIRED**         |

Furniture and Household Goods may also show internal fulfillment progress inside the voucher detail screen:

| Detail-state label               | Meaning                                            |
| -------------------------------- | -------------------------------------------------- |
| Fulfillment in Progress          | One or more requested lines unresolved             |
| Ready to Finalize                | All requested quantities resolved                  |
| Finalized with Unavailable Items | Finalized, with one or more unavailable quantities |

Those are not replacements for the large cashier-card status labels.

A voucher can be:

```text
READY TO REDEEM
Fulfillment in Progress
```

until its requested lines are resolved.

---

### 11. What This Means for the Builder

The S5 Assisted Builder remains authoritative for visual design and interaction rules.

Release C changes the backend and adds Household Goods into that design.

The builder must support:

```text
Select one, two, or all three voucher types
→ Collect household information once
→ Collect type-specific details
→ Show Delivery only when selected types are delivery eligible
→ Collect Requestor / Organization information
→ Review the full linked request
→ Submit one request group with one to three vouchers
```

## Part 2A: Assisted Builder Public Request Flow

### 1. Purpose

The Voucher Request form becomes one assisted request builder that allows a Vincentian to request any valid combination of:

- Clothing Voucher
- Furniture Voucher
- Household Goods Voucher

One completed submission creates one Voucher Request Group and one, two, or three linked child vouchers.

The Assisted Builder must preserve the S5 visual contract while replacing S5’s former single-voucher submission limitation.

The builder must not use cart, checkout, retail-shopping, or order language.

---

### 2. Governing Interaction Rules

#### 2.1 One request, multiple independently redeemable vouchers

The form creates one request group and up to three child vouchers:

```text
Voucher Request Group
├── Clothing Voucher, when selected
├── Furniture Voucher, when selected
└── Household Goods Voucher, when selected
```

Each child voucher remains independently redeemable, independently expirable, independently auditable, and independently correctable.

#### 2.2 Shared information is collected once

The builder collects household information once and requestor/organization information once.

It must not ask the Vincentian to repeat household information, delivery information, or requestor information for each selected voucher type.

#### 2.3 Dynamic steps are derived, never hardcoded

The builder must calculate visible steps from the selected voucher types and the active delivery capabilities of those voucher types.

Step labels, step numbers, progress indicators, Back behavior, Continue behavior, and Review links must use the same dynamic step model.

No path may use hardcoded text such as:

```text
Step 4 of 7
```

unless the value is generated from the current visible-step array.

#### 2.4 Type-specific data remains in memory when navigating backward

Moving backward and forward through the builder must preserve entered information during the current session.

Examples:

- Furniture selections remain selected when the Vincentian returns from Delivery.
- Household Goods categories and quantities remain selected when the Vincentian returns from Requestor / Organization.
- Delivery address remains populated when the Vincentian returns to Furniture or Household Goods.
- Household fields remain populated when the Vincentian returns to Assistance Needed.

This does not require persistent draft saving in Release C. It applies to the active builder session.

#### 2.5 Removing a selected voucher type requires confirmation when data exists

A Vincentian may return to Assistance Needed and deselect a voucher type.

When that type contains entered data, the system must require confirmation before removing it.

Example:

```text
Remove Household Goods Voucher?

Removing this selection will remove the household-goods categories and quantities entered for this request.

[Keep Household Goods] [Remove Household Goods]
```

Rules:

- Removing Clothing requires confirmation only if the user has passed the Clothing information step.
- Removing Furniture requires confirmation when one or more Furniture items have been selected.
- Removing Household Goods requires confirmation when one or more Household Goods categories or quantities have been entered.
- Removing the final selected voucher type is not allowed. At least one voucher type must remain selected.
- Removing all selected delivery-eligible voucher types clears the group-level delivery selection and address only after confirmation.

#### 2.6 Validation behavior

The Continue button validates only the current step.

The Submit Request action validates all required builder state before creating the request group.

Errors must:

- appear beside the affected field or section;
- use clear plain language;
- receive keyboard focus when the user attempts to continue;
- not rely on color alone;
- preserve entered data.

---

### 3. Canonical Builder Step Order

The canonical order is:

1. Assistance Needed
2. Household Information
3. Clothing Voucher, when selected
4. Choose Furniture Items, when selected
5. Household Goods, when selected
6. Delivery, when at least one selected voucher type is delivery eligible
7. Requestor / Organization
8. Review Request

Clothing always appears before Furniture. Furniture always appears before Household Goods. Delivery always appears after all selected voucher-detail steps.

This order remains consistent regardless of the selection combination.

---

### 4. Exact Step Paths

#### 4.1 Clothing only

```text
1. Assistance Needed
2. Household Information
3. Clothing Voucher
4. Requestor / Organization
5. Review Request
```

#### 4.2 Furniture only

```text
1. Assistance Needed
2. Household Information
3. Choose Furniture Items
4. Delivery
5. Requestor / Organization
6. Review Request
```

The Delivery step appears only when Furniture delivery is enabled in Voucher Type Settings.

#### 4.3 Household Goods only

```text
1. Assistance Needed
2. Household Information
3. Household Goods
4. Delivery
5. Requestor / Organization
6. Review Request
```

The Delivery step appears only when Household Goods delivery is enabled in Voucher Type Settings.

#### 4.4 Clothing plus Furniture

```text
1. Assistance Needed
2. Household Information
3. Clothing Voucher
4. Choose Furniture Items
5. Delivery
6. Requestor / Organization
7. Review Request
```

#### 4.5 Clothing plus Household Goods

```text
1. Assistance Needed
2. Household Information
3. Clothing Voucher
4. Household Goods
5. Delivery
6. Requestor / Organization
7. Review Request
```

#### 4.6 Furniture plus Household Goods

```text
1. Assistance Needed
2. Household Information
3. Choose Furniture Items
4. Household Goods
5. Delivery
6. Requestor / Organization
7. Review Request
```

#### 4.7 Clothing plus Furniture plus Household Goods

```text
1. Assistance Needed
2. Household Information
3. Clothing Voucher
4. Choose Furniture Items
5. Household Goods
6. Delivery
7. Requestor / Organization
8. Review Request
```

#### 4.8 Delivery-disabled variation

When none of the selected voucher types are delivery eligible, Delivery is omitted entirely.

Example, if Household Goods delivery is later disabled:

```text
Clothing + Household Goods

1. Assistance Needed
2. Household Information
3. Clothing Voucher
4. Household Goods
5. Requestor / Organization
6. Review Request
```

---

### 5. Step 1: Assistance Needed

#### 5.1 Purpose

This step allows the Vincentian to select one, two, or all three voucher types.

The existing S5 large-card design remains authoritative.

#### 5.2 Card behavior

Each voucher type is displayed as an independently selectable assistance card:

```text
Clothing Voucher
Furniture Voucher
Household Goods Voucher
```

The cards are multi-select, not radio buttons.

At least one card must be selected to continue.

#### 5.3 Card content

##### Clothing Voucher

```text
Clothing Voucher

Clothing assistance for the household.
```

##### Furniture Voucher

```text
Furniture Voucher

Select needed furniture items. Delivery may be available.
```

##### Household Goods Voucher

```text
Household Goods Voucher

Select up to 10 household-goods categories and enter the quantity needed for each.
Delivery may be available.
```

The delivery language must be configuration-aware.

When delivery is disabled for a voucher type, the card must not imply that delivery is available.

#### 5.4 Selection state

Selected cards must be unmistakable without relying on color alone.

Required selected-state treatment:

- selected indicator icon;
- visible selected label or state text;
- stronger border;
- distinct background treatment;
- keyboard focus treatment;
- accessible selected state.

Example:

```text
✓ Selected
Furniture Voucher
```

#### 5.5 Side summary

On desktop, the S5 side summary panel must show selected voucher types as the user makes selections.

On mobile, the selected summary must remain accessible without consuming excessive vertical space.

The summary is informational. It does not replace the Review Request step.

---

### 6. Step 2: Household Information

#### 6.1 Purpose

This step captures shared household information once for the request group.

#### 6.2 Required fields

The Household Information step includes only:

- First Name
- Last Name
- Date of Birth
- Adults
- Children

It must not include:

- Conference or Organization
- Requestor Name
- Requestor Email
- Notes
- Comments
- Free-text request descriptions

#### 6.3 Existing identity and eligibility rules

Existing rules for household identity, duplicate detection, 90-day eligibility behavior, and date-of-birth handling remain authoritative.

Release C must not weaken or replace these protections merely because the builder now creates multiple vouchers.

The eligibility result must be evaluated separately for each selected voucher type before group creation.

#### 6.4 Household count display

The builder should display a plain-language household total once Adults and Children are present.

Example:

```text
Household size: 5 people
```

This is informational only. It does not alter Clothing, Furniture, or Household Goods rules unless a future configured rule explicitly uses household size.

---

### 7. Step 3: Clothing Voucher

#### 7.1 Purpose

The Clothing Voucher step is informational only.

It confirms the Clothing Voucher that will be created without collecting additional free-form request data.

#### 7.2 Required content

The step must include existing production Clothing Voucher copy, including:

- Clothing Voucher creation confirmation;
- 30-day expiration rule;
- one-visit redemption rule.

#### 7.3 Prohibited content

The Clothing Voucher step must not include:

- Notes;
- Comments;
- request details;
- category selections;
- item quantities;
- editable pricing;
- delivery controls.

#### 7.4 Clothing delivery behavior

Clothing does not independently create a Delivery step unless Clothing delivery is later enabled through Voucher Type Settings.

Current Release C initial configuration keeps Clothing delivery unavailable.

---

### 8. Step 4: Choose Furniture Items

#### 8.1 Purpose

This step uses the S5 Assisted Builder Furniture experience.

It allows the Vincentian to select Furniture catalog items and requested quantities.

#### 8.2 S5 visual and interaction rules

The Furniture step must preserve the S5 design contract:

- Option 2 Assisted Builder visual language;
- large, readable item cards;
- no cart drawer;
- no checkout language;
- no retail-shopping language;
- large quantity controls;
- persistent running Furniture summary;
- desktop side summary;
- mobile-friendly behavior.

#### 8.3 Search field

Furniture search must be materially more visible than a standard text field.

Required treatment:

- visible label;
- strong contrast against the surrounding background;
- high-contrast border;
- generous padding and large tap target;
- clear keyboard-focus state;
- not placeholder-only;
- readable by older users.

Suggested visible label:

```text
Search furniture
```

Suggested helper text:

```text
Search by item name or category.
```

#### 8.4 Furniture category pills

Furniture category navigation uses horizontal filter pills.

The pill row must include dynamic scroll controls.

| Condition                      | Left Arrow | Right Arrow |
| ------------------------------ | ---------- | ----------- |
| Pills fit within visible width | Hidden     | Hidden      |
| Overflow at far left           | Hidden     | Visible     |
| Overflow in the middle         | Visible    | Visible     |
| Overflow at far right          | Visible    | Hidden      |

The controls must recalculate on:

- initial render;
- Furniture catalog render;
- search/filter changes;
- window resize;
- horizontal scroll;
- orientation change.

The arrows must be sufficiently large for older users and must not block category labels.

#### 8.5 Furniture pricing copy

Until future partner-aware pricing work replaces it, use the locked S5 copy:

```text
All furniture prices shown have already been discounted by 50%.
```

The builder must not display:

- Selected item retail maximum;
- Up to value language in review or confirmation;
- the former Mattress/Frame Bundle pricing explanation.

#### 8.6 Furniture selection summary

The summary must show:

- selected item names;
- selected quantities;
- applicable displayed Furniture price information;
- delivery eligibility as part of the group-level Delivery step, not per-item delivery fees.

The summary must not use a cart metaphor.

---

### 9. Step 5: Household Goods

#### 9.1 Purpose

This step allows the Vincentian to select up to 10 granular Household Goods categories and enter the requested quantity for each selected category.

Household Goods is separate from Furniture.

Furniture must never appear as a Household Goods category.

#### 9.2 Screen structure

The Household Goods screen uses the same S5 Assisted Builder visual system as Furniture:

- strong visible search field;
- broad filter-group pills;
- selected-item summary;
- readable category cards or rows;
- large quantity controls;
- desktop side summary;
- mobile-friendly stacked layout.

The screen must not resemble an online shopping cart.

#### 9.3 Household Goods search

Suggested visible label:

```text
Search household goods
```

Suggested helper text:

```text
Search bedding, curtains, pots and pans, towels, and more.
```

The search field follows the same high-contrast and accessibility requirements as Furniture search.

#### 9.4 Browse-group pills

Household Goods filter pills represent broad browsing groups, such as:

```text
All
Kitchen
Bed & Bath
Window Coverings
Cleaning & Home Basics
Small Appliances
Storage & Organization
```

These pills do not represent the voucher’s actual requested categories.

They only help the Vincentian find granular categories.

The same dynamic left/right overflow-arrow behavior required for Furniture category pills applies here.

#### 9.5 Granular category cards

The selectable categories are editable Household Goods catalog entries, such as:

```text
Pots & Pans
Bedding
Bath Towels
Curtains
Laundry Basket
```

Each category card or row must show:

- category name;
- optional managed guidance, when present;
- selected state;
- quantity control once selected;
- configured quantity maximum, when the maximum is greater than zero.

Individual Household Goods category cards must not display unit pricing, retail pricing, shelf pricing, or estimated maximum unit pricing.

The system may use the catalog’s estimated Conference / Partner cost per unit internally to calculate the projected request estimate, but that unit value is not shown as a shopper-facing or Vincentian-facing per-item price.

#### 9.6 Quantity behavior

When a category is selected:

- requested quantity begins at `1`;
- the Vincentian may increase or decrease it with large controls;
- direct numeric keyboard entry must also be supported;
- quantity cannot be zero while the category remains selected;
- reducing quantity to zero removes the category after confirmation or a clear inline action;
- quantity limits apply immediately.

#### 9.7 Household Goods limit display

The screen must show:

```text
Selected categories: 3 of 10
```

The screen must also show the configured voucher-wide quantity behavior.

When a voucher-wide maximum is configured:

```text
Requested units: 12 of 30
```

When the voucher-wide maximum is `0`:

```text
Requested units: 12
```

Category-specific limits must be visible when relevant.

Example:

```text
Bath Towels
Maximum quantity: 6
```

The system must not display a fabricated maximum when that category’s configured maximum is `0`.

#### 9.8 Projected Household Goods estimate

The builder calculates a projected Conference / Partner cost using the issued catalog values:

```text
Requested quantity × estimated Conference / Partner cost per unit
```

The running summary must label this clearly:

```text
Estimated Conference / Partner Cost
```

Supporting copy:

```text
Estimate based on current catalog settings. Actual fulfillment details and final redemption totals are recorded when the voucher is redeemed.
```

This projected value supports informed request review. It does not create a dollar allowance for the neighbor to manage while shopping.

The builder must not present this value as:

- retail price;
- shelf price;
- guaranteed price;
- exact final price;
- amount available for the neighbor to spend.

#### 9.9 Validation

The Vincentian may not continue until:

- at least one Household Goods category is selected;
- no more than 10 categories are selected;
- every selected category has a quantity greater than zero;
- no selected category exceeds its configured quantity maximum, unless its maximum is `0`;
- total requested quantity does not exceed the configured voucher-wide maximum, unless that maximum is `0`.

---

### 10. Step 6: Delivery

#### 10.1 Visibility

Delivery appears once when at least one selected voucher type has delivery enabled.

It is a request-group-level step.

It is not repeated for Furniture and Household Goods separately.

#### 10.2 Eligible-type message

The step must identify which selected voucher types are eligible.

Examples:

```text
Delivery is available for: Furniture
```

```text
Delivery is available for: Household Goods
```

```text
Delivery is available for: Furniture and Household Goods
```

#### 10.3 Delivery choices

The S5 card treatment remains authoritative.

Choices:

```text
No delivery needed
Delivery needed
```

When delivery is not selected, the Review screen must show:

```text
Delivery: Not selected
```

It must never display:

```text
Delivery: $0.00
```

#### 10.4 Delivery fee

The existing delivery fee remains a single request-group-level amount.

For the current configuration, the builder displays:

```text
Delivery: $50.00
```

when delivery is selected.

The fee must be snapshotted when the request group is created.

#### 10.5 Address behavior

When delivery is selected:

- display delivery-address fields;
- preserve the existing warning-only address verification behavior;
- do not create dispatch, routing, scheduling, driver assignment, or delivery-attempt workflows;
- preserve entered address data while the user navigates backward and forward in the builder.

---

### 11. Step 7: Requestor / Organization

#### 11.1 Purpose

This step keeps operational requestor information separate from household information.

#### 11.2 Required fields

- Conference / Organization
- Requestor Name
- Requestor Email

#### 11.3 Prohibited content

This step must not include:

- household counts;
- Date of Birth;
- voucher-specific Notes;
- item selections;
- delivery address;
- general-purpose free-text request fields.

#### 11.4 Validation

The builder must preserve current production validation and Conference eligibility rules.

The selected Conference or Organization must be evaluated against each selected voucher type’s availability.

The builder must not allow a request group to submit if the selected Conference or Organization is not allowed to issue one of the selected voucher types.

---

### 12. Step 8: Review Request

#### 12.1 Purpose

The Review Request step gives the Vincentian one complete view of the request before submission.

It is the final place to correct information without restarting the form.

#### 12.2 Review sections

The screen must show separate, clearly labeled sections for:

1. Household Information
2. Clothing Voucher, when selected
3. Furniture Voucher, when selected
4. Household Goods Voucher, when selected
5. Delivery, when visible
6. Requestor / Organization

Each section includes an Edit action that returns to the correct builder step without discarding other completed information.

#### 12.3 Clothing review content

Show:

- Clothing Voucher selected;
- household size;
- 30-day expiration rule;
- one-visit redemption rule.

No Notes section appears.

#### 12.4 Furniture review content

Show:

- selected Furniture items;
- selected quantities;
- current Furniture price display or estimate according to the existing catalog;
- locked S5 pricing copy;
- no selected retail maximum row;
- no “Up to” value row.

#### 12.5 Household Goods review content

Show:

- selected Household Goods categories;
- requested quantity for each category;
- total requested Household Goods units;
- Estimated Conference / Partner Cost;
- supporting estimate copy.

The Review Request screen must not show Household Goods unit cost values as retail prices, shelf prices, guaranteed prices, or per-item spending allowances.

#### 12.6 Delivery review content

Show:

- Delivery: Not selected, when not selected;
- Delivery: $50.00, when selected;
- eligible voucher types;
- delivery address, when selected.

The Delivery section appears once per request group, never once per child voucher.

#### 12.7 Stock message

When Furniture or Household Goods is selected, the Review Request screen displays the following message:

```text
Stock fluctuates. Items are not guaranteed to be in stock. The neighbor will need to visit the store to see what is currently available.
```

This is informational and prominent.

It does not require the Vincentian to enter a note, comment, or additional acknowledgement field.

#### 12.8 Submission

The Submit Request action must:

- validate all builder state;
- create the request group and all selected child vouchers atomically;
- snapshot selected catalog data, delivery eligibility, delivery fee, and request information;
- prevent partial success.

If any selected child voucher cannot be created, the system must create none of the vouchers and must explain the issue without losing the entered request state.

---

### 13. Confirmation Screen

#### 13.1 Purpose

The confirmation screen confirms that the request group was submitted successfully.

It must accurately reflect the real backend result.

Because Release C creates all selected child vouchers atomically, it may state that the selected voucher requests were created or submitted.

#### 13.2 Required content

The confirmation screen must show:

- request-group confirmation;
- created voucher types;
- voucher identifiers, when appropriate under current privacy rules;
- Conference / Organization;
- delivery status;
- next-step information using existing production approval and issuance rules.

#### 13.3 Stock message repetition

When Furniture or Household Goods was selected, repeat:

```text
Stock fluctuates. Items are not guaranteed to be in stock. The neighbor will need to visit the store to see what is currently available.
```

#### 13.4 Pricing-copy rules

The confirmation must not reintroduce:

- Selected item retail maximum;
- Up to value language;
- obsolete Furniture pricing language;
- a $0.00 delivery display.

For Furniture, preserve the locked transitional statement:

```text
All furniture prices shown have already been discounted by 50%.
```

until the future partner-aware pricing model replaces it.

---

### 14. Builder Accessibility and Responsive Behavior

The builder must preserve S5’s accessibility goals and must be usable by older Vincentians.

Required behavior:

- visible labels on all search fields;
- high contrast for search inputs, controls, and selected states;
- keyboard-operable cards, quantity controls, pills, arrows, Edit links, and navigation buttons;
- visible focus indicators;
- status and selection states not conveyed by color alone;
- large touch targets for step controls, category pills, arrows, quantity controls, and buttons;
- logical screen-reader labels for dynamic counts and validation;
- desktop side summary that does not hide required information;
- mobile summary that remains available without overwhelming the screen;
- no horizontally clipped content except intentionally scrollable pill rows;
- category scroll arrows that remain usable on touch and keyboard devices.

---

### 15. Public Builder Non-Goals

Release C does not add:

- saved request drafts;
- shopper cart behavior;
- online payment;
- point-of-sale integration;
- live store-inventory lookup;
- delivery dispatch;
- delivery route planning;
- driver scheduling;
- driver assignment;
- multiple delivery attempts;
- RouteShyft integration;
- Vincentian free-text Notes;
- item-level fulfillment Notes;
- a per-voucher delivery charge when one request group contains multiple delivery-eligible voucher types.

## Part 2B: Cashier Cards, Fulfillment, Finalization, and Request-Group Context

### 1. Purpose

Part 2B defines how cashiers identify voucher status, open vouchers, fulfill Furniture and Household Goods requests, record actual prices and unavailable items, save progress, finalize redemption, and understand when vouchers belong to the same request group.

The design goal is:

```text
Open voucher
→ enter all fulfillment information on one screen
→ save or finalize
→ done
```

The cashier must not need to move through a separate completion screen for each item, repeatedly open modals, calculate line totals manually, or reconstruct the same voucher in a second workflow.

---

### 2. Cashier Card Status System

#### 2.1 Cashier-facing status labels

The cashier screen uses these exact labels:

| Technical condition              | Cashier-facing label |
| -------------------------------- | -------------------- |
| Valid, unredeemed, and unexpired | READY TO REDEEM      |
| Finalized / redeemed             | REDEEMED             |
| Unredeemed and expired           | EXPIRED              |

The existing technical status value may remain `active`. The interface must translate it to:

```text
READY TO REDEEM
```

The technical status must not be renamed merely to change cashier-facing language.

#### 2.2 Status precedence

The same shared status resolver must be used by the cashier list, voucher detail screen, filters, API responses, and any future reporting display.

Status precedence:

```text
Finalized / Redeemed
→ Expired
→ Ready to Redeem
```

A finalized voucher remains **REDEEMED** even after its original expiration date passes.

#### 2.3 Preserve the existing card architecture

Cashier records remain cards.

Release C does not replace cards with:

- a table;
- a grid;
- a dashboard widget;
- a different browsing model;
- a list requiring an additional click to identify current status.

The objective is to make the existing cards immediately readable at a glance.

#### 2.4 Required card treatment

Every card includes:

1. A prominent left-side status rail.
2. A large, high-contrast status label in the card header.
3. A distinct status icon.
4. A short date-based supporting line.
5. Text and icon treatment that remains understandable without color.

| Cashier status  | Header label    | Supporting line  | Primary action         |
| --------------- | --------------- | ---------------- | ---------------------- |
| Ready to Redeem | READY TO REDEEM | Redeem by [date] | Redeem Voucher         |
| Redeemed        | REDEEMED        | Redeemed [date]  | View Details / Receipt |
| Expired         | EXPIRED         | Expired [date]   | View Details           |

#### 2.5 Visual hierarchy requirement

The status label must be one of the first things visible on the card.

It may not be implemented as:

- a small metadata chip;
- muted body text;
- a low-contrast pill;
- color-only treatment;
- a status hidden below the voucher details.

#### 2.6 Example card structure

```text
│ READY TO REDEEM                         Redeem by June 30, 2026
│ Neighbor Name
│ Household Goods Voucher
│ Conference Name
│
│ 4 requested categories
│                                               [Redeem Voucher]
```

```text
│ REDEEMED                                Redeemed June 14, 2026
│ Neighbor Name
│ Furniture Voucher
│ Conference Name
│
│ 3 requested items
│                                  [View Details] [View Receipt]
```

```text
│ EXPIRED                                 Expired June 5, 2026
│ Neighbor Name
│ Clothing Voucher
│ Conference Name
│
│ 5-person household
│                                                   [View Details]
```

#### 2.7 Detail-state information

Furniture and Household Goods vouchers may show a secondary fulfillment state inside the voucher detail screen.

| Detail-state label               | Meaning                                                      |
| -------------------------------- | ------------------------------------------------------------ |
| Fulfillment in Progress          | One or more requested quantities remain unresolved           |
| Ready to Finalize                | Every requested quantity is resolved                         |
| Finalized with Unavailable Items | Voucher is redeemed, with one or more unavailable quantities |

These labels do not replace the card’s primary status label.

A voucher may therefore show:

```text
READY TO REDEEM
Fulfillment in Progress
```

until it is finalized.

---

### 3. Cashier Voucher Detail Screen

#### 3.1 Purpose

Opening a Furniture or Household Goods voucher takes the cashier to one fulfillment workspace.

That workspace must display:

- voucher identity;
- household identity;
- voucher type;
- request-group context, when applicable;
- redemption deadline;
- requested items or categories;
- all fulfillment inputs;
- real-time totals;
- optional internal finalization note;
- Save Progress action;
- Finalize Voucher action.

#### 3.2 Header content

The header includes:

```text
READY TO REDEEM
Neighbor Name
Voucher Type
Conference / Organization
Redeem by [date]
```

When the voucher belongs to a request group, the header also includes a compact group-context area.

Example:

```text
Part of Request Group RG-10482

Clothing: Ready to Redeem
Furniture: This Voucher
Household Goods: Ready to Redeem
Delivery: Requested
```

The group-context area helps the cashier understand the broader request without requiring all voucher types to be redeemed together.

#### 3.3 Request-group navigation

The cashier may open a sibling voucher from the request-group context.

Rules:

- Opening a sibling voucher does not alter the current voucher.
- Redeeming one voucher does not redeem sibling vouchers.
- Expiration remains independent for each child voucher.
- Group-level delivery information is visible but not editable from ordinary cashier fulfillment unless the existing delivery mechanism already permits that action.
- The cashier must never be required to fulfill all child vouchers in one visit.

---

### 4. One-Screen Fulfillment Workspace

#### 4.1 Core rule

Furniture and Household Goods redemption occur in one screen.

The normal cashier workflow must not require:

- one completion page per item;
- repeated modal dialogs;
- a separate price-entry screen;
- repeated page transitions;
- manual multiplication;
- manual calculation of line totals.

#### 4.2 Requested-line sections

Each requested Furniture item or Household Goods category appears as a visible fulfillment section.

Example:

```text
Bath Towels                                      Requested: 6
```

```text
Dining Chairs                                    Requested: 4
```

Each requested line contains its own fulfillment rows, unavailable quantity control, resolution status, and calculated subtotal.

#### 4.3 Required visible fields

For each requested line, the cashier sees all of the following without opening another screen:

| Field              | Requirement                                            |
| ------------------ | ------------------------------------------------------ |
| Requested quantity | Read-only and always visible                           |
| Price Each         | Cashier-entered reimbursable amount                    |
| Fulfilled quantity | Cashier-entered quantity for that price                |
| Line total         | Automatically calculated                               |
| Item Unavailable   | Visible unavailable quantity control                   |
| Unavailable reason | Appears when unavailable quantity is greater than zero |
| Add another price  | Visible inline control                                 |
| Resolved count     | Always visible                                         |

#### 4.4 Meaning of Price Each

`Price Each` captures the actual amount that will be charged to the Conference or Partner for that fulfilled unit.

It is not a public retail-price display.

The value must use the same financial basis used for Furniture voucher billing.

The implementation must preserve the existing furniture financial model rather than silently changing what a Furniture price means.

#### 4.5 Multiple price rows

The cashier may enter more than one fulfillment row for the same requested item or category.

This is required because similar items may have different actual reimbursable prices.

Example:

```text
Bath Towels                                      Requested: 6

Price Each          Quantity          Line Total
[$3.00         ]    [2           ]    $6.00
[$4.00         ]    [2           ]    $8.00

[+ Add another price]

Item Unavailable: [2]
Reason: [Not currently in stock]

Resolved: 6 of 6
```

The cashier stays on the same screen.

#### 4.6 Add another price behavior

The **Add another price** control:

- adds a new inline fulfillment row beneath the existing rows;
- does not open a modal;
- does not navigate to another screen;
- creates a blank Price Each and Quantity row;
- may be removed while the voucher remains unfinalized;
- is available for both Furniture and Household Goods.

A fulfillment row may be removed only when:

- it contains no saved fulfillment quantity; or
- its quantity has been returned to zero before finalization.

Once a voucher is finalized, fulfillment rows are historical records and may not be ordinarily removed or edited.

#### 4.7 Automatic totals

For every fulfillment row:

```text
Price Each × Fulfilled Quantity = Line Total
```

The system calculates the line total immediately.

The cashier does not calculate:

- multiplication;
- requested quantity remaining;
- category subtotal;
- voucher total;
- Conference or Partner billing total.

#### 4.8 Item Unavailable behavior

The requested line includes a visible **Item Unavailable** field.

For requests with quantity greater than one, it is a numeric quantity field.

For a Furniture item requested at quantity one, it may be presented as a clear checkbox or toggle that sets:

```text
Item Unavailable: 1
```

The interface must still communicate the numeric result.

Examples:

```text
Bath Towels
Requested: 6
Fulfilled: 4
Item Unavailable: 2
```

```text
Dining Table
Requested: 1
Fulfilled: 0
Item Unavailable: 1
```

#### 4.9 Structured unavailable reasons

When Item Unavailable is greater than zero, the cashier must select a structured reason.

The reason list remains administrator-managed.

Examples may include:

- Not currently in stock
- Item condition not suitable
- Item could not be located
- Other approved operational reason

The system must not add an item-level open Notes field.

#### 4.10 Resolution invariant

Every requested line must satisfy:

```text
Requested Quantity
=
Total Fulfilled Quantity Across All Price Rows
+
Item Unavailable Quantity
```

The interface displays progress in plain language.

Example:

```text
Resolved: 4 of 6
```

Example when complete:

```text
Resolved: 6 of 6
```

#### 4.11 Validation behavior

The system must prevent:

- negative quantities;
- zero or negative prices for fulfilled rows;
- fulfilled quantities exceeding requested quantity;
- unavailable quantity exceeding remaining quantity;
- total fulfilled plus unavailable quantity exceeding requested quantity;
- finalization with unresolved requested quantity;
- unavailable quantity without a structured reason;
- empty fulfillment rows being saved as completed entries.

The system must preserve entered data when a validation error occurs.

---

### 5. Furniture and Household Goods Differences

Furniture and Household Goods share the cashier interaction pattern but retain separate catalog and request semantics.

| Behavior                      | Furniture                   | Household Goods                   |
| ----------------------------- | --------------------------- | --------------------------------- |
| Requested source              | Furniture catalog item      | Household Goods catalog entry     |
| Requested display             | Specific item name          | Granular household-goods category |
| Typical quantity              | Usually one, sometimes more | Often more than one               |
| Multiple fulfillment rows     | Allowed                     | Allowed                           |
| Mixed unit prices             | Allowed                     | Allowed                           |
| Partial fulfillment           | Allowed                     | Allowed                           |
| Unavailable quantity          | Allowed                     | Allowed                           |
| Structured unavailable reason | Required when unavailable   | Required when unavailable         |
| Delivery eligibility          | Voucher-type setting        | Voucher-type setting              |

No implementation may force Household Goods into the old Furniture item table merely because both types use a shared cashier experience.

---

### 6. Fulfillment Summary

The fulfillment workspace contains a persistent summary panel.

On desktop, it may appear in the side panel or footer region.

On mobile, it must remain reachable without forcing the cashier to scroll excessively between sections.

#### 6.1 Required summary values

| Summary item                        | Requirement                                                     |
| ----------------------------------- | --------------------------------------------------------------- |
| Requested units                     | Total across all requested lines                                |
| Fulfilled units                     | Total across all fulfillment entries                            |
| Unavailable units                   | Total across all requested lines                                |
| Resolved units                      | Fulfilled plus unavailable                                      |
| Actual redemption total             | Sum of all fulfillment line totals                              |
| Estimated Conference / Partner Cost | Request-time estimate, shown as comparison only when applicable |
| Finalization readiness              | Clear ready or unresolved state                                 |

#### 6.2 Estimate versus actual value

The system must clearly distinguish:

```text
Estimated Conference / Partner Cost
```

from:

```text
Actual Redemption Total
```

The estimate is request-time planning information.

The actual redemption total is calculated from cashier-entered fulfillment rows and is the amount used for the applicable billing, invoice, and reconciliation workflow.

The system must not overwrite the historical estimate when actual fulfillment occurs.

#### 6.3 Example summary

```text
Fulfillment Summary

Requested units: 10
Fulfilled units: 8
Item unavailable: 2
Resolved: 10 of 10

Estimated Conference / Partner Cost: $86.00
Actual Redemption Total: $74.00

Ready to Finalize
```

---

### 7. Save Progress and Finalize Voucher

#### 7.1 Save Progress

The cashier may save partially completed fulfillment work.

The action label is:

```text
Save Progress
```

Save Progress:

- saves current fulfillment rows;
- saves unavailable quantities and reasons;
- saves the current calculation state;
- does not redeem or finalize the voucher;
- leaves the voucher’s primary card status as READY TO REDEEM;
- may show the secondary detail state Fulfillment in Progress;
- does not generate a receipt or invoice.

#### 7.2 Finalize Voucher

The action label is:

```text
Finalize Voucher
```

Finalize Voucher is enabled only when all requested lines are fully resolved.

Before finalization, the cashier sees:

```text
Optional Internal Finalization Note
Visible only to authorized store and program users.
```

The note is optional.

#### 7.3 Finalization confirmation

The finalization action requires a concise confirmation dialog or confirmation area.

It must show:

- actual redemption total;
- fulfilled-unit total;
- unavailable-unit total;
- whether an internal finalization note will be saved;
- finality warning.

Example:

```text
Finalize this voucher?

Actual Redemption Total: $74.00
Fulfilled units: 8
Unavailable units: 2

Once finalized, redemption details cannot be edited through the ordinary cashier workflow.

[Back] [Finalize Voucher]
```

#### 7.4 Finalization effects

Finalization:

- records the redeemed/finalized timestamp;
- records the completing cashier or manager;
- records the optional internal finalization note, when supplied;
- locks normal fulfillment editing;
- changes primary cashier status to REDEEMED;
- generates or enables the appropriate receipt and invoice records;
- retains all fulfillment entries and unavailable reasons as historical records;
- creates necessary audit entries.

---

### 8. Optional Internal Finalization Note

#### 8.1 Scope

The Internal Finalization Note is the only permitted general free-text exception in the new redemption workflow.

It applies to the voucher as a whole, not individual items or Household Goods categories.

#### 8.2 Access and visibility

| Rule                                | Requirement                     |
| ----------------------------------- | ------------------------------- |
| Required                            | No                              |
| Who may enter it                    | Cashier or manager              |
| When available                      | Immediately before finalization |
| Neighbor-facing receipt             | Never display                   |
| Conference / Partner invoice        | Never display                   |
| Voucher detail for authorized staff | Display                         |
| Audit record                        | Include author and timestamp    |
| Normal editing after finalization   | Not allowed                     |

#### 8.3 Historical completion notes

Historical Furniture completion notes remain preserved for record integrity.

They must not appear as an editable field in new fulfillment workflows.

---

### 9. Receipt and Invoice Behavior

#### 9.1 Neighbor-facing receipt

The neighbor-facing receipt must remain concise and dignity-centered.

For Furniture and Household Goods, it shows:

- voucher type;
- fulfilled items or categories;
- fulfilled quantities;
- unavailable quantities, when applicable;
- redemption date;
- any relevant next-step information.

It does not show:

- Internal Finalization Notes;
- staff-only unavailable-reason details;
- technical audit data;
- estimated Conference or Partner cost;
- actual billing totals, unless existing approved receipt policy already requires them.

#### 9.2 Conference / Partner invoice or billing record

The Conference or Partner invoice or billing record includes:

- voucher identifier;
- request-group reference, when applicable;
- fulfilled items or Household Goods categories;
- unit price and fulfilled quantity;
- line totals;
- actual redemption total;
- applicable delivery fee, once per request group when delivery was selected;
- fulfillment and finalization date;
- unavailable quantities where relevant for reconciliation.

It does not include the Internal Finalization Note.

#### 9.3 Group-level delivery fee

When delivery is selected for a request group:

- the fee appears once;
- it is not duplicated across Furniture and Household Goods vouchers;
- billing records must preserve an identifiable connection between the child voucher and the group-level delivery fee;
- the financial system must not accidentally invoice the same delivery fee multiple times.

---

### 10. Expired Vouchers

#### 10.1 Expired voucher behavior

An unredeemed voucher becomes:

```text
EXPIRED
```

when its expiration rule is met.

The cashier may view its details but cannot:

- add fulfillment rows;
- save progress;
- finalize redemption;
- generate a new redemption record through the ordinary workflow.

#### 10.2 Existing correction authority

The established correction authority remains the only path for resolving an expiration-related exception, where existing policy allows it.

Release C must not create a cashier-side workaround that silently reactivates or redeems expired vouchers.

---

### 11. Cashier Workflow Non-Goals

Release C does not add:

- point-of-sale integration;
- automatic importing of register prices;
- live inventory checks;
- barcode scanning;
- SKU-level item capture;
- cashier free-text notes on individual items;
- repeated completion modals;
- a per-item wizard;
- delivery dispatch;
- route planning;
- driver scheduling;
- a mechanism to finalize one request-group child voucher by finalizing all siblings.

## Part 2C: Administration Screens and Configuration Behavior

### 1. Purpose

Part 2C defines how authorized users manage the configurable parts of the Voucher System without requiring a software release.

Release C must make the following operationally configurable:

- Household Goods browse groups
- Household Goods catalog categories
- estimated Conference / Partner reimbursement values
- Household Goods quantity limits
- Household Goods voucher-wide quantity limits
- delivery availability by voucher type
- delivery fee configuration
- structured unavailable reasons

These controls must support normal program management while preserving the historical accuracy of issued vouchers.

---

### 2. Administration Principles

#### 2.1 Configuration controls future requests, never history

Administrative changes apply to new requests created after the change.

They must not alter:

- issued voucher snapshots;
- estimated Conference / Partner cost on an existing voucher;
- request-group delivery eligibility;
- request-group delivery fee;
- fulfillment history;
- invoices;
- receipts;
- audit records;
- prior catalog names or browse-group labels shown on historical vouchers.

#### 2.2 Archive instead of delete

Any configuration record that has been used by a voucher must not be hard-deleted through the ordinary administration interface.

This includes:

- Household Goods browse groups;
- Household Goods catalog categories;
- unavailable reasons;
- delivery settings with historical impact.

The ordinary administrative action is:

```text
Active → Archived
```

Archived records:

- cannot be selected on new requests;
- remain visible on historical vouchers and reports;
- retain their historical names and values;
- may be restored to Active when appropriate.

#### 2.3 No uncontrolled “Other” category

The Household Goods request form must not include an unrestricted:

```text
Other
```

or:

```text
Other household item
```

category with a free-text field.

When a new type of household good needs to be requestable, an authorized administrator adds it to the Household Goods catalog.

That keeps request data consistent, searchable, reportable, and compatible with financial estimates.

#### 2.4 Administrative guidance is not a voucher note

Catalog guidance and unavailable reasons are managed operational data.

They are not a return of unrestricted Notes fields.

No administration screen may create a general-purpose Notes field for:

- Vincentians;
- Furniture items;
- Household Goods request lines;
- fulfillment rows;
- vouchers generally.

The only permitted general free-text exception remains the optional Internal Finalization Note entered by a cashier or manager immediately before voucher finalization.

---

### 3. Administration Navigation

Release C should add or extend a Voucher System administration area with the following sections:

```text
Voucher Settings
├── Voucher Types
├── Delivery Settings
├── Household Goods Browse Groups
├── Household Goods Catalog
├── Household Goods Limits
└── Unavailable Reasons
```

The exact WordPress menu placement may follow the existing administration architecture, but these controls must remain clearly separated by purpose.

The system must not hide core operational settings inside code constants, database scripts, or developer-only configuration.

---

### 4. Permissions and Administrative Authority

Release C must use explicit capabilities rather than relying only on broad role names.

Recommended capability boundaries:

| Capability                              | Purpose                                                                  |
| --------------------------------------- | ------------------------------------------------------------------------ |
| `svdp_manage_voucher_type_settings`     | Manage voucher-type delivery eligibility and related capability settings |
| `svdp_manage_delivery_settings`         | Manage request-group delivery fee configuration                          |
| `svdp_manage_household_goods_catalog`   | Manage Household Goods browse groups and catalog categories              |
| `svdp_manage_household_goods_limits`    | Manage quantity-limit settings                                           |
| `svdp_manage_unavailable_reasons`       | Manage structured unavailable reasons                                    |
| `svdp_view_voucher_configuration_audit` | Review configuration-change history                                      |

The final role mapping must be reconciled to the existing Voucher System roles and capabilities before implementation.

At minimum:

- cashiers cannot change catalog categories, limits, delivery eligibility, or reimbursement estimates;
- Vincentians cannot access administration screens;
- managers may have only the administrative permissions specifically assigned to them;
- system administrators retain the ability to manage configuration;
- auditors may view configuration audit history without editing configuration.

---

### 5. Voucher Type Settings

### 5.1 Purpose

Voucher Type Settings determine which root voucher types are available and whether each type may participate in delivery.

The root types are:

```text
Clothing
Furniture
Household Goods
```

### 5.2 Required configuration fields

For each voucher type, display:

| Field                    | Purpose                                                             |
| ------------------------ | ------------------------------------------------------------------- |
| Voucher Type             | Read-only system identifier and display label                       |
| Enabled for New Requests | Determines whether the type can be selected in the Assisted Builder |
| Delivery Available       | Determines whether the type can trigger the shared Delivery step    |
| Updated By               | Last configuration editor                                           |
| Updated At               | Last configuration timestamp                                        |

Initial Release C configuration:

| Voucher Type    | Enabled for New Requests | Delivery Available |
| --------------- | -----------------------: | -----------------: |
| Clothing        |                       On |                Off |
| Furniture       |                       On |                 On |
| Household Goods |                       On |                 On |

### 5.3 Delivery Available behavior

When `Delivery Available` is enabled for a voucher type:

- that type may trigger the shared Delivery step when selected;
- it may be listed in the Delivery step’s eligible-voucher-type message;
- it may participate in the request-group delivery record.

When `Delivery Available` is disabled:

- new requests containing only that voucher type do not show the Delivery step;
- new mixed requests do not list that type as delivery eligible;
- issued requests remain unchanged.

Changing this setting never:

- removes delivery from an issued request;
- removes the fee snapshot from a historical request group;
- changes a historical voucher’s delivery eligibility;
- creates a new delivery record;
- changes an in-progress cashier redemption.

### 5.4 Voucher-type disable behavior

Disabling a voucher type for new requests:

- removes it from the Assisted Builder selection screen for future sessions;
- does not delete or hide existing vouchers;
- does not prevent cashiers from redeeming already-issued vouchers;
- does not alter request groups already created;
- does not remove historical reporting data.

The system must require confirmation before disabling a currently enabled voucher type.

---

### 6. Delivery Settings

### 6.1 Delivery fee model

Delivery is assessed once per Voucher Request Group.

There is:

```text
One delivery fee
One delivery attempt
One group-level delivery record
```

regardless of whether the request group contains:

- Furniture only;
- Household Goods only;
- Furniture plus Household Goods;
- Clothing plus one or both delivery-eligible voucher types.

### 6.2 Required setting

The Delivery Settings screen includes:

| Field                | Purpose                                                                      |
| -------------------- | ---------------------------------------------------------------------------- |
| Current Delivery Fee | The amount used for newly submitted request groups when delivery is selected |
| Updated By           | Last editor                                                                  |
| Updated At           | Last change timestamp                                                        |

The current production fee may be seeded as the initial value.

The fee must be stored as a non-negative monetary amount.

### 6.3 Snapshot rule

At request-group creation, the system snapshots:

- whether delivery was requested;
- delivery fee;
- selected voucher types;
- eligible voucher types;
- address and address-verification data.

Changing the current fee later affects only new request groups.

The system must never revise an issued request group’s delivery fee automatically.

### 6.4 Confirmation behavior

Changing the delivery fee requires a confirmation message that makes the effective timing clear.

Example:

```text
Update delivery fee?

The new fee will apply only to future voucher request groups.
Existing request groups and invoices will not change.

[Cancel] [Update Fee]
```

---

### 7. Household Goods Browse Groups

### 7.1 Purpose

Browse Groups organize Household Goods categories in the Assisted Builder’s filter-pill navigation.

They are not voucher request lines.

Example Browse Groups:

```text
Kitchen
Bed & Bath
Window Coverings
Cleaning & Home Basics
Small Appliances
Storage & Organization
```

### 7.2 Browse Group list screen

The administration list displays:

| Column                    | Purpose                             |
| ------------------------- | ----------------------------------- |
| Name                      | Displayed filter-pill label         |
| Slug                      | Stable technical identifier         |
| Status                    | Active or Archived                  |
| Sort Order                | Builder display order               |
| Active Catalog Categories | Count of active categories assigned |
| Updated At                | Last change                         |
| Updated By                | Last editor                         |

Required actions:

- Add Browse Group
- Edit Browse Group
- Archive Browse Group
- Restore Browse Group
- Reorder Browse Groups

### 7.3 Browse Group fields

| Field      | Requirement                                                                                                         |
| ---------- | ------------------------------------------------------------------------------------------------------------------- |
| Name       | Required, unique among active groups                                                                                |
| Slug       | System-generated from name by default; must remain stable after creation unless a deliberate migration is performed |
| Sort Order | Required numeric order                                                                                              |
| Status     | Active or Archived                                                                                                  |

### 7.4 Archive safeguards

A Browse Group may not be archived while it contains active Household Goods categories unless the administrator first:

- archives those categories; or
- reassigns them to another active Browse Group.

The system must explain which categories require attention.

### 7.5 Reordering

Browse Group order controls the order of filter pills in the Assisted Builder.

Reordering affects only future and current builder sessions.

It must not alter historical request-line snapshots.

---

### 8. Household Goods Catalog

### 8.1 Purpose

The Household Goods Catalog is the editable registry of granular requestable needs.

Examples:

```text
Pots & Pans
Plates & Bowls
Bedding
Bath Towels
Curtains
Laundry Basket
Broom
Toaster
```

Furniture is never a Household Goods catalog category.

### 8.2 Catalog list screen

The catalog list must support:

- search by category name;
- filter by Browse Group;
- filter by Active or Archived status;
- sort by Browse Group and Sort Order;
- quick visibility of estimated reimbursement amount and quantity cap.

Recommended columns:

| Column                                       | Purpose                                   |
| -------------------------------------------- | ----------------------------------------- |
| Category Name                                | Requestable Household Goods category      |
| Browse Group                                 | Builder grouping                          |
| Estimated Conference / Partner Cost Per Unit | Used for projected request estimate       |
| Quantity Maximum                             | `0` means no category-level limit         |
| Status                                       | Active or Archived                        |
| Sort Order                                   | Builder display order within Browse Group |
| Guidance Present                             | Indicates whether cashier guidance exists |
| Updated At                                   | Last change                               |
| Updated By                                   | Last editor                               |

Required actions:

- Add Household Goods Category
- Edit Household Goods Category
- Archive Household Goods Category
- Restore Household Goods Category
- Reorder within Browse Group

### 8.3 Required catalog fields

| Field                                        | Requirement                                       |
| -------------------------------------------- | ------------------------------------------------- |
| Category Name                                | Required                                          |
| Browse Group                                 | Required active Browse Group                      |
| Estimated Conference / Partner Cost Per Unit | Required non-negative monetary amount             |
| Quantity Maximum                             | Required non-negative integer; `0` means no limit |
| Sort Order                                   | Required within Browse Group                      |
| Active / Archived                            | Required status                                   |
| Cashier Guidance                             | Optional short managed instruction                |
| Created / Updated metadata                   | System-managed                                    |

### 8.4 Estimated Conference / Partner Cost Per Unit

This field replaces the earlier concept of a public estimated maximum unit price.

Its purpose is internal request planning and projected funding exposure.

It represents:

```text
The estimated amount the Conference or Partner may pay
for one fulfilled unit in this Household Goods category.
```

It must not be presented to the Vincentian as:

- retail price;
- shelf price;
- guaranteed price;
- exact final value;
- amount available for the neighbor to spend.

The Assisted Builder uses it only to calculate:

```text
Estimated Conference / Partner Cost
```

for the overall Household Goods selection.

### 8.5 Cashier Guidance

Cashier Guidance is optional managed text attached to a catalog category.

Examples:

```text
Enter each fulfilled towel price separately when prices differ.
```

```text
Use this category for curtains only. Curtain rods are a separate category.
```

Rules:

- visible only to authorized staff in the cashier fulfillment workspace;
- never shown to the Vincentian;
- never printed on a receipt or invoice;
- not editable from a voucher;
- not treated as a Notes field;
- retained as an immutable snapshot when the category is used on a voucher, if guidance was present.

### 8.6 Archive behavior

Archiving a Household Goods category:

- removes it from new Assisted Builder requests;
- retains it on issued vouchers;
- retains historical reporting;
- retains snapshots and fulfillment history;
- does not alter request groups currently in progress or already submitted.

A category that has historical use cannot be deleted through the normal administration interface.

### 8.7 Category edits and issued vouchers

Editing any of the following affects future requests only:

- category name;
- Browse Group;
- estimated Conference / Partner cost;
- quantity maximum;
- cashier guidance;
- sort order;
- active status.

At issuance, the system snapshots the relevant category data onto the requested line.

Historical requested lines retain:

- original category name;
- original Browse Group;
- original estimated Conference / Partner cost per unit;
- original quantity maximum;
- original cashier guidance, when present;
- original sort order.

---

### 9. Household Goods Limits

### 9.1 Fixed selected-category limit

The maximum number of distinct Household Goods categories selected on one Household Goods voucher is:

```text
10
```

This is a locked product rule.

It is not editable through ordinary administration.

The Assisted Builder always displays the current count:

```text
Selected categories: 4 of 10
```

### 9.2 Per-category quantity maximum

Each Household Goods catalog category has a configurable Quantity Maximum.

Rules:

|            Value | Meaning                                      |
| ---------------: | -------------------------------------------- |
|              `0` | No per-category quantity limit               |
| Positive integer | Maximum requested quantity for that category |

The Assisted Builder validates this limit as soon as the Vincentian changes quantity.

The catalog entry’s Quantity Maximum is snapshotted onto the issued voucher request line.

### 9.3 Voucher-wide requested quantity maximum

Household Goods has one global voucher-wide requested quantity maximum.

Required setting:

| Field                            | Purpose                                                      |
| -------------------------------- | ------------------------------------------------------------ |
| Maximum Total Requested Quantity | Maximum units across all selected Household Goods categories |

Rules:

|            Value | Meaning                                                            |
| ---------------: | ------------------------------------------------------------------ |
|              `0` | No voucher-wide quantity limit                                     |
| Positive integer | Maximum combined requested quantity across all selected categories |

This setting applies to future requests.

The value in effect when the voucher is issued must be snapshotted onto the Household Goods voucher.

### 9.4 Limit validation order

When Household Goods quantities are entered, the system validates:

1. no more than 10 selected categories;
2. each selected category quantity is greater than zero;
3. each selected category does not exceed its category Quantity Maximum, unless the maximum is `0`;
4. total requested quantity does not exceed the voucher-wide maximum, unless that maximum is `0`.

The system must show a plain-language explanation of the relevant limit.

Example:

```text
Bath Towels is limited to 6 on one voucher.
```

Example:

```text
This Household Goods voucher is limited to 30 total requested items.
```

---

### 10. Unavailable Reasons

### 10.1 Purpose

Unavailable Reasons provide structured explanations when a requested Furniture item or Household Goods quantity cannot be fulfilled.

They are not free-text Notes.

### 10.2 Unavailable Reason list screen

The list displays:

| Column     | Purpose                             |
| ---------- | ----------------------------------- |
| Reason     | Cashier-facing reason label         |
| Status     | Active or Archived                  |
| Sort Order | Cashier dropdown order              |
| Used By    | Furniture, Household Goods, or Both |
| Updated At | Last change                         |
| Updated By | Last editor                         |

Required actions:

- Add Unavailable Reason
- Edit Unavailable Reason
- Archive Unavailable Reason
- Restore Unavailable Reason
- Reorder Reasons

### 10.3 Required fields

| Field             | Requirement                         |
| ----------------- | ----------------------------------- |
| Reason Label      | Required                            |
| Applies To        | Furniture, Household Goods, or Both |
| Sort Order        | Required                            |
| Active / Archived | Required                            |

Examples:

```text
Not currently in stock
Item condition not suitable
Item could not be located
Other approved operational reason
```

“Other approved operational reason” remains a controlled label. It does not open a required free-text Notes field.

### 10.4 Historical behavior

When a reason is selected at fulfillment:

- its identifier and display label are snapshotted;
- later edits do not rewrite historical vouchers;
- archiving the reason removes it from new selections but preserves history.

---

### 11. Configuration Audit History

### 11.1 Required audit coverage

The system must create a configuration audit event for:

- voucher type enabled/disabled;
- voucher type delivery availability changed;
- delivery fee changed;
- Browse Group added, edited, reordered, archived, or restored;
- Household Goods category added, edited, reordered, archived, or restored;
- Household Goods quantity maximum changed;
- Household Goods voucher-wide quantity maximum changed;
- estimated Conference / Partner cost changed;
- cashier guidance changed;
- Unavailable Reason added, edited, reordered, archived, or restored.

### 11.2 Required audit fields

Each audit record includes:

| Field                      | Purpose                                                                               |
| -------------------------- | ------------------------------------------------------------------------------------- |
| Configuration Area         | What was changed                                                                      |
| Record Type                | Voucher Type, Browse Group, Catalog Category, Limit, Delivery Fee, Unavailable Reason |
| Record Identifier          | Stable internal identifier                                                            |
| Human-readable Record Name | Snapshot at time of change                                                            |
| Field Changed              | Specific field                                                                        |
| Before Value               | Prior value                                                                           |
| After Value                | New value                                                                             |
| Changed By                 | Authorized user                                                                       |
| Changed At                 | Timestamp                                                                             |
| Human Summary              | Plain-language summary                                                                |

Example:

```text
Bath Towels estimated Conference / Partner cost changed
from $3.00 to $4.00 by Jane Smith on July 12, 2026.
The new amount applies to future requests only.
```

### 11.3 Audit visibility

Configuration audit history must be viewable by users with the designated audit capability.

It must not be editable through ordinary administration.

---

### 12. Configuration Validation and Safety Rules

The system must prevent:

- duplicate active Browse Group names;
- duplicate active Household Goods Category names within the same Browse Group;
- category creation without an active Browse Group;
- negative reimbursement estimates;
- negative quantity limits;
- non-integer quantity limits;
- archived category assignment to a new voucher;
- archived unavailable reason selection for a new fulfillment action;
- Browse Group archival while active categories remain assigned;
- deletion of historically referenced configuration records;
- current configuration changes from altering issued voucher snapshots.

The system must provide plain-language validation messages and preserve the administrator’s entered data after a validation error.

---

### 13. Administrative Non-Goals

Release C does not add:

- inventory count tracking;
- live store inventory availability;
- SKU management;
- barcode management;
- automatic retail price imports;
- POS integration;
- automatic partner reimbursement reconciliation;
- bulk catalog import/export;
- role-management redesign;
- free-text “Other Item” requests;
- a new delivery routing or dispatch system;
- a process for retrospectively recalculating issued vouchers after catalog or fee changes.

## Part 3: Acceptance Criteria, Migration, Backward Compatibility, Audit, and Non-Goals

### 1. Purpose

Part 3 converts the Release C product decisions into build-verifiable requirements.

It defines:

- acceptance criteria;
- migration rules;
- backward-compatibility requirements;
- audit requirements;
- financial safeguards;
- request-group creation rules;
- regression coverage;
- accessibility and visual acceptance requirements;
- explicit non-goals.

The purpose is to make the developer handoff testable, not interpretive.

---

### 2. Release C Scope Summary

Release C introduces a major upgrade to the Voucher System.

The release includes:

1. A three-voucher Assisted Builder based on the locked S5 design direction.
2. Request-group support for one, two, or three linked child vouchers.
3. Support for Clothing, Furniture, and Household Goods in any combination.
4. A distinct Household Goods voucher type.
5. An editable Household Goods catalog with browse groups, estimated Conference / Partner cost, quantity limits, and active/archive behavior.
6. Configurable delivery eligibility by voucher type.
7. One group-level delivery fee and delivery record per voucher request group.
8. A single-screen cashier fulfillment workspace for Furniture and Household Goods.
9. Multiple inline price rows per requested item or category.
10. Visible unavailable quantity and structured unavailable reason capture.
11. Optional internal voucher-level finalization note for cashiers or managers.
12. Removal of Furniture item-level completion Notes.
13. No Vincentian Notes fields on any voucher type.
14. Cashier card status display labels: READY TO REDEEM, REDEEMED, EXPIRED.
15. Improved visual distinction between cashier card statuses.
16. Stock availability message on Review and Confirmation screens.
17. Backward compatibility for existing Clothing and Furniture vouchers.

---

### 3. Release C Non-Negotiable Product Rules

The following rules are locked and must be treated as acceptance criteria.

#### 3.1 Voucher types

The system supports these root voucher types:

```text
clothing
furniture
household_goods
```

The legacy value `household` must not be reinterpreted as the new Household Goods voucher type.

Historical `household` records remain associated with the existing Furniture behavior.

#### 3.2 Multi-voucher submission

A Vincentian may submit any of the following combinations:

| Selected voucher types                 | Required support |
| -------------------------------------- | ---------------- |
| Clothing only                          | Yes              |
| Furniture only                         | Yes              |
| Household Goods only                   | Yes              |
| Clothing + Furniture                   | Yes              |
| Clothing + Household Goods             | Yes              |
| Furniture + Household Goods            | Yes              |
| Clothing + Furniture + Household Goods | Yes              |

One submission creates one request group and one child voucher for each selected type.

#### 3.3 One voucher of each type per request group

A request group may include no more than:

- one Clothing Voucher;
- one Furniture Voucher;
- one Household Goods Voucher.

The database must prevent duplicate child vouchers of the same type within one request group.

#### 3.4 Independent child voucher lifecycle

Each child voucher remains independently:

- redeemable;
- expirable;
- correctable;
- auditable;
- printable;
- reportable.

Redeeming one child voucher must not redeem any sibling voucher.

Expiring one child voucher must not expire the entire request group unless the sibling vouchers are independently expired.

#### 3.5 Household Goods is not Furniture

Household Goods is a distinct voucher type.

It must not be implemented as:

- a Furniture category;
- a Furniture item;
- a repurposed legacy `household` voucher;
- a hardcoded list inside the request form.

#### 3.6 Notes boundary

No unrestricted Notes field may appear in:

- Clothing request flow;
- Furniture request flow;
- Household Goods request flow;
- Furniture item fulfillment;
- Household Goods line fulfillment;
- Vincentian-facing request steps.

The only allowed general free-text exception is:

```text
Optional Internal Finalization Note
```

This note is voucher-level only, entered by cashier or manager only, and available immediately before finalization.

#### 3.7 Delivery configuration

Delivery eligibility is configurable by voucher type.

Initial configuration:

| Voucher type    | Delivery available |
| --------------- | -----------------: |
| Clothing        |                Off |
| Furniture       |                 On |
| Household Goods |                 On |

Delivery is recorded once per request group.

There is one delivery fee and one delivery attempt for the entire request group.

#### 3.8 Cashier card labels

Cashier cards must display:

```text
READY TO REDEEM
REDEEMED
EXPIRED
```

The technical status may remain `active`, but the cashier-facing display for a valid, unredeemed, unexpired voucher is:

```text
READY TO REDEEM
```

---

### 4. Request Group Creation Acceptance Criteria

#### 4.1 Atomic creation

Submitting the Assisted Builder must create the request group and all selected child vouchers atomically.

Required behavior:

- If all selected child vouchers can be created, the request group is created.
- If any selected child voucher cannot be created, none are created.
- The user receives a clear validation or system error.
- Entered form data is preserved when possible.
- No partial request group may remain visible to cashiers or administrators as a valid request.

#### 4.2 Shared request snapshot

The request group must snapshot shared information:

- household name;
- date of birth;
- adults;
- children;
- Conference or Organization;
- requestor name;
- requestor email;
- submitted timestamp;
- submitting user or source;
- delivery selection and address when applicable.

#### 4.3 Child voucher snapshots

Each child voucher must retain enough information to be independently rendered, redeemed, corrected, printed, invoiced, and audited.

This is required even when the voucher is accessed outside the request-group view.

#### 4.4 Duplicate prevention

The system must prevent:

- two Clothing vouchers in the same request group;
- two Furniture vouchers in the same request group;
- two Household Goods vouchers in the same request group;
- child vouchers without a valid selected voucher type;
- request group creation with zero child vouchers.

#### 4.5 Eligibility and availability validation

Before creating the request group, the system must validate each selected voucher type against:

- global voucher-type availability;
- Conference or Organization authorization;
- existing eligibility rules;
- duplicate or recent-voucher rules;
- required household identity data;
- required type-specific data.

---

### 5. Assisted Builder Acceptance Criteria

#### 5.1 S5 design authority

The Assisted Builder must follow the locked S5 design direction.

This includes:

- assisted stepper layout;
- card-based assistance selection;
- household and requestor separation;
- strong visual hierarchy;
- desktop side summary;
- mobile-friendly summary behavior;
- S5 styling and spacing direction;
- no cart language;
- no checkout language.

#### 5.2 Dynamic steps

The builder must generate visible steps from selected voucher types and delivery eligibility.

The system must support all seven valid voucher combinations.

Step numbering must update dynamically.

No step number may be hardcoded.

#### 5.3 Data preservation while navigating

When the Vincentian moves backward or forward in the builder, entered data must remain available during the active session.

This includes:

- household information;
- selected assistance types;
- Furniture selections and quantities;
- Household Goods selections and quantities;
- delivery selection and address;
- requestor and organization fields.

#### 5.4 Deselecting a voucher type

If a selected voucher type contains entered data, deselecting it requires confirmation.

Removing that type clears its related data.

The system may not allow the request to have zero selected voucher types.

#### 5.5 Search field visibility

Furniture and Household Goods search fields must be significantly more visible than standard form fields.

Required behavior:

- visible label;
- high contrast;
- strong border;
- large tap target;
- clear focus state;
- not placeholder-only;
- readable for older users.

#### 5.6 Category pill arrows

Furniture and Household Goods category or browse-group pills must show dynamic left and right arrows when pill content overflows.

Rules:

| Pill state            | Left arrow | Right arrow |
| --------------------- | ---------- | ----------- |
| No overflow           | Hidden     | Hidden      |
| Overflow at far left  | Hidden     | Visible     |
| Overflow in middle    | Visible    | Visible     |
| Overflow at far right | Visible    | Hidden      |

Arrows must update on:

- initial render;
- catalog render;
- search changes;
- filter changes;
- scroll;
- resize;
- orientation change.

#### 5.7 Stock message

When Furniture or Household Goods is selected, the Review and Confirmation screens must display:

```text
Stock fluctuates. Items are not guaranteed to be in stock. The neighbor will need to visit the store to see what is currently available.
```

#### 5.8 Household Goods estimate visibility

The builder may display only the projected amount the Conference or Partner may pay.

Required label:

```text
Estimated Conference / Partner Cost
```

The builder must not show Household Goods catalog values as:

- retail price;
- shelf price;
- guaranteed price;
- exact final price;
- amount available for the neighbor to spend.

---

### 6. Household Goods Catalog Acceptance Criteria

#### 6.1 Editable catalog

Household Goods browse groups and catalog categories must be editable by authorized administrators.

They must not be hardcoded into the request form.

#### 6.2 Browse groups

The system must support active and archived browse groups.

Browse groups control the Household Goods filter-pill navigation.

#### 6.3 Catalog categories

Each Household Goods catalog category must support:

- category name;
- browse group;
- active or archived status;
- sort order;
- estimated Conference / Partner cost per unit;
- per-category quantity maximum;
- optional cashier guidance;
- created and updated metadata.

#### 6.4 Quantity limits

The system must enforce:

- maximum 10 selected Household Goods categories;
- per-category quantity maximum, where configured;
- voucher-wide requested quantity maximum, where configured.

A configured value of `0` means no limit.

#### 6.5 Historical snapshots

When a Household Goods voucher is issued, each requested line must snapshot:

- category name;
- browse group;
- estimated Conference / Partner cost per unit;
- category quantity maximum;
- cashier guidance, when present;
- sort order;
- voucher-wide maximum in effect.

Later catalog changes must not rewrite issued voucher lines.

#### 6.6 Archive instead of delete

Configuration records used by issued vouchers cannot be hard-deleted through ordinary administration.

They may be archived.

Archived records must remain available for historical display, audit, reporting, invoices, and receipts.

---

### 7. Delivery Acceptance Criteria

#### 7.1 Delivery step visibility

The Delivery step appears only when at least one selected voucher type has Delivery Available enabled.

It appears once per request group.

#### 7.2 Eligible-type message

The Delivery step must identify which selected voucher types are delivery eligible.

Examples:

```text
Delivery is available for: Furniture
Delivery is available for: Household Goods
Delivery is available for: Furniture and Household Goods
```

#### 7.3 One delivery fee

If delivery is selected, the system applies one delivery fee to the request group.

It must not apply one fee per child voucher.

#### 7.4 Delivery snapshot

At request-group creation, the system snapshots:

- delivery requested or not;
- delivery fee;
- eligible voucher types;
- selected voucher types;
- delivery address;
- address-verification data.

Later configuration changes do not alter issued request groups.

#### 7.5 Delivery non-goals

Release C must not add:

- route planning;
- driver scheduling;
- driver assignment;
- dispatch workflow;
- delivery calendar;
- multiple delivery attempts;
- RouteShyft integration.

---

### 8. Cashier Fulfillment Acceptance Criteria

#### 8.1 One-screen fulfillment

Furniture and Household Goods must be fulfilled through a single voucher fulfillment workspace.

The normal workflow must not require:

- one screen per requested item;
- repeated item-completion modals;
- repeated page transitions;
- manual multiplication;
- manual line-total calculation.

#### 8.2 Requested lines

Each requested Furniture item or Household Goods category appears as a visible requested line.

Each requested line displays:

- requested name;
- requested quantity;
- fulfillment rows;
- unavailable quantity;
- unavailable reason when needed;
- resolved count;
- subtotal;
- add another price control.

#### 8.3 Multiple price rows

The cashier may add multiple price rows for one requested line.

Each row includes:

- Price Each;
- fulfilled quantity;
- calculated line total.

The system calculates:

```text
Price Each × Fulfilled Quantity = Line Total
```

#### 8.4 Unavailable quantity

Each requested line includes a visible unavailable quantity control.

For a single-quantity Furniture item, the UI may present this as a clear checkbox or toggle, but the stored result must still be numeric.

#### 8.5 Structured unavailable reason

If unavailable quantity is greater than zero, the cashier must select a structured unavailable reason.

The system must not add an item-level free-text note.

#### 8.6 Resolution invariant

For each requested line:

```text
Requested Quantity = Sum of Fulfilled Quantities + Unavailable Quantity
```

The voucher cannot be finalized until every requested line satisfies this invariant.

#### 8.7 Save Progress

The cashier may save fulfillment work without finalizing the voucher.

Save Progress:

- saves fulfillment rows;
- saves unavailable quantities;
- saves unavailable reasons;
- does not redeem the voucher;
- does not generate a final receipt or invoice;
- leaves the primary cashier card label as READY TO REDEEM.

#### 8.8 Finalize Voucher

Finalize Voucher is enabled only when all requested lines are resolved.

Finalization:

- records redeemed or finalized timestamp;
- records completing cashier or manager;
- stores optional internal finalization note when provided;
- locks ordinary fulfillment editing;
- changes cashier-facing status to REDEEMED;
- generates or enables receipt and invoice records;
- records audit entries.

#### 8.9 Internal finalization note

The optional Internal Finalization Note:

- applies to the whole voucher;
- is not required;
- is cashier or manager only;
- is not shown to the Vincentian;
- is not printed on neighbor receipt;
- is not printed on Conference or Partner invoice;
- is visible only in authorized internal detail or audit views;
- is not ordinarily editable after finalization.

---

### 9. Cashier Card Acceptance Criteria

#### 9.1 Status visibility

Cashier cards must make status readable at a glance.

Cards must retain the existing card architecture while adding:

- prominent left status rail;
- large high-contrast status label;
- distinct status icon;
- relevant status date;
- non-color status cues.

#### 9.2 Required labels

Cashier-facing labels:

```text
READY TO REDEEM
REDEEMED
EXPIRED
```

#### 9.3 Status precedence

Status precedence:

```text
Redeemed or finalized
then expired
then ready to redeem
```

A redeemed voucher remains REDEEMED even after its original expiration date passes.

#### 9.4 Expired behavior

An unredeemed expired voucher may be viewed but not redeemed through ordinary cashier workflow.

Existing correction authority remains the only path for allowed expiration-related exceptions.

---

### 10. Receipts and Invoice Acceptance Criteria

#### 10.1 Neighbor-facing receipts

Neighbor-facing receipts must not show:

- Internal Finalization Note;
- estimated Conference or Partner cost;
- staff-only unavailable guidance;
- technical audit data.

Receipts may show:

- voucher type;
- fulfilled requested items or categories;
- fulfilled quantities;
- unavailable quantities, where appropriate;
- redemption date;
- appropriate next-step language.

#### 10.2 Conference or Partner billing records

Billing records must include:

- voucher identifier;
- request-group reference when applicable;
- fulfilled items or categories;
- unit price;
- fulfilled quantity;
- line total;
- actual redemption total;
- delivery fee when applicable;
- finalization date.

The group-level delivery fee must appear once.

It must not be duplicated across Furniture and Household Goods child vouchers.

#### 10.3 Estimate versus actual value

The system must preserve both:

```text
Estimated Conference / Partner Cost
```

and:

```text
Actual Redemption Total
```

The estimate is based on request-time catalog snapshots.

The actual total is based on cashier fulfillment entries.

The actual total must not overwrite the historical estimate.

---

### 11. Migration and Backward Compatibility

#### 11.1 Existing Clothing vouchers

Existing Clothing vouchers must continue to:

- display correctly;
- redeem correctly;
- expire according to existing rules;
- appear in cashier views;
- support existing correction and audit behavior.

#### 11.2 Existing Furniture vouchers

Existing Furniture vouchers must continue to:

- display correctly;
- preserve requested items;
- preserve pricing and fulfillment history;
- preserve historical completion notes where already present;
- generate or display existing receipts and invoices;
- support existing correction and audit behavior.

Release C must not destructively migrate old Furniture fulfillment records into the new requested-line and fulfillment-entry model unless a deliberate, tested adapter or migration is created.

#### 11.3 Legacy `household` value

Any existing legacy `household` voucher type value continues to resolve to the historical behavior currently used by the system.

It must not become the new Household Goods voucher type.

New Household Goods vouchers must use:

```text
household_goods
```

#### 11.4 Request group compatibility

Historical vouchers may have no request group.

The system must support both:

- legacy standalone vouchers;
- Release C request-group child vouchers.

Cashier and admin screens must not assume every voucher has a request group.

#### 11.5 Existing delivery data

Existing Furniture delivery behavior and historical delivery data must remain viewable and valid.

Release C introduces request-group-level delivery for new grouped requests.

The implementation must either:

- adapt legacy Furniture delivery records into display compatibility; or
- preserve the legacy display path for historical Furniture vouchers.

It must not erase, duplicate, or reinterpret historical delivery fees.

---

### 12. Audit Acceptance Criteria

#### 12.1 Request and voucher creation audit

The system must audit:

- request group creation;
- child voucher creation;
- selected voucher types;
- selected delivery option;
- delivery fee snapshot;
- Household Goods request-line snapshots;
- Furniture request-line snapshots where applicable.

#### 12.2 Fulfillment audit

The system must audit:

- fulfillment row creation;
- fulfillment row changes before finalization;
- unavailable quantity changes;
- unavailable reason selection;
- Save Progress events;
- Finalize Voucher event;
- internal finalization note creation;
- completing user;
- final redemption totals.

#### 12.3 Configuration audit

The system must audit changes to:

- voucher-type availability;
- delivery availability by voucher type;
- delivery fee;
- Household Goods browse groups;
- Household Goods catalog categories;
- estimated Conference / Partner cost;
- category quantity maximum;
- voucher-wide Household Goods maximum;
- cashier guidance;
- unavailable reasons.

#### 12.4 Audit immutability

Audit entries may not be edited or deleted through ordinary administration.

Corrections must create additional audit entries rather than rewriting prior ones.

---

### 13. Security and Permission Acceptance Criteria

#### 13.1 Vincentian permissions

Vincentians may:

- create voucher requests;
- select authorized voucher types;
- enter household and requestor information;
- select Furniture and Household Goods needs;
- submit request groups.

Vincentians may not:

- enter unrestricted Notes;
- edit Household Goods catalog;
- edit delivery settings;
- edit unavailable reasons;
- redeem vouchers;
- add finalization notes;
- alter cashier fulfillment entries.

#### 13.2 Cashier permissions

Cashiers may:

- view assigned or available vouchers according to existing access rules;
- redeem Clothing vouchers according to existing workflow;
- enter Furniture and Household Goods fulfillment rows;
- save progress;
- mark unavailable quantities;
- select unavailable reasons;
- add optional Internal Finalization Note;
- finalize vouchers if permitted by existing role rules.

Cashiers may not:

- edit Household Goods catalog;
- edit delivery settings;
- edit voucher-type settings;
- alter request-group structure;
- edit finalized fulfillment entries through ordinary workflow.

#### 13.3 Manager and administrator permissions

Managers and administrators may receive expanded permissions according to existing role structure.

Administrative actions must be capability-based, not hardcoded solely by role name.

---

### 14. Testing and Regression Matrix

#### 14.1 Voucher-combination tests

The test suite or manual checkpoint must verify submission for all seven combinations:

1. Clothing only
2. Furniture only
3. Household Goods only
4. Clothing + Furniture
5. Clothing + Household Goods
6. Furniture + Household Goods
7. Clothing + Furniture + Household Goods

Each must verify:

- correct dynamic steps;
- correct request group creation;
- correct child voucher count;
- correct child voucher type values;
- correct delivery visibility;
- correct Review screen sections;
- correct Confirmation screen output.

#### 14.2 Delivery tests

Verify:

- Clothing only does not show Delivery with initial settings.
- Furniture only shows Delivery.
- Household Goods only shows Delivery.
- Furniture + Household Goods shows one Delivery step.
- Clothing + Furniture + Household Goods shows one Delivery step.
- Delivery fee is snapshotted once per request group.
- Disabling Household Goods delivery affects new requests only.
- Issued request groups retain their original delivery snapshot.

#### 14.3 Household Goods catalog tests

Verify:

- active categories appear in builder;
- archived categories do not appear in new requests;
- historical vouchers retain archived category snapshots;
- browse group ordering controls filter-pill order;
- category ordering controls display order;
- estimated Conference / Partner cost calculation works;
- category quantity cap works;
- voucher-wide quantity cap works;
- `0` means no limit.

#### 14.4 Cashier fulfillment tests

Verify:

- one-screen fulfillment displays all requested lines;
- multiple price rows can be added;
- line totals calculate automatically;
- unavailable quantity can be entered;
- unavailable reason is required when unavailable quantity is greater than zero;
- unresolved lines block finalization;
- Save Progress works without redeeming voucher;
- Finalize Voucher works only when all lines are resolved;
- finalization locks ordinary editing;
- internal note is stored and not printed externally.

#### 14.5 Cashier card tests

Verify:

- READY TO REDEEM appears for valid unredeemed vouchers;
- REDEEMED appears for finalized vouchers;
- EXPIRED appears for unredeemed expired vouchers;
- redeemed vouchers do not later display as expired;
- card status is understandable without color;
- status treatment remains visible on desktop and mobile.

#### 14.6 Legacy regression tests

Verify:

- legacy Clothing vouchers still redeem;
- legacy Furniture vouchers still display;
- legacy Furniture receipts and invoices still work;
- historical completion notes remain viewable where already part of record;
- legacy standalone vouchers without request groups do not break cashier or admin screens;
- legacy `household` values are not treated as new Household Goods vouchers.

---

### 15. Accessibility Acceptance Criteria

The builder, cashier screens, and administration screens must meet the following functional accessibility requirements:

- all inputs have visible labels;
- all interactive controls are keyboard accessible;
- focus indicators are visible;
- error messages are text-based and connected to fields;
- status is not communicated by color alone;
- selected states are not communicated by color alone;
- category-pill scroll arrows are keyboard usable;
- search fields have clear labels and focus state;
- card status labels are readable at a glance;
- touch targets are large enough for older users and mobile use;
- dynamic counts are understandable to screen readers.

---

### 16. Visual Acceptance Criteria

#### 16.1 S5 builder fidelity

The Assisted Builder must follow the attached S5 HTML sample and S5 design decisions unless expressly superseded by Release C.

Release C supersedes S5 only where needed to support:

- all three voucher types;
- multi-select assistance type selection;
- request-group creation;
- Household Goods;
- configurable delivery;
- revised review and confirmation behavior.

#### 16.2 Cashier cards

Cashier cards must remain cards.

The status treatment must be obvious enough that a reviewer can identify READY TO REDEEM, REDEEMED, and EXPIRED cards in a quick scan.

#### 16.3 Cashier fulfillment workspace

The fulfillment workspace must look and behave like a single worksheet.

A reviewer must be able to enter fulfillment data for multiple requested lines without opening a separate screen for each line.

---

### 17. Explicit Release C Non-Goals

Release C does not include:

- point-of-sale integration;
- live store inventory lookup;
- barcode scanning;
- SKU tracking;
- stock count management;
- online payment;
- public neighbor self-service portal;
- saved request drafts;
- route planning;
- dispatch;
- driver scheduling;
- driver assignment;
- delivery calendar;
- multiple delivery attempts;
- RouteShyft integration;
- bulk Household Goods catalog import/export;
- automatic partner reimbursement reconciliation;
- retrospective recalculation of issued vouchers after catalog changes;
- unrestricted Vincentian Notes;
- unrestricted item-level cashier Notes;
- reworking the entire role system;
- redesigning cashier cards into a table;
- changing the established 90-day or 30-day eligibility rules unless explicitly specified in another approved change.

---

### 18. Definition of Done for Release C

Release C is not complete until:

1. All seven voucher combinations can be submitted successfully.
2. Request groups and child vouchers are created atomically.
3. Household Goods exists as a distinct voucher type.
4. Household Goods catalog is administratively editable.
5. Household Goods requested lines snapshot catalog values at issuance.
6. Delivery is configurable by voucher type.
7. Delivery is stored and billed once per request group.
8. Furniture and Household Goods use the one-screen fulfillment workspace.
9. Multiple price rows are supported.
10. Partial fulfillment and unavailable quantities are supported.
11. Finalization is blocked until requested quantities are resolved.
12. Internal Finalization Note is voucher-level only and hidden from external documents.
13. Furniture item-level Notes are removed from new workflow.
14. Cashier cards clearly show READY TO REDEEM, REDEEMED, and EXPIRED.
15. Stock availability message appears in Review and Confirmation.
16. Legacy Clothing and Furniture vouchers remain functional.
17. Configuration changes are audited.
18. Fulfillment actions are audited.
19. Receipt and invoice behavior is verified.
20. Accessibility and visual acceptance checks are completed.
