You are executing inside the SVdP Vouchers plugin repo on branch `codex/slice-b-neighbor-preferences`.

## Objective
Implement Neighbor Delivery Preferences data layer.

## RULES
- DO NOT modify voucher schema
- DO NOT modify request form
- DO NOT modify cashier UI
- DO NOT implement delivery sending
- DO NOT implement tokens or OTP
- DO NOT integrate with delivery manager yet
- DO NOT remove Vincentian logic yet

## TASK
Implement ONLY Checkpoint 1:
- create preferences table
- create lookup key strategy
- create CRUD access layer

## REQUIRED TABLE

wp_svdp_neighbor_delivery_preferences

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

## REQUIRED FILE

includes/class-neighbor-delivery-preferences.php

## REQUIRED METHODS

- build_lookup_key($first_name, $last_name, $dob)
- normalize_identity_fields($first_name, $last_name, $dob)
- get_by_lookup_key($lookup_key)
- upsert_preferences($data)

## DATABASE

- integrate into existing schema upgrade flow in class-database.php
- bump schema version
- ensure idempotent creation

## LOOKUP KEY RULES

- lowercase
- trim
- collapse whitespace
- hash result

## STOP CONDITION

Stop when:
- table exists
- class exists
- lookup key generation works
- CRUD methods work

DO NOT:
- connect to delivery manager
- add UI
- add API routes
- modify voucher logic

## REQUIRED OUTPUT

- files changed
- schema version change
- summary of behavior
- any assumptions made