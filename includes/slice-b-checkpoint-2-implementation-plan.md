You are executing inside the SVdP Vouchers plugin repo on branch `codex/slice-b-neighbor-preferences`.

You are continuing **Slice B — Neighbor Identity + Delivery Preferences**.

## Current State Assumption
Checkpoint 1 is already complete:
- the `wp_svdp_neighbor_delivery_preferences` table exists
- `includes/class-neighbor-delivery-preferences.php` exists
- lookup key generation exists
- CRUD methods exist

Before making changes, verify that this is true in the current branch state. If not, stop and report the mismatch.

## Objective
Implement **Checkpoint 2 only**:
- integrate neighbor delivery preference lookup with voucher data
- compute lookup keys from voucher identity fields
- add helper methods to retrieve preferences for a voucher
- add helper methods to upsert preferences from voucher-adjacent data
- keep this backend-only

## RULES
- DO NOT modify request form UI
- DO NOT modify cashier UI
- DO NOT implement delivery sending
- DO NOT integrate with delivery manager yet
- DO NOT implement tokens or OTP
- DO NOT build snapshot logic
- DO NOT remove existing Vincentian UI fields yet
- DO NOT create a full neighbor master-record system

## AUTHORITATIVE FILES
- includes/class-neighbor-delivery-preferences.php
- includes/class-voucher.php
- includes/class-database.php

## REQUIRED BEHAVIOR

### Voucher Preference Lookup
Add a helper path that can derive neighbor preferences from existing voucher identity:
- first_name
- last_name
- dob

### Voucher Preference Access Methods
Implement conservative helper methods, such as:
- get_preferences_for_voucher($voucher)
- get_preferences_for_voucher_id($voucher_id)
- upsert_preferences_for_voucher($voucher, $preference_data)

Exact method names may vary if the repo’s current class patterns strongly suggest a better naming convention.

### Identity Source
Use voucher identity fields only:
- first name
- last name
- DOB

Do not introduce a separate neighbor table.

### Preference Source of Truth
Neighbor delivery preferences are now the reusable source for:
- preferred language
- opt-in state
- auto-send
- method-specific contact fields

Do not use Vincentian email as the reusable neighbor preference source.

## IN SCOPE
- voucher-to-preference lookup integration
- voucher-to-preference upsert integration
- backend helper methods only

## OUT OF SCOPE
- UI
- sending
- delivery methods/providers
- tokenized access
- OTP/security
- snapshots/live status
- unsubscribe flow

## STOP CONDITION
Stop when:
- voucher code can retrieve preferences by voucher identity
- voucher code can upsert preferences by voucher identity
- no UI changes have been made
- no delivery behavior has been added

## REQUIRED OUTPUT
Return:
- exact files changed
- exact methods added/changed
- summary of voucher integration behavior
- assumptions or risks noticed