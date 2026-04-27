# Checkpoint 5.1 — Address Verification Foundation

## Objective

Add non-blocking address verification infrastructure across:
- DB
- Backend provider abstraction
- REST endpoint
- Frontend integration (minimal)

---

## Step 1 — Database Migration

File: `includes/class-database.php`

Add columns to `svdp_furniture_voucher_meta`:

```
delivery_lat DECIMAL(10,7) NULL
delivery_lng DECIMAL(10,7) NULL
delivery_verified TINYINT(1) DEFAULT 0
delivery_verification_source VARCHAR(50) NULL
delivery_verification_confidence DECIMAL(5,2) NULL
delivery_normalized_address TEXT NULL
```

Rules:
- Add via `dbDelta`
- Backward compatible
- No data loss

---

## Step 2 — Provider Interface

Create:

```
includes/address/class-address-provider-interface.php
```

```
interface SVDP_Address_Provider_Interface {
    public function search($query);
    public function normalize($result);
}
```

---

## Step 3 — Nominatim Provider

Create:

```
includes/address/class-address-provider-nominatim.php
```

Requirements:
- Use `wp_remote_get`
- Add headers:
  - User-Agent
- Rate limit protection (basic throttle)
- Parse JSON response

Return structure:
```
[
  'display' => string,
  'lat' => float,
  'lng' => float,
  'confidence' => float,
  'raw' => array
]
```

---

## Step 4 — REST Endpoint

Register:

```
/svdp/v1/address/search
```

Handler:
- sanitize `q`
- call provider
- return normalized results

File:
```
includes/class-rest-routes.php (or equivalent)
```

---

## Step 5 — Frontend Hook

File:
```
public/js/voucher-request.js
```

Add:
- listener on `deliveryLine1`
- debounce (300ms)
- fetch suggestions from REST endpoint

DO NOT:
- block submission
- override manual input automatically

---

## Step 6 — Hidden Fields

Form additions:

```
<input type="hidden" name="deliveryLat">
<input type="hidden" name="deliveryLng">
<input type="hidden" name="deliveryVerified">
<input type="hidden" name="deliveryNormalized">
```

---

## Step 7 — Save Logic

File:
```
includes/class-voucher.php
```

Modify:

```
create_voucher()
```

Behavior:

IF lat/lng present:
- store in new columns
- set verified = 1

ELSE:
- leave NULL
- verified = 0

---

## Validation Rules

- Address fields still required when delivery = true
- Verification NOT required

---

## Test Cases

### Case 1 — Verified Address
- Select suggestion
- Submit
- Expect lat/lng stored

### Case 2 — Manual Address
- Type manually
- Submit
- Expect success, no lat/lng

### Case 3 — API Failure
- Disable endpoint
- Submit
- Expect success

---

## Done When

- DB columns exist
- Endpoint returns results
- Frontend fetches suggestions
- Submission unaffected
- Verified data stored correctly
