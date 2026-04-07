# Slice B — Neighbor Identity + Delivery Preferences

## Objective
Introduce persistent, reusable neighbor delivery preferences independent of vouchers.

## Scope
This slice is responsible ONLY for:
- storing delivery preferences
- retrieving preferences by neighbor identity
- enabling future delivery workflows

## Out of Scope
- delivery sending
- SMS/email execution
- tokenized access
- OTP/security
- UI/UX polish
- snapshot system

---

## Identity Strategy

We do NOT introduce a full neighbor entity system.

We use a lookup key derived from:
- first_name
- last_name
- dob

### Lookup Key
normalized(first_name + last_name + dob)

Normalization:
- lowercase
- trim whitespace
- collapse spaces
- hash (sha1 or md5)

Field:
neighbor_lookup_key

---

## Database Table

Table: wp_svdp_neighbor_delivery_preferences

Fields:
- id
- neighbor_lookup_key (indexed)

- first_name
- last_name
- dob

- preferred_language

- is_opted_in
- auto_send_enabled

- email_enabled
- email_address

- sms_enabled
- phone_number

- notifications_paused

- created_at
- updated_at

---

## Behavior

### Read
- compute lookup key from voucher data
- retrieve preferences if they exist

### Write
- create or update preferences after voucher creation
- overwrite existing record by lookup key

---

## Design Constraints

- Do NOT modify voucher schema
- Do NOT store preferences inside vouchers
- Do NOT collect contact info during request form
- Do NOT introduce authentication
- Do NOT couple to delivery manager yet

---

## Checkpoints

### Checkpoint 1 — Data Layer
- create table
- create class
- implement lookup key
- implement CRUD

### Checkpoint 2 — Voucher Integration
- compute lookup key from voucher
- read preferences
- write/update preferences

### Checkpoint 3 — Cashier Hooks
- expose get/set methods for UI layer
- prepare for delivery integration

---

## Done When
- preferences persist across vouchers
- lookup works deterministically
- no UI changes required yet