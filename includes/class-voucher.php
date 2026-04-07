<?php
/**
 * Voucher management
 */
class SVDP_Voucher {

    /**
     * Normalize legacy voucher types for clothing workflows.
     */
    public static function normalize_voucher_type($voucher_type) {
        $voucher_type = strtolower(trim((string) $voucher_type));

        if ($voucher_type === 'furniture' || $voucher_type === 'household') {
            return 'furniture';
        }

        return 'clothing';
    }

    /**
     * Check whether a voucher type is furniture at the root row level.
     */
    public static function is_furniture_voucher_type($voucher_type) {
        return self::normalize_voucher_type($voucher_type) === 'furniture';
    }

    /**
     * Get all vouchers formatted for cashier views.
     */
    public static function get_cashier_vouchers() {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $furniture_meta_table = $wpdb->prefix . 'svdp_furniture_voucher_meta';
        $invoices_table = $wpdb->prefix . 'svdp_invoices';

        $results = $wpdb->get_results("
            SELECT
                v.*,
                c.name as conference_name,
                fm.delivery_required,
                fm.delivery_address_line_1,
                fm.delivery_address_line_2,
                fm.delivery_city,
                fm.delivery_state,
                fm.delivery_zip,
                fm.delivery_fee,
                fm.estimated_total_min,
                fm.estimated_total_max,
                fm.estimated_requestor_portion_min,
                fm.estimated_requestor_portion_max,
                fm.completed_at as furniture_completed_at,
                fm.completed_by_user_id as furniture_completed_by_user_id,
                fm.receipt_file_path,
                fm.invoice_file_path,
                fi.invoice_number,
                fi.invoice_date,
                fi.amount as invoice_amount,
                fi.delivery_fee as invoice_delivery_fee,
                fi.items_total as invoice_items_total,
                fi.conference_share_total,
                fi.stored_file_path as invoice_stored_file_path
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            LEFT JOIN $furniture_meta_table fm ON fm.voucher_id = v.id
            LEFT JOIN $invoices_table fi ON fi.voucher_id = v.id
            WHERE v.status != 'Denied'
            ORDER BY v.voucher_created_date DESC, v.id DESC
        ");

        if (!$results) {
            return [];
        }

        $furniture_voucher_ids = array_values(array_filter(array_map(function($voucher) {
            return self::is_furniture_voucher_type($voucher->voucher_type) ? (int) $voucher->id : null;
        }, $results)));
        $item_progress_map = self::get_furniture_item_progress_map($furniture_voucher_ids);

        return array_map(function($voucher) use ($item_progress_map) {
            $voucher_id = (int) $voucher->id;
            $progress = $item_progress_map[$voucher_id] ?? null;
            return self::format_cashier_voucher($voucher, $progress);
        }, $results);
    }

    /**
     * Get a single voucher formatted for cashier views.
     */
    public static function get_cashier_voucher($voucher_id) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $furniture_meta_table = $wpdb->prefix . 'svdp_furniture_voucher_meta';
        $invoices_table = $wpdb->prefix . 'svdp_invoices';

        $voucher = $wpdb->get_row($wpdb->prepare("
            SELECT
                v.*,
                c.name as conference_name,
                fm.delivery_required,
                fm.delivery_address_line_1,
                fm.delivery_address_line_2,
                fm.delivery_city,
                fm.delivery_state,
                fm.delivery_zip,
                fm.delivery_fee,
                fm.estimated_total_min,
                fm.estimated_total_max,
                fm.estimated_requestor_portion_min,
                fm.estimated_requestor_portion_max,
                fm.completed_at as furniture_completed_at,
                fm.completed_by_user_id as furniture_completed_by_user_id,
                fm.receipt_file_path,
                fm.invoice_file_path,
                fi.invoice_number,
                fi.invoice_date,
                fi.amount as invoice_amount,
                fi.delivery_fee as invoice_delivery_fee,
                fi.items_total as invoice_items_total,
                fi.conference_share_total,
                fi.stored_file_path as invoice_stored_file_path
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            LEFT JOIN $furniture_meta_table fm ON fm.voucher_id = v.id
            LEFT JOIN $invoices_table fi ON fi.voucher_id = v.id
            WHERE v.id = %d
              AND v.status != 'Denied'
            LIMIT 1
        ", $voucher_id));

        if (!$voucher) {
            return null;
        }

        $item_progress = null;
        $furniture_items = [];

        if (self::is_furniture_voucher_type($voucher->voucher_type)) {
            $item_progress_map = self::get_furniture_item_progress_map([(int) $voucher->id]);
            $item_progress = $item_progress_map[(int) $voucher->id] ?? null;
            $furniture_items = self::get_furniture_voucher_items((int) $voucher->id);
        }

        return self::format_cashier_voucher($voucher, $item_progress, $furniture_items);
    }
    
    /**
     * Get all vouchers with coat information
     */
    public static function get_vouchers($request) {
        return self::get_cashier_vouchers();
    }

    /**
     * Build the reusable neighbor lookup key from an existing voucher payload.
     *
     * @param array|object $voucher Voucher row or formatted voucher payload.
     * @return string
     */
    public static function get_neighbor_lookup_key_for_voucher($voucher) {
        $identity = self::extract_voucher_identity($voucher);
        if ($identity === null) {
            return '';
        }

        return SVDP_Neighbor_Delivery_Preferences::build_lookup_key(
            $identity['first_name'],
            $identity['last_name'],
            $identity['dob']
        );
    }

    /**
     * Build the reusable neighbor lookup key from a voucher ID.
     *
     * @param int $voucher_id Voucher ID.
     * @return string
     */
    public static function get_neighbor_lookup_key_for_voucher_id($voucher_id) {
        $voucher = self::get_voucher_identity_row($voucher_id);
        if ($voucher === null) {
            return '';
        }

        return self::get_neighbor_lookup_key_for_voucher($voucher);
    }

    /**
     * Retrieve reusable delivery preferences for a voucher identity.
     *
     * @param array|object $voucher Voucher row or formatted voucher payload.
     * @return array<string, mixed>|null
     */
    public static function get_preferences_for_voucher($voucher) {
        $identity = self::extract_voucher_identity($voucher);
        if ($identity === null) {
            return null;
        }

        return SVDP_Neighbor_Delivery_Preferences::get_by_identity(
            $identity['first_name'],
            $identity['last_name'],
            $identity['dob']
        );
    }

    /**
     * Retrieve reusable delivery preferences using a voucher ID.
     *
     * @param int $voucher_id Voucher ID.
     * @return array<string, mixed>|null
     */
    public static function get_preferences_for_voucher_id($voucher_id) {
        $voucher = self::get_voucher_identity_row($voucher_id);
        if ($voucher === null) {
            return null;
        }

        return self::get_preferences_for_voucher($voucher);
    }

    /**
     * Create or update reusable delivery preferences using voucher identity fields.
     *
     * @param array|object         $voucher Voucher row or formatted voucher payload.
     * @param array<string, mixed> $preference_data Preference values to persist.
     * @return array<string, mixed>|false
     */
    public static function upsert_preferences_for_voucher($voucher, $preference_data = []) {
        $identity = self::extract_voucher_identity($voucher);
        if ($identity === null || !is_array($preference_data)) {
            return false;
        }

        return SVDP_Neighbor_Delivery_Preferences::upsert_preferences_for_identity(
            $identity['first_name'],
            $identity['last_name'],
            $identity['dob'],
            $preference_data
        );
    }

    /**
     * Create or update reusable delivery preferences using a voucher ID.
     *
     * @param int                 $voucher_id Voucher ID.
     * @param array<string, mixed> $preference_data Preference values to persist.
     * @return array<string, mixed>|false
     */
    public static function upsert_preferences_for_voucher_id($voucher_id, $preference_data = []) {
        $voucher = self::get_voucher_identity_row($voucher_id);
        if ($voucher === null) {
            return false;
        }

        return self::upsert_preferences_for_voucher($voucher, $preference_data);
    }
    
    /**
     * Check if coat can be issued (resets August 1st)
     */
    private static function can_issue_coat($coat_issued_date) {
        if (empty($coat_issued_date)) {
            return true;
        }
        
        // Get most recent August 1st
        $today = new DateTime();
        $current_year = (int)$today->format('Y');
        $current_month = (int)$today->format('m');
        
        // If we're before August, use last year's August 1st
        if ($current_month < 8) {
            $reset_date = new DateTime(($current_year - 1) . '-08-01');
        } else {
            $reset_date = new DateTime($current_year . '-08-01');
        }
        
        $issued_date = new DateTime($coat_issued_date);
        
        // Can issue if the coat was issued before the most recent August 1st
        return $issued_date < $reset_date;
    }
    
    /**
     * Check for duplicate or similar voucher
     */
    public static function check_duplicate($request) {
        $params = self::get_request_data($request);

        $first_name = sanitize_text_field($params['firstName']);
        $last_name = sanitize_text_field($params['lastName']);
        $dob = sanitize_text_field($params['dob']);
        $created_by = sanitize_text_field($params['createdBy']);
        $voucher_type = isset($params['voucherType'])
            ? self::normalize_voucher_type(sanitize_text_field($params['voucherType']))
            : 'clothing';
        $conference_slug = isset($params['conference']) ? sanitize_text_field($params['conference']) : '';

        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';

        // Get requesting organization's eligibility window
        $requesting_org = null;
        if (!empty($conference_slug)) {
            $requesting_org = SVDP_Conference::get_by_slug($conference_slug);
        }
        $eligibility_days = ($requesting_org && isset($requesting_org->eligibility_days))
            ? intval($requesting_org->eligibility_days)
            : 90; // Default to 90 days

        // Calculate cutoff date based on organization's eligibility window
        $eligibility_cutoff = date('Y-m-d', strtotime("-{$eligibility_days} days"));

        // Build base query based on created_by
        $conference_filter = ($created_by === 'Cashier') ? '' : 'AND c.is_emergency = 0';

        $voucher_type_clause = self::build_duplicate_voucher_type_clause($voucher_type);

        // STEP 1: Check for EXACT match (including voucher_type)
        $exact_query = $wpdb->prepare("
            SELECT v.*, c.name as conference_name
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            WHERE v.first_name = %s
            AND v.last_name = %s
            AND v.dob = %s
            AND {$voucher_type_clause}
            AND v.voucher_created_date >= %s
            $conference_filter
            ORDER BY v.voucher_created_date DESC
            LIMIT 1
        ", $first_name, $last_name, $dob, $eligibility_cutoff);

        $exact_match = $wpdb->get_row($exact_query);

        if ($exact_match) {
            // Calculate next eligible date using organization's eligibility window
            $voucher_date = new DateTime($exact_match->voucher_created_date);
            $next_eligible = clone $voucher_date;
            $next_eligible->modify("+{$eligibility_days} days");
            
            return [
                'matchType' => 'exact',
                'found' => true,
                'firstName' => $exact_match->first_name,
                'lastName' => $exact_match->last_name,
                'dob' => $exact_match->dob,
                'conference' => $exact_match->conference_name,
                'voucherCreatedDate' => $exact_match->voucher_created_date,
                'vincentianName' => $exact_match->vincentian_name,
                'nextEligibleDate' => $next_eligible->format('Y-m-d'),
            ];
        }
        
        // STEP 2: Check for SIMILAR names (if no exact match, same voucher_type)
        // Using SOUNDEX for phonetic matching and checking same DOB
        $similar_query = $wpdb->prepare("
            SELECT v.*, c.name as conference_name,
                   (SOUNDEX(v.first_name) = SOUNDEX(%s)) as first_soundex_match,
                   (SOUNDEX(v.last_name) = SOUNDEX(%s)) as last_soundex_match
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            WHERE v.dob = %s
            AND {$voucher_type_clause}
            AND v.voucher_created_date >= %s
            AND (
                SOUNDEX(v.first_name) = SOUNDEX(%s)
                OR SOUNDEX(v.last_name) = SOUNDEX(%s)
                OR v.first_name LIKE %s
                OR v.last_name LIKE %s
            )
            $conference_filter
            ORDER BY v.voucher_created_date DESC
            LIMIT 5
        ",
            $first_name,
            $last_name,
            $dob,
            $eligibility_cutoff,
            $first_name,
            $last_name,
            '%' . $wpdb->esc_like(substr($first_name, 0, 3)) . '%',
            '%' . $wpdb->esc_like(substr($last_name, 0, 3)) . '%'
        );

        $similar_matches = $wpdb->get_results($similar_query);

        if ($similar_matches && count($similar_matches) > 0) {
            // Format similar matches
            $matches = [];
            foreach ($similar_matches as $match) {
                $voucher_date = new DateTime($match->voucher_created_date);
                $next_eligible = clone $voucher_date;
                $next_eligible->modify("+{$eligibility_days} days");
                
                $matches[] = [
                    'firstName' => $match->first_name,
                    'lastName' => $match->last_name,
                    'dob' => $match->dob,
                    'conference' => $match->conference_name,
                    'voucherCreatedDate' => $match->voucher_created_date,
                    'vincentianName' => $match->vincentian_name,
                    'nextEligibleDate' => $next_eligible->format('Y-m-d'),
                ];
            }
            
            return [
                'matchType' => 'similar',
                'found' => true,
                'matches' => $matches,
            ];
        }
        
        return ['found' => false];
    }
    
    /**
     * Create voucher
     */
    public static function create_voucher($request) {
        $params = self::get_request_data($request);

        $first_name = sanitize_text_field($params['firstName']);
        $last_name = sanitize_text_field($params['lastName']);
        $dob = sanitize_text_field($params['dob']);
        $adults = intval($params['adults']);
        $children = intval($params['children']);
        $conference = sanitize_text_field($params['conference']);
        $vincentian_name = isset($params['vincentianName']) ? sanitize_text_field($params['vincentianName']) : null;
        $vincentian_email = isset($params['vincentianEmail']) ? sanitize_email($params['vincentianEmail']) : null;
        $voucher_type = isset($params['voucherType'])
            ? self::normalize_voucher_type(sanitize_text_field($params['voucherType']))
            : 'clothing';

        // Extract new override fields
        $manager_id = isset($params['manager_id']) ? intval($params['manager_id']) : null;
        $reason_id = isset($params['reason_id']) ? intval($params['reason_id']) : null;

        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        // Build override note if manager override
        $override_note = null;
        if ($manager_id && $reason_id) {
            $manager_table = $wpdb->prefix . 'svdp_managers';
            $reason_table = $wpdb->prefix . 'svdp_override_reasons';

            $manager = $wpdb->get_row($wpdb->prepare(
                "SELECT name FROM $manager_table WHERE id = %d", $manager_id
            ));

            $reason = $wpdb->get_row($wpdb->prepare(
                "SELECT reason_text FROM $reason_table WHERE id = %d", $reason_id
            ));

            if ($manager && $reason) {
                $override_note = sprintf(
                    'Approved by %s - Reason: %s - %s',
                    $manager->name,
                    $reason->reason_text,
                    current_time('Y-m-d H:i:s')
                );
            }
        }

        // Get conference by slug or name
        $conference_obj = SVDP_Conference::get_by_slug($conference);
        if (!$conference_obj) {
            $conference_obj = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}svdp_conferences WHERE name = %s",
                $conference
            ));
        }

        if (!$conference_obj) {
            return new WP_Error('invalid_conference', 'Conference not found');
        }

        // Validate voucher type against organization's allowed types
        $allowed_types = SVDP_Settings::normalize_voucher_types(
            $conference_obj->allowed_voucher_types,
            ['clothing']
        );

        if (!in_array($voucher_type, $allowed_types)) {
            return new WP_Error('invalid_voucher_type',
                'This organization does not offer ' . ucfirst($voucher_type) . ' vouchers. Allowed types: ' . implode(', ', array_map('ucfirst', $allowed_types))
            );
        }

        if (self::is_furniture_voucher_type($voucher_type)) {
            return self::create_furniture_voucher_record([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'dob' => $dob,
                'adults' => $adults,
                'children' => $children,
                'conference_obj' => $conference_obj,
                'vincentian_name' => $vincentian_name,
                'vincentian_email' => $vincentian_email,
                'override_note' => $override_note,
                'manager_id' => $manager_id,
                'reason_id' => $reason_id,
                'params' => $params,
            ]);
        }

        // Calculate voucher value based on conference type
        $household_size = $adults + $children;
        if ($conference_obj->is_emergency) {
            // Emergency vouchers: $10 per person
            $voucher_value = $household_size * 10;
        } else {
            // Conference vouchers: $20 per person
            $voucher_value = $household_size * 20;
        }

        // Calculate items count based on conference type
        $items_per_person = $conference_obj->is_emergency
            ? ($conference_obj->emergency_items_per_person ?? 3)
            : ($conference_obj->regular_items_per_person ?? 7);
        $voucher_items_count = $household_size * $items_per_person;

        // Insert voucher
        $result = $wpdb->insert($table, [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'dob' => $dob,
            'adults' => $adults,
            'children' => $children,
            'conference_id' => $conference_obj->id,
            'vincentian_name' => $vincentian_name,
            'vincentian_email' => $vincentian_email,
            'created_by' => $conference_obj->is_emergency ? 'Cashier' : 'Vincentian',
            'voucher_created_date' => current_time('Y-m-d'),
            'voucher_value' => $voucher_value,
            'voucher_type' => $voucher_type,
            'voucher_items_count' => $voucher_items_count,
            'override_note' => $override_note,
            'manager_id' => $manager_id,
            'reason_id' => $reason_id,
        ]);
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create voucher');
        }
        
