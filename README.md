# SVdP Vouchers

A comprehensive virtual clothing voucher management system for St. Vincent de Paul organizations.

## Features

- **Database-First Architecture**: WordPress database as primary storage with optional Monday.com sync
- **Conference Management**: Add/remove conferences dynamically via admin interface
- **Duplicate Detection**: Intelligent 90-day eligibility checking with differential rules for Vincentians vs. Cashiers
- **Role-Based Access**: Separate interfaces for Vincentians and Cashiers with appropriate permissions
- **Winter Coat Tracking**: Annual reset system (August 1st) for coat eligibility
- **Voucher Lifecycle**: Active → Redeemed → Expired status tracking
- **Emergency Override**: Cashiers can override duplicate rules with accountability tracking
- **Real-Time Updates**: DataTables-powered cashier station with automatic refresh
- **Optional Monday.com Sync**: Bidirectional sync capability for existing Monday.com users

## Installation

1. **Upload Plugin**
   - Download the `svdp-vouchers` folder
   - Upload to `/wp-content/plugins/` directory
   - Or upload via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate Plugin**
   - Go to Plugins → Installed Plugins
   - Find "SVdP Vouchers" and click "Activate"
   - Database tables will be created automatically
   - Default conferences will be added
   - "SVdP Cashier" role will be created

3. **Assign Cashier Role**
   - Go to Users → All Users
   - Edit users who should access the cashier station
   - Check "SVdP Cashier" role (in addition to other roles)
   - Save

## Configuration

### Managing Conferences

1. Go to **SVdP Vouchers → Conferences**
2. Add new conferences with name and slug
3. Update Monday.com labels if syncing (optional)
4. Copy shortcodes for each conference

### Setting Up Pages

**For Each Conference:**
1. Create new page (e.g., "Virtual Clothing Voucher - St Mary")
2. Add shortcode: `[svdp_voucher_request conference="st-mary-fort-wayne"]`
3. Publish page
4. Share URL with Vincentians from that conference

**For Cashier Station:**
1. Create new page (e.g., "Cashier Station")
2. Add shortcode: `[svdp_cashier_station]`
3. Make page private or password-protected
4. Share URL with cashiers only

### Optional Monday.com Sync

If you want to sync with an existing Monday.com board:

1. Go to **SVdP Vouchers → Monday.com Sync**
2. Enable sync checkbox
3. Enter your Monday.com API key
4. Enter your Board ID
5. Configure column IDs (JSON mapping)
6. Save settings

**Column ID JSON Example:**
{
"firstName": "text_1234567",
"lastName": "text_2345678",
"dob": "date_3456789",
"adults": "numbers_4567890",
"children": "numbers_5678901",
"conference": "status_6789012",
"vincentianName": "text_7890123",
"vincentianEmail": "email_8901234",
"createdBy": "status_9012345",
"voucherCreatedDate": "date_0123456",
"status": "status_1234567",
"redeemedDate": "date_2345678",
"overrideNote": "long_text_3456789",
"coatStatus": "status_4567890",
"coatIssuedDate": "date_5678901"
}

To find column IDs in Monday.com:
1. Open your board
2. Open browser developer tools (F12)
3. Go to Network tab
4. Make a change to any item
5. Look for GraphQL requests
6. Find column IDs in the request payload

## Business Rules

### Eligibility Windows

**90-Day Window**: Households can receive one voucher per 90 days
- **Conference Requests (Vincentians)**: Blocked by non-Emergency vouchers only
- **Emergency Requests (Cashiers)**: Blocked by ANY voucher (including Emergency)

**30-Day Expiration**: Vouchers expire 30 days after creation
- Expired vouchers cannot be redeemed
- Status automatically calculated as "Expired"

**Annual Coat Reset**: Winter coat eligibility resets August 1st
- One coat per household per year
- Eligibility calculated from most recent August 1st

### Override Capability

Cashiers can override duplicate rules for true emergencies:
- Cashier name is recorded in override note
- Override date is automatically logged
- Maintains accountability and audit trail

### Status Workflow
Created → Active (0-30 days, unredeemed)
→ Redeemed (marked as used)
→ Expired (30+ days, unredeemed)

## Shortcodes

### Voucher Request Form

**Basic:**
[svdp_voucher_request]
Shows dropdown of all conferences

**Conference-Specific:**
[svdp_voucher_request conference="st-mary-fort-wayne"]
Pre-selects conference, hides dropdown

### Cashier Station
[svdp_cashier_station]
Requires user to be logged in with "SVdP Cashier" role or Administrator

## Database Schema

### wp_svdp_vouchers
- Stores all voucher records
- Tracks status, dates, household size
- Links to conference via foreign key
- Optional Monday.com item ID for sync

### wp_svdp_conferences
- Manages available conferences
- Supports soft delete (active flag)
- Includes Monday.com label mapping
- Slug-based identification for shortcodes

