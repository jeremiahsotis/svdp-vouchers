You are executing inside the SVdP Vouchers plugin repo on branch `codex/slice-a-delivery-framework`.

You are continuing **Slice A — Delivery Framework Foundation**.

## Current State Assumption
Checkpoint 1 is already complete:
- `includes/interfaces/interface-delivery-method.php` exists
- `includes/interfaces/interface-delivery-provider.php` exists
- `includes/class-delivery-manager.php` exists
- those files are required in `svdp-vouchers.php`

Before making changes, verify that this is true in the current branch state. If not, stop and report the mismatch.

## Objective for This Checkpoint
Implement **Checkpoint 2 only**:
- add dedicated delivery method registry
- add dedicated delivery provider registry
- add email delivery method
- add wp_mail email provider
- prove a basic link-style email can be sent through the delivery manager

## Authoritative Existing Files
- `includes/class-delivery-manager.php`
- `includes/interfaces/interface-delivery-method.php`
- `includes/interfaces/interface-delivery-provider.php`
- `svdp-vouchers.php`
- `includes/class-settings.php`

## Required New Files
Create:
- `includes/class-delivery-method-registry.php`
- `includes/class-delivery-provider-registry.php`
- `includes/delivery/class-delivery-method-email.php`
- `includes/providers/class-email-provider-wp-mail.php`

## Locked Product Rules
- Do not change cashier UI
- Do not change voucher detail templates
- Do not remove or alter existing PDF/print code
- Do not alter current neighbor document routes/actions
- Email delivery in this checkpoint is **link-based**, not PDF-based
- This checkpoint is infrastructure only
- Keep changes additive and local

## Required Behavior

### Delivery Method Registry
Provide a class that can register and return delivery methods by slug.

### Delivery Provider Registry
Provide a class that can register and return delivery providers by slug.

### Email Delivery Method
Slug: `email`

Responsibilities:
- resolve provider slug from payload or fallback logic
- normalize payload for email delivery
- expect a link-style payload, not an attachment/PDF payload

Expected normalized payload shape:
- subject
- message
- headers (optional)
- provider override if present

### wp_mail Provider
Slug: `wp_mail`

Responsibilities:
- send via `wp_mail()`
- accept normalized payload from the email method
- return a success/failure result cleanly
- do not assume voucher-specific behavior

### Delivery Manager Integration
Update `SVDP_Delivery_Manager` to use the dedicated registry classes instead of holding raw arrays directly, if that can be done cleanly without breaking Checkpoint 1 behavior.

If a lighter-touch approach is safer:
- keep public behavior stable
- internally delegate to the new registries

## Suggested Default Behavior
If no provider override is supplied in payload, email method may default to `wp_mail` for now.

Do not add settings-driven provider selection yet unless absolutely required for the class shape.
Settings integration is Checkpoint 4.

## In Scope
- registry classes
- email method class
- wp_mail provider class
- wiring/bootstrap requires
- a minimal proof path that the delivery manager can send an email through the email method/provider stack

## Explicitly Out of Scope
- SMS provider implementation
- Telnyx logic
- settings persistence/UI
- voucher preferences
- snapshots
- token/OTP/security
- cashier actions
- replacing current neighbor voucher flows

## Required Execution Steps
1. Read the authoritative files first.
2. Verify Checkpoint 1 is present.
3. Implement only Checkpoint 2.
4. Keep changes small and additive.
5. Stop immediately after Checkpoint 2 is complete.

## Required Stop Condition
Stop when:
- registry classes exist
- email method exists
- wp_mail provider exists
- the delivery manager can send a basic link-style email through the new abstraction

Do not continue into SMS, settings, or UI work.

## Required Output
When you stop, provide:
- exact files changed
- concise summary of behavior added
- any assumptions made
- any regressions or risks noticed