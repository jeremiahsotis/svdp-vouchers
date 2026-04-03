# Slice A — Delivery Framework Foundation

## Branch
codex/slice-a-delivery-framework

## Objective
Introduce a delivery abstraction layer for voucher communications without changing existing behavior.

This slice creates the foundation for:
- email delivery
- sms delivery
- future delivery methods

It must NOT change current voucher flows or UI.

## Authoritative Files
- includes/
- svdp-vouchers.php
- includes/class-settings.php

## Required New Files

### Core
- includes/class-delivery-manager.php
- includes/class-delivery-method-registry.php
- includes/class-delivery-provider-registry.php

### Interfaces
- includes/interfaces/interface-delivery-method.php
- includes/interfaces/interface-delivery-provider.php

### Methods
- includes/delivery/class-delivery-method-email.php
- includes/delivery/class-delivery-method-sms.php

### Providers
- includes/providers/class-email-provider-wp-mail.php
- includes/providers/class-sms-provider-telnyx.php

## Behavior

### Delivery Manager
Expose:
- send($method, $recipient, $payload)

Internally:
- resolve method
- resolve provider
- call provider

### Email Method
- uses wp_mail provider
- sends LINK (no PDF)

### SMS Method
- uses Telnyx provider
- stub implementation acceptable

## Settings

Store in existing settings system:
- delivery_email_provider
- delivery_sms_provider
- delivery_sms_telnyx_api_key
- delivery_sms_telnyx_from_number

## Constraints
- Do not modify existing voucher flows
- Do not modify cashier UI
- Do not remove PDF logic
- Do not remove print logic
- Do not send real SMS yet if not ready
- Do not introduce new frameworks

## Acceptance Criteria
- delivery manager can be instantiated
- email method sends a basic message via wp_mail
- sms method resolves provider without crashing
- providers are swappable via settings