# Slice 5 — Address Verification (Foundation Only)

## Decision

Implement address verification as **non-blocking enrichment**, not validation.

- Submission MUST succeed with unverified or manual addresses
- Verification augments data (lat/lng, normalized form, confidence)
- No service-area enforcement in this slice
- Provider must be abstracted (first provider: OpenStreetMap Nominatim via backend proxy)

---

## Scope (IN)

### Data Layer
Add verification metadata + geospatial fields to furniture meta table.

### Backend
- Provider abstraction
- Nominatim provider implementation
- REST endpoint for lookup (proxy only)

### Frontend
- Autocomplete when `deliveryRequired = true`
- Hidden fields for verified data
- Manual entry fallback always allowed

---

## Scope (OUT)

- Service area enforcement
- Address blocking
- Map display
- Distance calculations
- Multi-provider switching UI
- Delivery eligibility logic

---

## Core Principles

1. **Never block submission**
2. **Always preserve manual input**
3. **Verification is advisory**
4. **Provider must be swappable**
5. **Backend owns API calls (no direct browser calls)**

---

## Data Model Changes

Table: `svdp_furniture_voucher_meta`

Add:

- `delivery_lat` DECIMAL(10,7) NULL
- `delivery_lng` DECIMAL(10,7) NULL
- `delivery_verified` TINYINT(1) DEFAULT 0
- `delivery_verification_source` VARCHAR(50) NULL
- `delivery_verification_confidence` DECIMAL(5,2) NULL
- `delivery_normalized_address` TEXT NULL

---

## Backend Architecture

### Interface

```
SVDP_Address_Provider_Interface
- search($query)
- normalize($result)
```

### Implementation

```
SVDP_Address_Provider_Nominatim
```

Constraints:
- Backend proxy only
- Custom User-Agent required
- Rate limit (1 req/sec safe default)
- No client-side direct calls

---

## REST Endpoint

```
GET /svdp/v1/address/search?q=...
```

Response:
```
[
  {
    "display": "123 Main St, Fort Wayne, IN 46802",
    "lat": 41.08,
    "lng": -85.13,
    "confidence": 0.92,
    "raw": {...}
  }
]
```

---

## Frontend Behavior

### Trigger
ONLY when:
- voucherType = furniture
- deliveryRequired = true

### UX Flow

1. User types address
2. Autocomplete suggestions appear
3. User can:
   - select suggestion → fills + stores verified data
   - ignore → manual entry remains valid

### Hidden Fields

- `deliveryLat`
- `deliveryLng`
- `deliveryVerified`
- `deliveryNormalized`

---

## Save Behavior

In `create_voucher`:

IF verified:
- store lat/lng + metadata

ELSE:
- store manual fields only
- set `delivery_verified = 0`

---

## Risks

### Risk: False confidence in verification
Mitigation:
- store confidence score
- never enforce in this slice

### Risk: API dependency
Mitigation:
- abstraction layer

### Risk: UX confusion
Mitigation:
- explicit “optional” behavior

---

## Done Criteria

- Address lookup endpoint returns results
- Autocomplete works when delivery toggled
- Manual entry still submits successfully
- Lat/lng stored when available
- No submission failures due to verification
