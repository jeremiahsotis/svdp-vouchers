# Slice 5.2.4 — Populate Structured Address Fields from Verified Provider Selection

## Purpose

When a user selects a verified address suggestion, the form must populate the full delivery address fields, not only store lat/lng and normalized metadata.

The user must not be asked to verify an address and then manually re-enter city, state, and ZIP.

## Scope

Modify only:

- includes/address/class-address-provider-interface.php
- includes/address/class-address-provider-nominatim.php
- public/js/voucher-request.js

No schema changes.
No REST route changes.
No service area enforcement.
No blocking unverified addresses.

## Authoritative Execution Sources

- docs/slice-5-address-verification-implementation-brief.md
- docs/slice-5.2-address-verification-hybrid-display-implementation-brief.md
- docs/slice-5.2.4-structured-address-selection-checkpoint.md

## Required Behavior

When a user selects an address suggestion:

- deliveryLine1 is populated
- deliveryCity is populated when available
- deliveryState is populated when available
- deliveryZip is populated when available
- deliveryLat is populated
- deliveryLng is populated
- deliveryVerified is set to 1
- deliveryNormalized is populated
- deliveryLine2 is preserved and never overwritten

## Provider Contract Update

Update `includes/address/class-address-provider-interface.php` documentation so provider results may include:

- label
- normalized_address
- latitude
- longitude
- source
- confidence
- line1
- city
- state
- zip

## Nominatim Provider Update

In `includes/address/class-address-provider-nominatim.php`, use `$row['address']` from Nominatim to return structured fields.

Build:

- `line1`
  - prefer house number + road
  - fallback to name/display_name when needed
- `city`
  - prefer city
  - fallback to town
  - fallback to village
  - fallback to municipality
  - fallback to county only if no better locality exists
- `state`
  - prefer state
- `zip`
  - prefer postcode

Do not fail the result if one structured field is missing. Return what is available.

## Frontend Update

In `public/js/voucher-request.js`, update `selectAddressSuggestion(index)`.

Required:

- Preserve existing `deliveryLine2`
- Set line1/city/state/zip from structured fields
- Set lat/lng/verified/normalized
- Fix typo if present: `normalized_addres` must become `normalized_address`
- Hide suggestions
- Keep existing “Address verified” message behavior

Suggested logic:

```js
const existingLine2 = form.find('[name="deliveryLine2"]').val();

form.find('[name="deliveryLine1"]').val(suggestion.line1 || suggestion.address_line_1 || suggestion.label || '');
form.find('[name="deliveryCity"]').val(suggestion.city || '');
form.find('[name="deliveryState"]').val(suggestion.state || '');
form.find('[name="deliveryZip"]').val(suggestion.zip || suggestion.postcode || '');
form.find('[name="deliveryLine2"]').val(existingLine2);

form.find('[name="deliveryLat"]').val(suggestion.latitude ?? '');
form.find('[name="deliveryLng"]').val(suggestion.longitude ?? '');
form.find('[name="deliveryVerified"]').val('1');
form.find('[name="deliveryNormalized"]').val(suggestion.normalized_address || suggestion.label || '');
```

## Important Constraint

Do not trigger stale verification clearing after programmatically filling the fields from a selected suggestion.

If setting field values triggers input/change handlers, set verification metadata after setting visible address fields.

## Manual Validation

1. Toggle delivery on.
2. Type a searchable address.
3. Select provider suggestion.
4. Confirm:
   - Address Line 1 populated
   - City populated
   - State populated
   - ZIP populated
   - Address verified message visible
5. Add Address Line 2.
6. Confirm:
   - Verified state remains
   - Address Line 2 remains
7. Edit Address Line 1 manually.
8. Confirm:
   - Verified state clears
   - lat/lng clear
   - normalized clears
9. Submit unverified manually entered address.
10. Confirm:
   - submission still succeeds

## Done Definition

This checkpoint is complete when selecting a provider suggestion produces a complete usable delivery address without requiring manual city/state/ZIP entry.