        $voucher_id = $wpdb->insert_id;
        
        $next_eligible = self::get_next_eligible_date($conference_obj);
        
        // Calculate coat eligibility (next August 1st if after current August 1st)
        $coat_eligible_after = null;
        $today = new DateTime();
        $current_year = (int)$today->format('Y');
        $current_month = (int)$today->format('m');
        
        if ($current_month >= 8) {
            $next_august = new DateTime(($current_year + 1) . '-08-01');
        } else {
            $next_august = new DateTime($current_year . '-08-01');
        }
        $coat_eligible_after = $next_august->format('F j, Y');

        // Send email notification to conference
        self::send_conference_notification($voucher_id);
        
        return [
            'success' => true,
            'voucher_id' => $voucher_id,
            'nextEligibleDate' => $next_eligible->format('F j, Y'),
            'coatEligibleAfter' => $coat_eligible_after,
        ];
    }

    /**
     * Persist a furniture voucher root row plus its meta and snapshot items.
     *
     * @param array $context Normalized furniture request context.
     * @return array|WP_Error
     */
    private static function create_furniture_voucher_record($context) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $furniture_meta_table = $wpdb->prefix . 'svdp_furniture_voucher_meta';

        $requested_items = self::parse_requested_furniture_items($context['params']['items'] ?? []);
        if (is_wp_error($requested_items)) {
            return $requested_items;
        }

        $delivery = self::sanitize_delivery_payload($context['params']);
        if (is_wp_error($delivery)) {
            return $delivery;
        }

        $catalog_rows = SVDP_Furniture_Catalog::get_items_by_ids(array_keys($requested_items), true);
        $catalog_row_map = [];
        foreach ($catalog_rows as $catalog_row) {
            $catalog_row_map[(int) $catalog_row->id] = $catalog_row;
        }

        if (count($catalog_row_map) !== count($requested_items)) {
            return new WP_Error(
                'invalid_furniture_items',
                'One or more selected furniture items are no longer available. Please refresh and try again.',
                ['status' => 400]
            );
        }

        $estimates = self::calculate_furniture_estimates($requested_items, $catalog_row_map, $delivery['required']);

        $result = $wpdb->insert($vouchers_table, [
            'first_name' => $context['first_name'],
            'last_name' => $context['last_name'],
            'dob' => $context['dob'],
            'adults' => $context['adults'],
            'children' => $context['children'],
            'conference_id' => $context['conference_obj']->id,
            'vincentian_name' => $context['vincentian_name'],
            'vincentian_email' => $context['vincentian_email'],
            'created_by' => $context['conference_obj']->is_emergency ? 'Cashier' : 'Vincentian',
            'voucher_created_date' => current_time('Y-m-d'),
            'voucher_value' => $estimates['requestor_portion_max'],
            'voucher_type' => 'furniture',
            'voucher_items_count' => $estimates['item_count'],
            'override_note' => $context['override_note'],
            'manager_id' => $context['manager_id'],
            'reason_id' => $context['reason_id'],
        ]);

        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create furniture voucher.');
        }

        $voucher_id = (int) $wpdb->insert_id;

        $meta_result = $wpdb->insert($furniture_meta_table, [
            'voucher_id' => $voucher_id,
            'delivery_required' => $delivery['required'] ? 1 : 0,
            'delivery_address_line_1' => $delivery['address']['line_1'],
            'delivery_address_line_2' => $delivery['address']['line_2'],
            'delivery_city' => $delivery['address']['city'],
            'delivery_state' => $delivery['address']['state'],
            'delivery_zip' => $delivery['address']['zip'],
            'delivery_fee' => $estimates['delivery_fee'],
            'estimated_total_min' => $estimates['total_min'],
            'estimated_total_max' => $estimates['total_max'],
            'estimated_requestor_portion_min' => $estimates['requestor_portion_min'],
            'estimated_requestor_portion_max' => $estimates['requestor_portion_max'],
        ]);

        if ($meta_result === false) {
            self::cleanup_failed_furniture_voucher($voucher_id);
            return new WP_Error('database_error', 'Failed to save furniture delivery details.');
        }

        $items_result = self::persist_furniture_voucher_items($voucher_id, $requested_items, $catalog_row_map);
        if (is_wp_error($items_result)) {
            self::cleanup_failed_furniture_voucher($voucher_id);
            return $items_result;
        }

        $next_eligible = self::get_next_eligible_date($context['conference_obj']);

        self::send_conference_notification($voucher_id);

        return [
            'success' => true,
            'voucher_id' => $voucher_id,
            'voucherType' => 'furniture',
            'itemCount' => $estimates['item_count'],
            'deliveryRequired' => $delivery['required'],
            'deliveryFee' => $estimates['delivery_fee'],
            'estimatedTotalMin' => $estimates['total_min'],
            'estimatedTotalMax' => $estimates['total_max'],
            'estimatedRequestorPortionMin' => $estimates['requestor_portion_min'],
            'estimatedRequestorPortionMax' => $estimates['requestor_portion_max'],
            'nextEligibleDate' => $next_eligible->format('F j, Y'),
        ];
    }

    /**
     * Parse requested furniture items from a REST payload.
     *
     * @param mixed $raw_items JSON-decoded or raw item list.
     * @return array|WP_Error
     */
    private static function parse_requested_furniture_items($raw_items) {
        if (is_string($raw_items)) {
            $decoded = json_decode($raw_items, true);
            if (is_array($decoded)) {
                $raw_items = $decoded;
            }
        }

        if (!is_array($raw_items) || empty($raw_items)) {
            return new WP_Error('furniture_items_required', 'Select at least one furniture item.', ['status' => 400]);
        }

        $requested_items = [];

        foreach ($raw_items as $raw_item) {
            if (is_object($raw_item)) {
                $raw_item = (array) $raw_item;
            }

            if (!is_array($raw_item)) {
                return new WP_Error('invalid_furniture_items', 'Invalid furniture item selection.', ['status' => 400]);
            }

            $catalog_item_id = intval($raw_item['catalogItemId'] ?? $raw_item['catalog_item_id'] ?? 0);
            $quantity = intval($raw_item['quantity'] ?? 1);

            if ($catalog_item_id <= 0 || $quantity <= 0) {
                return new WP_Error('invalid_furniture_items', 'Invalid furniture item selection.', ['status' => 400]);
            }

            if (!isset($requested_items[$catalog_item_id])) {
                $requested_items[$catalog_item_id] = 0;
            }

            $requested_items[$catalog_item_id] += $quantity;
        }

        if (empty($requested_items)) {
            return new WP_Error('furniture_items_required', 'Select at least one furniture item.', ['status' => 400]);
        }

        return $requested_items;
    }

    /**
     * Sanitize the optional delivery payload for furniture vouchers.
     *
     * @param array $params Request parameters.
     * @return array|WP_Error
     */
    private static function sanitize_delivery_payload($params) {
        $delivery_required = !empty($params['deliveryRequired']) && $params['deliveryRequired'] !== 'false';
        $delivery_address = $params['deliveryAddress'] ?? [];

        if (is_string($delivery_address)) {
            $decoded = json_decode($delivery_address, true);
            if (is_array($decoded)) {
                $delivery_address = $decoded;
            }
        } elseif (is_object($delivery_address)) {
            $delivery_address = (array) $delivery_address;
        }

        if (!is_array($delivery_address)) {
            $delivery_address = [];
        }

        $address = [
            'line_1' => sanitize_text_field($delivery_address['line1'] ?? $delivery_address['line_1'] ?? ''),
            'line_2' => sanitize_text_field($delivery_address['line2'] ?? $delivery_address['line_2'] ?? ''),
            'city' => sanitize_text_field($delivery_address['city'] ?? ''),
            'state' => sanitize_text_field($delivery_address['state'] ?? ''),
            'zip' => sanitize_text_field($delivery_address['zip'] ?? ''),
        ];

        if ($delivery_required) {
            if ($address['line_1'] === '' || $address['city'] === '' || $address['state'] === '' || $address['zip'] === '') {
                return new WP_Error(
                    'delivery_address_required',
                    'Delivery address line 1, city, state, and ZIP code are required when delivery is selected.',
                    ['status' => 400]
                );
            }
        } else {
            $address = [
                'line_1' => null,
                'line_2' => null,
                'city' => null,
                'state' => null,
                'zip' => null,
            ];
        }

        return [
            'required' => $delivery_required,
            'address' => $address,
        ];
    }

    /**
     * Calculate furniture request estimates from catalog snapshots.
     *
     * @param array $requested_items Requested quantities keyed by catalog item ID.
     * @param array $catalog_row_map Catalog rows keyed by item ID.
     * @param bool  $delivery_required Whether delivery is selected.
     * @return array
     */
    private static function calculate_furniture_estimates($requested_items, $catalog_row_map, $delivery_required) {
        $estimated_total_min = 0.0;
        $estimated_total_max = 0.0;
        $item_count = 0;

        foreach ($requested_items as $catalog_item_id => $quantity) {
            if (!isset($catalog_row_map[$catalog_item_id])) {
                continue;
            }

            $catalog_row = $catalog_row_map[$catalog_item_id];
            $quantity = intval($quantity);
            $item_count += $quantity;

            if ($catalog_row->pricing_type === 'fixed') {
                $fixed_price = (float) $catalog_row->price_fixed;
                $estimated_total_min += $fixed_price * $quantity;
                $estimated_total_max += $fixed_price * $quantity;
            } else {
                $estimated_total_min += (float) $catalog_row->price_min * $quantity;
                $estimated_total_max += (float) $catalog_row->price_max * $quantity;
            }
        }

        $delivery_fee = $delivery_required ? 50.0 : 0.0;

        return [
            'item_count' => $item_count,
            'total_min' => round($estimated_total_min, 2),
            'total_max' => round($estimated_total_max, 2),
            'delivery_fee' => round($delivery_fee, 2),
            'requestor_portion_min' => round(($estimated_total_min * 0.5) + $delivery_fee, 2),
            'requestor_portion_max' => round(($estimated_total_max * 0.5) + $delivery_fee, 2),
        ];
    }

    /**
     * Normalize a catalog row's conference coverage mode.
     *
     * The catalog schema currently stores this as discount_* for compatibility with prior recon notes.
     *
     * @param object $catalog_row Catalog row.
     * @return string
     */
    private static function get_catalog_discount_type($catalog_row) {
        return isset($catalog_row->discount_type) && $catalog_row->discount_type === 'fixed'
            ? 'fixed'
            : 'percent';
    }

    /**
     * Normalize a catalog row's conference coverage amount.
     *
     * @param object $catalog_row Catalog row.
     * @return float
     */
    private static function get_catalog_discount_value($catalog_row) {
        $default_value = 50.0;

        if (!isset($catalog_row->discount_value) || $catalog_row->discount_value === null || $catalog_row->discount_value === '') {
            return $default_value;
        }

        $discount_value = round((float) $catalog_row->discount_value, 2);
        if ($discount_value < 0) {
            return $default_value;
        }

        if (self::get_catalog_discount_type($catalog_row) === 'percent') {
            return min($discount_value, 100.0);
        }

        return $discount_value;
    }

    /**
     * Calculate the conference and store share for a single concrete item price.
     *
     * @param string $discount_type Catalog coverage mode.
     * @param float  $discount_value Catalog coverage amount.
     * @param float  $price Concrete item price.
     * @return array
     */
    private static function calculate_furniture_item_shares($discount_type, $discount_value, $price) {
        $price = max(round((float) $price, 2), 0.0);
        $discount_value = max(round((float) $discount_value, 2), 0.0);

        if ($discount_type === 'fixed') {
            $conference_share = min($discount_value, $price);
        } else {
            $conference_share = $price * (min($discount_value, 100.0) / 100);
        }

        $conference_share = round($conference_share, 2);
        $store_share = round(max($price - $conference_share, 0.0), 2);

        return [
            'conference_share_amount' => $conference_share,
            'store_share_amount' => $store_share,
        ];
    }

    /**
     * Persist snapshot rows for requested furniture items.
     *
     * @param int   $voucher_id Root voucher ID.
     * @param array $requested_items Requested quantities keyed by catalog item ID.
     * @param array $catalog_row_map Catalog rows keyed by item ID.
     * @return true|WP_Error
     */
    private static function persist_furniture_voucher_items($voucher_id, $requested_items, $catalog_row_map) {
        global $wpdb;
        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';

        foreach ($requested_items as $catalog_item_id => $quantity) {
            if (!isset($catalog_row_map[$catalog_item_id])) {
                return new WP_Error('invalid_furniture_items', 'Invalid furniture item selection.');
            }

            $catalog_row = $catalog_row_map[$catalog_item_id];
            $discount_type = self::get_catalog_discount_type($catalog_row);
            $discount_value = self::get_catalog_discount_value($catalog_row);
            $fixed_price = $catalog_row->pricing_type === 'fixed' && $catalog_row->price_fixed !== null
                ? (float) $catalog_row->price_fixed
                : null;

            for ($index = 0; $index < intval($quantity); $index++) {
                $share_amounts = $fixed_price !== null
                    ? self::calculate_furniture_item_shares($discount_type, $discount_value, $fixed_price)
                    : [
                        'conference_share_amount' => null,
                        'store_share_amount' => null,
                    ];

                $result = $wpdb->insert($voucher_items_table, [
                    'voucher_id' => $voucher_id,
                    'catalog_item_id' => (int) $catalog_row->id,
                    'requested_item_name_snapshot' => $catalog_row->name,
                    'requested_category_snapshot' => $catalog_row->category,
                    'requested_pricing_type_snapshot' => $catalog_row->pricing_type,
                    'requested_price_min_snapshot' => $catalog_row->price_min,
                    'requested_price_max_snapshot' => $catalog_row->price_max,
                    'requested_price_fixed_snapshot' => $catalog_row->price_fixed,
                    'discount_type_snapshot' => $discount_type,
                    'discount_value_snapshot' => number_format($discount_value, 2, '.', ''),
                    'conference_share_amount' => $share_amounts['conference_share_amount'],
                    'store_share_amount' => $share_amounts['store_share_amount'],
                    'requested_sort_order_snapshot' => (int) $catalog_row->sort_order,
                    'status' => 'requested',
                ]);

                if ($result === false) {
                    return new WP_Error('database_error', 'Failed to save requested furniture items.');
                }
            }
        }

        return true;
    }

    /**
     * Remove partially created furniture records when one of the child inserts fails.
     *
     * @param int $voucher_id Root voucher ID.
     * @return void
     */
    private static function cleanup_failed_furniture_voucher($voucher_id) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'svdp_voucher_items', ['voucher_id' => intval($voucher_id)]);
        $wpdb->delete($wpdb->prefix . 'svdp_furniture_voucher_meta', ['voucher_id' => intval($voucher_id)]);
        $wpdb->delete($wpdb->prefix . 'svdp_vouchers', ['id' => intval($voucher_id)]);
    }

    /**
     * Calculate the next eligible date for the requesting organization.
     *
     * @param object $conference_obj Conference row.
     * @return DateTime
     */
    private static function get_next_eligible_date($conference_obj) {
        $eligibility_days = !empty($conference_obj->eligibility_days) ? intval($conference_obj->eligibility_days) : 90;
        $next_eligible = new DateTime();
        $next_eligible->modify('+' . $eligibility_days . ' days');
        return $next_eligible;
    }

    /**
     * Fetch aggregate furniture item progress counts keyed by voucher ID.
     *
     * @param array $voucher_ids Voucher IDs.
     * @return array
     */
    private static function get_furniture_item_progress_map($voucher_ids) {
        global $wpdb;
        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';

        $voucher_ids = array_values(array_filter(array_map('intval', (array) $voucher_ids)));
        if (empty($voucher_ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($voucher_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT
                voucher_id,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) AS requested,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
             FROM $voucher_items_table
             WHERE voucher_id IN ($placeholders)
             GROUP BY voucher_id",
            $voucher_ids
        );

        $rows = $wpdb->get_results($query);
        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row->voucher_id] = [
                'total' => (int) $row->total,
                'requested' => (int) $row->requested,
                'completed' => (int) $row->completed,
                'cancelled' => (int) $row->cancelled,
            ];
        }

        return $map;
    }

    /**
     * Fetch a furniture voucher's requested item snapshots for read-only cashier detail views.
     *
     * @param int $voucher_id Voucher ID.
     * @return array
     */
    private static function get_furniture_voucher_items($voucher_id) {
        return SVDP_Furniture_Voucher::get_voucher_items(intval($voucher_id));
    }

    /**
     * Create a denied voucher record for tracking
     */
    public static function create_denied_voucher($request) {
        $params = self::get_request_data($request);
        
        $first_name = sanitize_text_field($params['firstName']);
        $last_name = sanitize_text_field($params['lastName']);
        $dob = sanitize_text_field($params['dob']);
        $adults = intval($params['adults']);
        $children = intval($params['children']);
        $conference = sanitize_text_field($params['conference']);
        $vincentian_name = isset($params['vincentianName']) ? sanitize_text_field($params['vincentianName']) : null;
        $vincentian_email = isset($params['vincentianEmail']) ? sanitize_email($params['vincentianEmail']) : null;
        $denial_reason = sanitize_text_field($params['denialReason']);
        $created_by = isset($params['createdBy']) ? sanitize_text_field($params['createdBy']) : 'Vincentian';
        $voucher_type = isset($params['voucherType'])
            ? self::normalize_voucher_type(sanitize_text_field($params['voucherType']))
            : 'clothing';
        
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';
        
        // Get conference by slug or name
        $conference_obj = SVDP_Conference::get_by_slug($conference);
        if (!$conference_obj) {
            $conference_obj = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}svdp_conferences WHERE name = %s",
                $conference
            ));
        }
        
        if (!$conference_obj) {
            return new WP_Error('invalid_conference', 'Conference not found');
        }
        
        // Calculate voucher value based on conference type
        $household_size = $adults + $children;
        if ($conference_obj->is_emergency) {
            $voucher_value = $household_size * 10;
        } else {
            $voucher_value = $household_size * 20;
        }
        
        // Insert denied voucher
        $result = $wpdb->insert($table, [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'dob' => $dob,
            'adults' => $adults,
            'children' => $children,
            'conference_id' => $conference_obj->id,
            'vincentian_name' => $vincentian_name,
            'vincentian_email' => $vincentian_email,
            'created_by' => $created_by,
            'voucher_created_date' => current_time('Y-m-d'),
            'voucher_value' => $voucher_value,
            'voucher_type' => $voucher_type,
            'status' => 'Denied',
            'denial_reason' => $denial_reason,
        ]);
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create denied voucher record');
        }
        
        $voucher_id = $wpdb->insert_id;
        
        return [
            'success' => true,
            'voucher_id' => $voucher_id,
            'message' => 'Denied voucher recorded for tracking',
        ];
    }
    
    /**
     * Update voucher status
     */
    public static function update_status($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        $id = intval($request['id']);
        $params = self::get_request_data($request);
        $status = sanitize_text_field($params['status']);

        $update_data = ['status' => $status];

        if ($status === 'Redeemed') {
            $update_data['redeemed_date'] = date('Y-m-d');

            // Get item counts if provided
            $items_adult = isset($params['items_adult']) ? intval($params['items_adult']) : 0;
            $items_children = isset($params['items_children']) ? intval($params['items_children']) : 0;

            // Calculate redemption value
            $item_values = SVDP_Settings::get_item_values();
            $redemption_value = ($items_adult * $item_values['adult']) + ($items_children * $item_values['child']);

            // Store redemption data
            $update_data['items_adult_redeemed'] = $items_adult;
            $update_data['items_children_redeemed'] = $items_children;
            $update_data['redemption_total_value'] = $redemption_value;
        }

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update status', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'redemption_value' => isset($redemption_value) ? number_format($redemption_value, 2) : null
        ]);
    }
    
    /**
     * Update coat status with household counts
     */
    public static function update_coat_status($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        // ID comes from URL path, adults/children from JSON body
        $id = intval($request['id']);
        $params = self::get_request_data($request);
        $adults = intval($params['adults']);
        $children = intval($params['children']);
    
        // Validate that at least one coat is being issued
        if ($adults < 0 || $children < 0) {
            return new WP_Error('invalid_input', 'Invalid coat counts', ['status' => 400]);
        }
    
        if ($adults === 0 && $children === 0) {
            return new WP_Error('invalid_input', 'Must issue at least one coat', ['status' => 400]);
        }

        // Check coat eligibility before allowing issuance
        $voucher = $wpdb->get_row($wpdb->prepare(
            "SELECT coat_issued_date FROM $table WHERE id = %d",
            $id
        ));

        if ($voucher && !empty($voucher->coat_issued_date)) {
            // Check if coat can be issued based on August 1st reset
            if (!self::can_issue_coat($voucher->coat_issued_date)) {
                // Calculate next eligible date
                $today = new DateTime();
                $current_month = (int)$today->format('m');
                $next_august = new DateTime();

                if ($current_month >= 8) {
                    $next_august->modify('+1 year');
                }
                $next_august->setDate((int)$next_august->format('Y'), 8, 1);

                return new WP_Error(
                    'coat_not_eligible',
                    'This household already received a coat this season. Next eligible date: ' . $next_august->format('F j, Y'),
                    ['status' => 403]
                );
            }
        }

        $update_data = [
            'coat_status' => 'Issued',
            'coat_issued_date' => date('Y-m-d'),
            'coat_adults_issued' => $adults,
            'coat_children_issued' => $children,
        ];
    
        $result = $wpdb->update($table, $update_data, ['id' => $id]);
    
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update coat status', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'adults' => $adults,
            'children' => $children,
            'total' => $adults + $children
        ]);
    }

    /**
     * Send notification email to conference
     */
    private static function send_conference_notification($voucher_id) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        
        // Get voucher with conference info
        $voucher = $wpdb->get_row($wpdb->prepare("
            SELECT v.*, c.name as conference_name, c.notification_email
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            WHERE v.id = %d
        ", $voucher_id));
        
        if (!$voucher || empty($voucher->notification_email)) {
            return; // No email configured for this conference
        }
        
        // Skip email for Emergency conference (cashier station)
        if (empty($voucher->vincentian_name) || empty($voucher->vincentian_email)) {
            return;
        }

        $furniture_meta = null;
        if (self::is_furniture_voucher_type($voucher->voucher_type)) {
            $furniture_meta = $wpdb->get_row($wpdb->prepare(
                "SELECT *
                 FROM {$wpdb->prefix}svdp_furniture_voucher_meta
                 WHERE voucher_id = %d",
                $voucher_id
            ));
        }
        
        // Calculate expiration date (30 days from creation)
        $created = new DateTime($voucher->voucher_created_date);
        $expires = clone $created;
        $expires->modify('+30 days');
        
        // Build email
        $to = $voucher->notification_email;
        $voucher_type_label = ucfirst(self::normalize_voucher_type($voucher->voucher_type));
        $subject = 'New ' . $voucher_type_label . ' Voucher Created - ' . $voucher->first_name . ' ' . $voucher->last_name;
        
        $household_size = intval($voucher->adults) + intval($voucher->children);
        $voucher_amount = floatval($voucher->voucher_value);
        
        $message = self::get_email_template($voucher, $created, $expires, $household_size, $voucher_amount, $furniture_meta);
        
        // Email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
        ];
        
        // Send email
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Generate email template
     */
    private static function get_email_template($voucher, $created, $expires, $household_size, $voucher_amount, $furniture_meta = null) {
        $voucher_type = self::normalize_voucher_type($voucher->voucher_type);
        $is_furniture = $voucher_type === 'furniture';
        $requestor_portion_display = $is_furniture && $furniture_meta
            ? self::format_money_range(
                $furniture_meta->estimated_requestor_portion_min !== null ? (float) $furniture_meta->estimated_requestor_portion_min : null,
                $furniture_meta->estimated_requestor_portion_max !== null ? (float) $furniture_meta->estimated_requestor_portion_max : null
            )
            : '$' . number_format($voucher_amount, 2);
        $delivery_address = $is_furniture && $furniture_meta
            ? self::format_delivery_address([
                $furniture_meta->delivery_address_line_1 ?? '',
                $furniture_meta->delivery_address_line_2 ?? '',
                $furniture_meta->delivery_city ?? '',
                $furniture_meta->delivery_state ?? '',
                $furniture_meta->delivery_zip ?? '',
            ])
            : '';
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #006BA8; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
                .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #006BA8; }
                .label { font-weight: bold; color: #006BA8; }
                .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
                .highlight { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2 style="margin: 0;">New Voucher Created</h2>
                    <p style="margin: 5px 0 0 0;"><?php echo esc_html($voucher->conference_name); ?></p>
                </div>
                
                <div class="content">
                    <p>A new virtual <?php echo esc_html($voucher_type); ?> voucher has been created for your conference.</p>
                    
                    <div class="info-box">
                        <p><span class="label">Neighbor:</span> <?php echo esc_html($voucher->first_name . ' ' . $voucher->last_name); ?></p>
                        <p><span class="label">Date of Birth:</span> <?php echo esc_html($voucher->dob); ?></p>
                        <p><span class="label">Household Size:</span> <?php echo esc_html($household_size); ?> (<?php echo esc_html($voucher->adults); ?> adults, <?php echo esc_html($voucher->children); ?> children)</p>
                        <p><span class="label">Voucher Type:</span> <?php echo esc_html(ucfirst($voucher_type)); ?></p>
                        <?php if ($is_furniture): ?>
                            <p><span class="label">Requested Items:</span> <?php echo esc_html(intval($voucher->voucher_items_count)); ?></p>
                            <p><span class="label">Estimated Requestor Portion:</span> <?php echo esc_html($requestor_portion_display); ?></p>
                        <?php else: ?>
                            <p><span class="label">Voucher Amount:</span> <?php echo esc_html($requestor_portion_display); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-box">
                        <p><span class="label">Created:</span> <?php echo $created->format('l, F j, Y \a\t g:i A'); ?></p>
                        <p><span class="label">Expires:</span> <?php echo $expires->format('l, F j, Y'); ?></p>
                    </div>

                    <?php if ($is_furniture && $furniture_meta): ?>
                        <div class="info-box">
                            <p><span class="label">Delivery:</span> <?php echo !empty($furniture_meta->delivery_required) ? 'Yes' : 'No'; ?></p>
                            <?php if (!empty($furniture_meta->delivery_required) && $delivery_address !== ''): ?>
                                <p><span class="label">Delivery Address:</span> <?php echo esc_html($delivery_address); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="highlight">
                        <p style="margin: 0;"><strong>⏱️ Reminder:</strong> This voucher is valid for 30 days and must be used before the expiration date.</p>
                    </div>
                    
                    <div class="info-box">
                        <p><span class="label">Created By:</span> <?php echo esc_html($voucher->vincentian_name); ?></p>
                        <p><span class="label">Vincentian Email:</span> <a href="mailto:<?php echo esc_attr($voucher->vincentian_email); ?>"><?php echo esc_html($voucher->vincentian_email); ?></a></p>
                    </div>
                    
                    <p><strong>What the Neighbor needs to know:</strong></p>
                    <?php if ($is_furniture): ?>
                        <ul>
                            <li>The requested items are saved and visible to the cashier team.</li>
                            <li>Final fulfilled pricing may vary from the estimate range.</li>
                            <li>Delivery details are included when requested.</li>
                        </ul>
                    <?php else: ?>
                        <ul>
                            <li>Thrift Store hours: 9:30 AM – 4:00 PM</li>
                            <li>Stop by Customer Service before shopping</li>
                            <li>Voucher expires in 30 days</li>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="footer">
                    <p>This is an automated notification from the SVdP Virtual Voucher System.</p>
                    <p>For questions, please contact the Vincentian listed above.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Format a price as either a fixed amount or a range.
     *
     * @param float|null  $min Minimum price.
     * @param float|null  $max Maximum price.
     * @param float|null  $fixed Fixed price.
     * @param string|null $pricing_type Pricing type.
     * @return string
     */
    private static function format_money_range($min, $max, $fixed = null, $pricing_type = 'range') {
        if ($pricing_type === 'fixed' && $fixed !== null) {
            return '$' . number_format((float) $fixed, 2);
        }

        $min = $min !== null ? (float) $min : 0.0;
        $max = $max !== null ? (float) $max : $min;

        if (abs($max - $min) < 0.01) {
            return '$' . number_format($min, 2);
        }

        return '$' . number_format($min, 2) . ' - $' . number_format($max, 2);
    }

    /**
     * Collapse delivery address parts into one display string.
     *
     * @param array $parts Address fragments.
     * @return string
     */
    private static function format_delivery_address($parts) {
        $parts = array_values(array_filter(array_map(function($part) {
            return trim((string) $part);
        }, (array) $parts)));

        return implode(', ', $parts);
    }

    /**
     * Fetch only the identity fields needed for neighbor preference lookups.
     *
     * @param int $voucher_id Voucher ID.
     * @return array<string, mixed>|null
     */
    private static function get_voucher_identity_row($voucher_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        $voucher_id = intval($voucher_id);
        if ($voucher_id <= 0) {
            return null;
        }

        $voucher = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, first_name, last_name, dob
                 FROM $table
                 WHERE id = %d
                 LIMIT 1",
                $voucher_id
            ),
            ARRAY_A
        );

        return is_array($voucher) ? $voucher : null;
    }

    /**
     * Normalize voucher identity fields from array- or object-shaped payloads.
     *
     * @param array|object $voucher Voucher row or formatted voucher payload.
     * @return array<string, string>|null
     */
    private static function extract_voucher_identity($voucher) {
        if (is_object($voucher)) {
            $voucher = (array) $voucher;
        }

        if (!is_array($voucher)) {
            return null;
        }

        $identity = SVDP_Neighbor_Delivery_Preferences::normalize_identity_fields(
            $voucher['first_name'] ?? $voucher['firstName'] ?? '',
            $voucher['last_name'] ?? $voucher['lastName'] ?? '',
            $voucher['dob'] ?? ''
        );

        if ($identity['first_name'] === '' || $identity['last_name'] === '' || $identity['dob'] === '') {
            return null;
        }

        return [
            'first_name' => $identity['first_name'],
            'last_name' => $identity['last_name'],
            'dob' => $identity['dob'],
        ];
    }

    /**
     * Merge JSON and form parameters so REST and HTMX form posts both work.
     */
    private static function get_request_data($request) {
        $json_params = $request->get_json_params();
        if (!is_array($json_params)) {
            $json_params = [];
        }

        return array_merge($request->get_params(), $json_params);
    }

    /**
     * Support legacy clothing rows saved before voucher_type was normalized.
     */
    private static function build_duplicate_voucher_type_clause($voucher_type) {
        $normalized_type = self::normalize_voucher_type($voucher_type);

        if ($normalized_type === 'clothing') {
            return "(v.voucher_type = 'clothing' OR v.voucher_type = 'regular' OR v.voucher_type = '' OR v.voucher_type IS NULL)";
        }

        return "(v.voucher_type = 'furniture' OR v.voucher_type = 'household')";
    }

    /**
     * Format a voucher row for cashier-facing APIs and templates.
     */
    private static function format_cashier_voucher($voucher, $item_progress = null, $furniture_items = []) {
        $normalized_voucher_type = self::normalize_voucher_type($voucher->voucher_type);
        $is_furniture = $normalized_voucher_type === 'furniture';
        $created = new DateTime($voucher->voucher_created_date);
        $expiration = clone $created;
        $expiration->modify('+30 days');
        $today = new DateTime();

        $is_expired = ($today > $expiration && $voucher->status === 'Active');
        $coat_eligible = !$is_furniture;
        $coat_eligible_after = null;

        if (!$is_furniture && !empty($voucher->coat_issued_date)) {
            $coat_eligible = self::can_issue_coat($voucher->coat_issued_date);
            if (!$coat_eligible) {
                $next_august = new DateTime();
                $current_month = (int) $next_august->format('m');

                if ($current_month >= 8) {
                    $next_august->modify('+1 year');
                }

                $next_august->setDate((int) $next_august->format('Y'), 8, 1);
                $coat_eligible_after = $next_august->format('Y-m-d');
            }
        }

        if ($is_furniture && $item_progress === null) {
            $item_progress = [
                'total' => (int) ($voucher->voucher_items_count ?? 0),
                'requested' => (int) ($voucher->voucher_items_count ?? 0),
                'completed' => 0,
                'cancelled' => 0,
            ];
        }

        $workflow_status = $voucher->workflow_status ?? 'submitted';
        if ($is_furniture && ($voucher->status ?? '') === 'Redeemed') {
            $workflow_status = 'completed';
        } elseif ($is_furniture) {
            if (!empty($item_progress['total']) && intval($item_progress['requested']) === 0) {
                $workflow_status = 'ready_for_completion';
            } elseif ((intval($item_progress['completed']) + intval($item_progress['cancelled'])) > 0) {
                $workflow_status = 'in_progress';
            } else {
                $workflow_status = 'submitted';
            }
        }

        $receipt_file_path = $is_furniture ? ($voucher->receipt_file_path ?? null) : null;
        $invoice_file_path = $is_furniture
            ? ($voucher->invoice_stored_file_path ?? $voucher->invoice_file_path ?? null)
            : null;

        $delivery_address_parts = [
            $voucher->delivery_address_line_1 ?? '',
            $voucher->delivery_address_line_2 ?? '',
            $voucher->delivery_city ?? '',
            $voucher->delivery_state ?? '',
            $voucher->delivery_zip ?? '',
        ];
        $estimated_total_min = isset($voucher->estimated_total_min) && $voucher->estimated_total_min !== null ? (float) $voucher->estimated_total_min : null;
        $estimated_total_max = isset($voucher->estimated_total_max) && $voucher->estimated_total_max !== null ? (float) $voucher->estimated_total_max : null;
        $estimated_requestor_portion_min = isset($voucher->estimated_requestor_portion_min) && $voucher->estimated_requestor_portion_min !== null ? (float) $voucher->estimated_requestor_portion_min : null;
        $estimated_requestor_portion_max = isset($voucher->estimated_requestor_portion_max) && $voucher->estimated_requestor_portion_max !== null ? (float) $voucher->estimated_requestor_portion_max : null;

        return [
            'id' => (int) $voucher->id,
            'first_name' => $voucher->first_name,
            'last_name' => $voucher->last_name,
            'dob' => $voucher->dob,
            'adults' => (int) $voucher->adults,
            'children' => (int) $voucher->children,
            'voucher_value' => (float) $voucher->voucher_value,
            'voucher_type' => $normalized_voucher_type,
            'voucher_type_label' => ucfirst($normalized_voucher_type),
            'voucher_items_count' => (int) ($voucher->voucher_items_count ?? 0),
            'conference_id' => isset($voucher->conference_id) ? (int) $voucher->conference_id : 0,
            'conference_name' => $voucher->conference_name,
            'vincentian_name' => $voucher->vincentian_name,
            'vincentian_email' => $voucher->vincentian_email,
            'created_by' => $voucher->created_by,
            'voucher_created_date' => $voucher->voucher_created_date,
            'status' => $is_expired ? 'Expired' : $voucher->status,
            'workflow_status' => $workflow_status,
            'workflow_status_label' => self::format_workflow_status_label($workflow_status),
            'redeemed_date' => $voucher->redeemed_date,
            'override_note' => $voucher->override_note,
            'items_adult_redeemed' => (int) ($voucher->items_adult_redeemed ?? 0),
            'items_children_redeemed' => (int) ($voucher->items_children_redeemed ?? 0),
            'redemption_total_value' => isset($voucher->redemption_total_value) ? (float) $voucher->redemption_total_value : null,
            'coat_status' => $voucher->coat_status,
            'coat_issued_date' => $voucher->coat_issued_date,
            'coat_adults_issued' => isset($voucher->coat_adults_issued) ? (int) $voucher->coat_adults_issued : null,
            'coat_children_issued' => isset($voucher->coat_children_issued) ? (int) $voucher->coat_children_issued : null,
            'coat_eligible' => $coat_eligible,
            'coat_eligible_after' => $coat_eligible_after,
            'delivery_required' => $is_furniture ? !empty($voucher->delivery_required) : false,
            'delivery_fee' => isset($voucher->delivery_fee) && $voucher->delivery_fee !== null ? (float) $voucher->delivery_fee : 0.0,
            'delivery_address' => $is_furniture ? [
                'line_1' => $voucher->delivery_address_line_1 ?? null,
                'line_2' => $voucher->delivery_address_line_2 ?? null,
                'city' => $voucher->delivery_city ?? null,
                'state' => $voucher->delivery_state ?? null,
                'zip' => $voucher->delivery_zip ?? null,
            ] : null,
            'delivery_address_display' => $is_furniture ? self::format_delivery_address($delivery_address_parts) : '',
            'estimated_total_min' => $estimated_total_min,
            'estimated_total_max' => $estimated_total_max,
            'estimated_total_display' => $is_furniture ? self::format_money_range($estimated_total_min, $estimated_total_max) : '',
            'estimated_requestor_portion_min' => $estimated_requestor_portion_min,
            'estimated_requestor_portion_max' => $estimated_requestor_portion_max,
            'estimated_requestor_portion_display' => $is_furniture
                ? self::format_money_range($estimated_requestor_portion_min, $estimated_requestor_portion_max)
                : '',
            'furniture_completed_at' => $is_furniture ? ($voucher->furniture_completed_at ?? null) : null,
            'receipt_file_path' => $receipt_file_path,
            'receipt_file_url' => $is_furniture ? SVDP_Furniture_Receipt::public_url_from_relative_path($receipt_file_path) : null,
            'invoice_file_path' => $invoice_file_path,
            'invoice_file_url' => $is_furniture ? SVDP_Invoice::public_url_from_relative_path($invoice_file_path) : null,
            'invoice_number' => $is_furniture ? ($voucher->invoice_number ?? null) : null,
            'invoice_date' => $is_furniture ? ($voucher->invoice_date ?? null) : null,
            'invoice_amount' => $is_furniture && isset($voucher->invoice_amount) ? (float) $voucher->invoice_amount : null,
            'invoice_delivery_fee' => $is_furniture && isset($voucher->invoice_delivery_fee) ? (float) $voucher->invoice_delivery_fee : null,
            'invoice_items_total' => $is_furniture && isset($voucher->invoice_items_total) ? (float) $voucher->invoice_items_total : null,
            'invoice_conference_share_total' => $is_furniture && isset($voucher->conference_share_total) ? (float) $voucher->conference_share_total : null,
            'item_progress' => $is_furniture ? $item_progress : null,
            'remaining_items' => $is_furniture ? intval($item_progress['requested'] ?? 0) : null,
            'items' => $is_furniture ? $furniture_items : [],
        ];
    }

    /**
     * Format the internal furniture workflow badge label.
     *
     * @param string $workflow_status Workflow status slug.
     * @return string
     */
    private static function format_workflow_status_label($workflow_status) {
        $labels = [
            'submitted' => 'Submitted',
            'in_progress' => 'In Progress',
            'ready_for_completion' => 'Ready',
            'completed' => 'Completed',
        ];

        return $labels[$workflow_status] ?? ucfirst(str_replace('_', ' ', (string) $workflow_status));
    }
}
