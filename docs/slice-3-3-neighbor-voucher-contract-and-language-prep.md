# Slice 3.3 — Neighbor Voucher Contract + Language Prep

## Objective

Define the canonical neighbor-facing voucher document structure and introduce a language abstraction layer without changing current system behavior.

---

## Contract: Neighbor Voucher Document

A valid Neighbor Voucher MUST include:

1. Header
   - Title: "Furniture Voucher"
   - Subtext: "Bring this with you to the store"

2. Redemption Rule (from helper)
   - MUST use SVDP_Voucher_Rules::get_redemption_rule_text()

3. Neighbor Information
   - Name
   - DOB

4. Conference Information
   - Conference name

5. Item List (requested items only)
   - Item name
   - Category

6. Delivery Indicator
   - "Pickup" OR "Delivery to [address]"

7. Footer Guidance
   - Plain language instruction:
     "Present this voucher at the store to receive your items."

---

## Language Layer (Phase 1)

Introduce:

class SVDP_Language {

    public static function get($key) {
        $strings = [
            'voucher_title' => 'Furniture Voucher',
            'voucher_instruction' => 'Bring this with you to the store',
            'voucher_footer' => 'Present this voucher at the store to receive your items.',
        ];

        return $strings[$key] ?? '';
    }
}

Rules:
- English only
- No switching UI yet
- All new templates MUST use this instead of hardcoded strings

---

## Constraints

- Do NOT modify existing receipt
- Do NOT introduce translations yet
- Do NOT wire into SMS/email
- Do NOT change voucher creation flow

---

## Done Criteria

- Neighbor voucher template exists
- Uses rule helper
- Uses language helper
- Renders without errors