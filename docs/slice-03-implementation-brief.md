# Slice 03 — Always-On Neighbor-Facing Voucher PDF/Email + EN/ES/Burmese

## Branch
codex/slice-03-neighbor-voucher-pdf-email-i18n

## Objective
Create an always-on, shared, neighbor-facing voucher document system for all voucher types, with:
- print/open access for all vouchers
- email delivery for all vouchers
- PDF generation as a new internal plugin dependency
- multilingual support for English, Spanish, and Burmese
- shared document architecture instead of voucher-type-specific duplication

This slice must remove any admin-toggle dependency for printable/email voucher availability.

## Authoritative Execution Sources
- includes/class-furniture-receipt.php
- includes/class-voucher.php
- includes/class-invoice.php
- includes/class-cashier-shell.php
- public/templates/cashier/partials/voucher-detail.php
- public/templates/cashier/partials/voucher-detail-furniture.php
- public/templates/documents/furniture-receipt.php
- public/js/cashier-shell.js
- multilingual/document recon already reviewed
- PDF recon already reviewed

## Locked Product Decisions
- Print/email feature is available for all voucher types, always
- Admin toggle must be removed or bypassed so it no longer controls availability
- Email must send a PDF
- PDF generation is a new internal dependency added explicitly in this slice
- Languages:
  - English
  - Spanish
  - Burmese
- Neighbor-facing voucher documents must show only the approved-amount framing, not internal finance detail
- Neighbor-facing pre-fulfillment voucher language:
  - “Estimated amount approved for this voucher”
- Do not show:
  - item total
  - delivery fee amount
  - Conference portion label
  - store share
  - neighbor payment language
- Show:
  - neighbor identifying information as already appropriate in current system
  - requested items
  - whether delivery is included
  - estimated amount approved for this voucher

## In Scope
- Add internal PDF dependency
- Create shared neighbor-facing voucher document service
- Create shared language dictionary/service
- Create shared neighbor-voucher template
- Add open/print and email actions for all vouchers in cashier/detail UI
- Generate PDF and attach to email
- Support EN/ES/Burmese rendering

## Out of Scope
- changing invoice semantics
- cashier fulfillment workflow redesign
- Conference coverage math redesign
- unrelated receipt/invoice refactors beyond what is necessary to support shared document flow

## Required Architecture

### New Internal Dependency
Add TCPDF as an internal plugin dependency in a way compatible with the plugin’s current structure.

Do not assume external repo-level PDF service.
Do not rely on outside-the-repo PDF infrastructure.

### New Shared Service
Create:
- `includes/class-neighbor-voucher-document.php`

Responsibilities:
- normalize voucher data for neighbor-facing document output
- choose language strings
- render neighbor-facing voucher HTML
- generate PDF
- store generated artifact path
- email PDF via `wp_mail()`

### New Language Service
Create:
- `includes/class-voucher-i18n.php`

Responsibilities:
- return language dictionaries for `en`, `es`, and Burmese code used in this plugin
- centralize document labels

### New Shared Template
Create:
- `public/templates/documents/neighbor-voucher.php`

This template must be shared across voucher types with voucher-type-specific content blocks as needed.

## Data / Content Rules

### All Voucher Types
Must support:
- open/print voucher
- email voucher
- language selection

### Neighbor-Facing Voucher Content
Show:
- voucher title
- neighbor identifying details already consistent with current plugin practice
- requested items
- delivery included yes/no if applicable
- estimated amount approved for this voucher

Do not show:
- internal pricing breakdown
- estimated item total
- delivery fee amount
- invoice data
- neighbor payment language

## Storage Rules
Store generated PDFs in plugin-managed uploads path under voucher-specific document directory.

## UI Rules
In voucher detail/cashier UI, add:
- Open Voucher
- Email Voucher
- Language selector

These must be available for all voucher types without admin-toggle gating.

## Email Rules
- Use `wp_mail()`
- Attach generated PDF
- Subject/body can be simple and language-aware
- Do not require an external mail service integration in this slice

## Acceptance Criteria
- Printable/email voucher actions are available for all voucher types
- Admin toggle no longer governs availability
- PDF generation works inside the plugin
- Email sends PDF attachment
- EN/ES/Burmese document rendering works
- Furniture and clothing both use shared neighbor-voucher document architecture
- Neighbor-facing document does not expose internal financial detail

## Testing Expectations
- Generate voucher PDF for clothing
- Generate voucher PDF for furniture
- Email voucher PDF for clothing
- Email voucher PDF for furniture
- Verify English rendering
- Verify Spanish rendering
- Verify Burmese rendering
- Verify cashier/detail UI exposes actions for all voucher types
- Verify old admin toggle no longer blocks availability