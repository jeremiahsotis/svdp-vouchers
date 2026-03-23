# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Project Overview

SVdP Vouchers is a WordPress plugin that manages virtual clothing vouchers for St. Vincent de Paul organizations. It provides a database-first architecture with optional Monday.com synchronization for tracking voucher requests, redemptions, and coat distribution.

## Development Environment

This is a **Local by Flywheel** WordPress site located at:
- Full path: `/Users/jeremiahotis/Local Sites/voucher-system/app/public/wp-content/plugins/SVdP-Vouchers`
- The plugin is running within a containerized WordPress environment managed by Local

**Important**: You cannot run standard WordPress commands directly. Use the Local by Flywheel interface for:
- Starting/stopping the WordPress server
- Accessing the database via phpMyAdmin
- Viewing error logs
- Managing SSL certificates

## Core Architecture

### Database-First Design

The plugin uses WordPress database tables as the primary source of truth:
- **wp_svdp_vouchers**: Main voucher records with status tracking
- **wp_svdp_conferences**: Conference management with soft delete support

Monday.com sync is entirely optional and runs as a secondary sync layer after database operations complete.

### Key Classes & Responsibilities

1. **SVDP_Database** (`includes/class-database.php`)
   - Creates database schema on plugin activation
   - Defines indexes for performance (first_name, last_name, dob, voucher_created_date)
   - Inserts default conferences

2. **SVDP_Voucher** (`includes/class-voucher.php`)
   - All voucher CRUD operations
   - Duplicate detection with differential rules (Vincentian vs Cashier)
   - Coat eligibility calculation (August 1st annual reset)
   - Status management (Active → Redeemed → Expired)
   - Email notifications to conference contacts

3. **SVDP_Conference** (`includes/class-conference.php`)
   - Conference management with soft delete (active flag)
   - Slug-based identification for shortcode parameters
   - Emergency vs non-emergency conference distinction

4. **SVDP_Monday_Sync** (`includes/class-monday-sync.php`)
   - Optional Monday.com GraphQL API integration
   - Triggered after database operations via `SVDP_Monday_Sync::is_enabled()` checks
   - Stores Monday item IDs in voucher records for update operations

5. **SVDP_Shortcodes** (`includes/class-shortcodes.php`)
   - `[svdp_voucher_request]` - Public-facing voucher request form
   - `[svdp_cashier_station]` - Protected cashier interface with role checks

6. **SVDP_Admin** (`includes/class-admin.php`)
   - Admin menu and settings pages
   - AJAX handlers for conference management
   - CSV export functionality

### Critical Business Logic

#### Duplicate Detection Rules
Located in `SVDP_Voucher::check_duplicate()`:

- **90-day eligibility window** based on matching: first_name, last_name, dob
- **Vincentian requests**: Only blocked by non-Emergency vouchers (allows override of Emergency vouchers)
- **Cashier requests**: Blocked by ANY voucher type within 90 days
- **Two-tier matching**: Exact match first, then phonetic similarity using SOUNDEX
- Cashiers can override with accountability (name + note stored in `override_note`)

#### Coat Eligibility Calculation
Located in `SVDP_Voucher::can_issue_coat()`:

- Resets annually on **August 1st**
- Compares `coat_issued_date` to most recent August 1st
- If current month < August: uses prior year's August 1st
- If current month >= August: uses current year's August 1st
- One coat per household per year

#### Voucher Value Calculation
Located in `SVDP_Voucher::create_voucher()`:

- **Emergency conference**: $10 per person (adults + children)
- **Regular conference**: $20 per person (adults + children)
- Determined by `conference.is_emergency` flag

#### Status Lifecycle
- **Active**: 0-30 days old, unredeemed
- **Redeemed**: Manually marked by cashier
- **Expired**: Calculated in `get_vouchers()` if > 30 days and still Active
- **Denied**: Special status for tracking denied requests (filtered from cashier station)

### REST API Architecture

All endpoints prefixed with `/wp-json/svdp/v1/`:

**Public endpoints** (`permission_callback: '__return_true'`):
- `POST /vouchers/check-duplicate` - Duplicate detection
- `POST /vouchers/create` - Create voucher
- `POST /vouchers/create-denied` - Track denied requests
- `GET /conferences` - List active conferences