## REST API Endpoints

All endpoints require authentication via WordPress nonce.

### GET /wp-json/svdp/v1/vouchers
Retrieve all active, redeemed, and expired vouchers

### POST /wp-json/svdp/v1/vouchers/check-duplicate
Check for existing vouchers within 90 days
- Params: firstName, lastName, dob, createdBy
- Returns: found status, dates, eligibility

### POST /wp-json/svdp/v1/vouchers/create
Create new voucher
- Params: firstName, lastName, dob, adults, children, conference, vincentianName, vincentianEmail, overrideNote (optional)
- Triggers Monday.com sync if enabled

### PATCH /wp-json/svdp/v1/vouchers/{id}/status
Update voucher status (Active → Redeemed)
- Params: status
- Sets redeemed_date automatically
- Triggers Monday.com sync if enabled

### PATCH /wp-json/svdp/v1/vouchers/{id}/coat
Update coat status (Available → Issued)
- Params: coatStatus
- Sets coat_issued_date automatically
- Triggers Monday.com sync if enabled

### GET /wp-json/svdp/v1/conferences
Retrieve all active conferences

## Troubleshooting

### Cashier station shows "Permission denied"
- Ensure user is logged in
- Check user has "SVdP Cashier" role or Administrator role
- Try logging out and back in

### Vouchers not syncing to Monday.com
- Verify API key is correct
- Check Board ID is numeric
- Confirm column IDs JSON is valid
- Check Monday.com API limits (rate limiting)

### Duplicate not being detected
- Verify first name, last name, and date of birth match exactly
- Check if previous voucher is older than 90 days
- For Vincentian requests, only non-Emergency vouchers block

### Table not loading in cashier station
- Check browser console for JavaScript errors
- Verify REST API is accessible
- Ensure nonce is being generated correctly
- Try clearing browser cache

## Development

This repository is maintained as a deployable WordPress plugin codebase. Runtime PHP,
public JavaScript/CSS/templates, committed third-party runtime dependencies, Composer
metadata, CI configuration, governance contracts, and validation scripts stay in the
repo so the plugin can be installed directly from the checkout.

Generated guide documents, PDF/PNG render outputs, historical recon notes, and older
planning/spec working artifacts are externalized outside the plugin repo. The cleanup
package for this pass is:

`/Users/jeremiahotis/dev/wordpress/svdp/wp-content/plugins/svdp-vouchers-externalized-docs-2026-08-24.zip`

### File Structure
svdp-vouchers/
├── svdp-vouchers.php           # Main plugin file
├── includes/
│   ├── class-database.php       # Database schema
│   ├── class-voucher.php        # Voucher CRUD
│   ├── class-conference.php     # Conference management
│   ├── class-monday-sync.php    # Monday.com integration
│   ├── class-shortcodes.php     # Shortcode handlers
│   └── class-admin.php          # Admin functionality
├── admin/
│   ├── views/
│   │   ├── admin-page.php
│   │   ├── tab-conferences.php
│   │   ├── tab-monday.php
│   │   └── tab-settings.php
│   ├── css/admin.css
│   └── js/admin.js
├── public/
│   ├── templates/
│   │   ├── voucher-request-form.php
│   │   └── cashier-station.php
│   ├── css/voucher-forms.css
│   ├── js/voucher-request.js
│   └── js/cashier-station.js
├── vendor/                      # Committed PHP runtime dependencies
├── contracts/                   # Protected surface and contract registry
├── standards/                   # Governance standards
├── specs/                       # Current/archived implementation packets
├── scripts/                     # Validation and maintenance scripts
└── README.md

### Hooks & Filters

**Actions:**
- `svdp_vouchers_activated` - Fires on plugin activation
- `svdp_vouchers_voucher_created` - Fires after voucher creation
- `svdp_vouchers_voucher_updated` - Fires after voucher update

**Filters:**
- `svdp_vouchers_eligibility_days` - Modify 90-day eligibility window
- `svdp_vouchers_expiration_days` - Modify 30-day expiration period
- `svdp_vouchers_coat_reset_date` - Modify August 1st coat reset date

### Extending

To add custom functionality:
php
// Change eligibility window to 60 days
add_filter('svdp_vouchers_eligibility_days', function($days) {
    return 60;
});

// Add custom action after voucher creation
add_action('svdp_vouchers_voucher_created', function($voucher_id) {
    // Send email notification, log to external system, etc.
});

## Support

For questions, issues, or feature requests:
- Contact: District Program Manager
- Organization: St. Vincent de Paul Fort Wayne

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Database-first architecture with optional Monday.com sync
- Conference management system
- Duplicate detection with differential rules
- Winter coat tracking with annual reset
- Role-based access control
- Emergency override capability
- Real-time cashier station with DataTables
