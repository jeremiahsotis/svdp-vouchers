# Refusal Envelope

## Rule
All failures must return a structured response.

```json
{
  "ok": false,
  "error_code": "",
  "category": "",
  "reason": "",
  "next_step": ""
}
```

## Forbidden
- raw exceptions
- unstructured failures
- ambiguous error messages