**Protected endpoints** (`permission_callback: is_user_logged_in()`):
- `GET /vouchers` - List all vouchers with calculated statuses
- `PATCH /vouchers/{id}/status` - Update voucher status
- `PATCH /vouchers/{id}/coat` - Update coat issuance

### Frontend JavaScript Pattern

Both `public/js/voucher-request.js` and `public/js/cashier-station.js`:
- Use jQuery and are enqueued globally but only activate on their specific pages
- Access REST API via `svdpVouchers.restUrl` and `svdpVouchers.nonce` (localized)
- Cashier station uses DataTables for real-time voucher management

### Admin Interface

Tab-based admin page (`admin/views/admin-page.php`):
- **Conferences Tab**: AJAX-driven conference management
- **Monday.com Sync Tab**: API configuration with JSON column mapping
- **Analytics Tab**: Reporting and CSV export
- **Settings Tab**: General plugin settings

AJAX operations use `svdpAdmin.nonce` for security.

## Common Patterns

### Adding a New Voucher Field

1. **Database**: Add column in `SVDP_Database::create_tables()` using `dbDelta()`
2. **Model**: Update `SVDP_Voucher::create_voucher()` to accept and save the field
3. **API**: Return field in `SVDP_Voucher::get_vouchers()`
4. **Template**: Add input field to `public/templates/voucher-request-form.php`
5. **JavaScript**: Update form submission in `public/js/voucher-request.js`
6. **Cashier Station**: Display in `public/templates/cashier-station.php` and update `public/js/cashier-station.js` DataTables columns
7. **Monday Sync** (optional): Add to column mapping in `SVDP_Monday_Sync::create_monday_item()`

### Modifying Business Rules

Use WordPress filters defined in the README:
- `svdp_vouchers_eligibility_days` - Modify 90-day window
- `svdp_vouchers_expiration_days` - Modify 30-day expiration
- `svdp_vouchers_coat_reset_date` - Modify August 1st coat reset

### Database Queries

Always join conferences table when displaying voucher data:
```php
SELECT v.*, c.name as conference_name
FROM {$wpdb->prefix}svdp_vouchers v
LEFT JOIN {$wpdb->prefix}svdp_conferences c ON v.conference_id = c.id
```

### Email Notifications

Conference notifications are sent via `SVDP_Voucher::send_conference_notification()`:
- Triggered automatically after voucher creation
- Only sent if `conference.notification_email` is configured
- Skipped for Emergency conference (cashier station) vouchers
- Uses HTML email template with voucher details

## Security Notes

- All REST endpoints use WordPress nonce verification
- Public voucher creation endpoints use `__return_true` (no auth) - this is intentional for Vincentian form access
- Cashier station checks user roles: `svdp_cashier` or `administrator`
- Admin operations require `manage_options` capability
- AJAX handlers verify nonces via `check_ajax_referer()`
- All user input is sanitized using WordPress sanitization functions

## Testing Considerations

- Test duplicate detection with both exact matches and phonetic variations
- Verify coat eligibility resets correctly around August 1st boundary
- Test voucher expiration calculation for edge cases (exactly 30 days)
- Confirm Monday.com sync handles both create and update operations
- Test Emergency conference vs regular conference voucher value calculations
- Verify email notifications are sent to correct conference contacts

## Troubleshooting Local Environment

If the plugin isn't working:
1. Check Local by Flywheel site is running
2. Access site logs via Local → Logs tab
3. Verify database tables exist via Adminer/phpMyAdmin (Database tab in Local)
4. Check WordPress debug log: `app/public/wp-content/debug.log`
5. Ensure plugin is activated in WordPress admin

## Key Gotchas

1. **Status calculation is dynamic**: "Expired" status is calculated in `get_vouchers()`, not stored in DB
2. **Coat eligibility is time-sensitive**: Logic changes behavior on August 1st annually
3. **Duplicate rules differ by creator**: Vincentians vs Cashiers have different blocking rules
4. **Conference soft delete**: Use `active = 0` flag, never hard delete (preserves historical data)
5. **Monday sync is optional**: All sync calls check `SVDP_Monday_Sync::is_enabled()` first
6. **Emergency conference special handling**: Different voucher value, different creator type, different duplicate rules
