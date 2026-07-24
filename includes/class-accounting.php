<?php
/**
 * Recoverable monthly accounting orchestration and QuickBooks Desktop exports.
 */
class SVDP_Accounting {
    const CRON_HOOK = 'svdp_accounting_daily_reconciliation';
    const BASE_SUBDIR = 'svdp-vouchers/accounting';

    public static function ensure_schedule() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(self::next_two_am(), 'daily', self::CRON_HOOK);
        }
    }

    public static function clear_schedule() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    private static function next_two_am() {
        $timezone = wp_timezone();
        $next = new DateTime('today 02:00:00', $timezone);
        if ($next->getTimestamp() <= time()) {
            $next->modify('+1 day');
        }
        return $next->getTimestamp();
    }

    public static function reconcile() {
        $now = new DateTime('now', wp_timezone());
        if ((int) $now->format('j') < 1 || (int) $now->format('G') < 2) {
            return;
        }
        self::run_monthly('cron', 0);
        self::retry_failed_statement_emails();
    }

    public static function run_monthly($source = 'manual', $user_id = null) {
        global $wpdb;
        $user_id = $user_id === null ? get_current_user_id() : (int) $user_id;
        $cutoff = new DateTime('last day of previous month', wp_timezone());
        $batch_key = 'monthly-' . $cutoff->format('Y-m');
        $table = $wpdb->prefix . 'svdp_accounting_batches';
        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $table (batch_key, batch_type, cutoff_date, status, trigger_source, triggered_by_user_id)
             VALUES (%s, 'monthly', %s, 'processing', %s, %d)",
            $batch_key, $cutoff->format('Y-m-d'), sanitize_key($source), $user_id
        ));
        $batch = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE batch_key = %s", $batch_key));
        if (!$batch || (!$inserted && $batch->status !== 'processing')) {
            return $batch ? self::format_batch($batch) : new WP_Error('accounting_batch_failed', 'The monthly accounting batch could not be created.');
        }

        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT conference_id, MIN(invoice_date) period_start
             FROM {$wpdb->prefix}svdp_invoices
             WHERE statement_id IS NULL AND invoice_date <= %s
             GROUP BY conference_id",
            $cutoff->format('Y-m-d')
        ));
        $errors = [];
        foreach ($groups as $group) {
            $result = SVDP_Statement::generate_statement([
                'conferenceId' => (int) $group->conference_id,
                'periodStart' => $group->period_start,
                'periodEnd' => $cutoff->format('Y-m-d'),
            ]);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
                continue;
            }
            if ($result instanceof WP_REST_Response) {
                $result = $result->get_data();
            }
            $statement_id = (int) $result['statementId'];
            $pdf = self::create_statement_pdf($statement_id);
            if (is_wp_error($pdf)) {
                $errors[] = $pdf->get_error_message();
            }
            self::send_statement($statement_id);
        }

        $export = self::create_export($batch->id);
        $status = is_wp_error($export) ? 'blocked' : (empty($errors) ? 'completed' : 'partial');
        $wpdb->update($table, [
            'status' => $status,
            'completed_at' => current_time('mysql'),
            'email_last_error' => is_wp_error($export) ? $export->get_error_message() : implode('; ', $errors),
        ], ['id' => $batch->id]);
        self::audit('monthly_batch', 'batch', $batch->id, sprintf('Monthly accounting batch %s finished with status %s.', $batch_key, $status), null, null, $errors);
        return self::format_batch($wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $batch->id)));
    }

    public static function create_statement_pdf($statement_id) {
        global $wpdb;
        $statement = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}svdp_invoice_statements WHERE id = %d", $statement_id));
        if (!$statement || empty($statement->stored_file_path)) {
            return new WP_Error('statement_document_missing', 'The statement HTML document is unavailable.');
        }
        if (!class_exists('Dompdf\\Dompdf')) {
            return new WP_Error('pdf_dependency_missing', 'The PDF renderer is unavailable.');
        }
        $uploads = wp_upload_dir();
        $html_path = trailingslashit($uploads['basedir']) . ltrim($statement->stored_file_path, '/');
        $html = file_exists($html_path) ? file_get_contents($html_path) : false;
        if ($html === false) {
            return new WP_Error('statement_document_missing', 'The statement HTML document could not be read.');
        }
        $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter');
        $dompdf->render();
        $relative = trailingslashit(dirname($statement->stored_file_path)) . sanitize_file_name(strtolower($statement->statement_number) . '.pdf');
        $absolute = trailingslashit($uploads['basedir']) . $relative;
        if (file_put_contents($absolute, $dompdf->output()) === false) {
            return new WP_Error('statement_pdf_write_failed', 'The statement PDF could not be stored.');
        }
        $wpdb->update($wpdb->prefix . 'svdp_invoice_statements', ['pdf_file_path' => $relative], ['id' => $statement_id]);
        return $absolute;
    }

    public static function send_statement($statement_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_invoice_statements';
        $statement = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.name conference_name, c.billing_email, c.notification_email
             FROM $table s JOIN {$wpdb->prefix}svdp_conferences c ON c.id=s.conference_id WHERE s.id=%d",
            $statement_id
        ));
        if (!$statement || $statement->email_status === 'sent') {
            return true;
        }
        $to = sanitize_email($statement->billing_email ?: $statement->notification_email);
        $updates = ['email_to_snapshot' => $to, 'email_attempts' => (int) $statement->email_attempts + 1, 'email_last_attempt_at' => current_time('mysql')];
        if (!$to) {
            $updates['email_status'] = 'missing_recipient';
            $updates['email_last_error'] = 'No billing or notification email is configured.';
            $wpdb->update($table, $updates, ['id' => $statement_id]);
            return false;
        }
        $uploads = wp_upload_dir();
        $attachment = $statement->pdf_file_path ? trailingslashit($uploads['basedir']) . $statement->pdf_file_path : self::create_statement_pdf($statement_id);
        $sent = !is_wp_error($attachment) && wp_mail($to, 'Statement ' . $statement->statement_number, 'Attached is your SVdP voucher statement.', [], [$attachment]);
        $updates['email_status'] = $sent ? 'sent' : 'failed';
        $updates['email_sent_at'] = $sent ? current_time('mysql') : null;
        $updates['email_last_error'] = $sent ? null : (is_wp_error($attachment) ? $attachment->get_error_message() : 'WordPress mail delivery failed.');
        $wpdb->update($table, $updates, ['id' => $statement_id]);
        self::audit('statement_email', 'statement', $statement_id, sprintf('Statement %s email %s for %s.', $statement->statement_number, $updates['email_status'], $statement->conference_name), null, $updates, $updates['email_last_error']);
        return $sent;
    }

    public static function retry_failed_statement_emails() {
        global $wpdb;
        $ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}svdp_invoice_statements WHERE email_status IN ('failed','missing_recipient') AND email_attempts < 10");
        foreach ($ids as $id) {
            self::send_statement((int) $id);
        }
    }

    public static function create_export($batch_id) {
        global $wpdb;
        $required = ['bookkeeping_email', 'quickbooks_ar_account', 'quickbooks_income_account', 'quickbooks_voucher_item', 'quickbooks_delivery_item'];
        $settings = [];
        foreach ($required as $key) {
            $settings[$key] = trim((string) SVDP_Settings::get_setting($key, ''));
        }
        if (in_array('', $settings, true)) {
            return new WP_Error('accounting_configuration_missing', 'Bookkeeping email and all QuickBooks mappings must be configured.');
        }
        $statements = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, c.name conference_name, c.quickbooks_customer_name
             FROM {$wpdb->prefix}svdp_invoice_statements s
             JOIN {$wpdb->prefix}svdp_conferences c ON c.id=s.conference_id
             WHERE s.accounting_batch_id IS NULL AND s.generated_at <= (SELECT CONCAT(cutoff_date,' 23:59:59') FROM {$wpdb->prefix}svdp_accounting_batches WHERE id=%d)",
            $batch_id
        ));
        if (!$statements) {
            return true;
        }
        $statement_ids = array_map(fn($s) => (int) $s->id, $statements);
        $placeholders = implode(',', array_fill(0, count($statement_ids), '%d'));
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, v.first_name, v.last_name, s.statement_number, s.email_status statement_email_status, s.email_last_error statement_email_error, c.name conference_name, c.quickbooks_customer_name
             FROM {$wpdb->prefix}svdp_invoices i
             JOIN {$wpdb->prefix}svdp_vouchers v ON v.id=i.voucher_id
             JOIN {$wpdb->prefix}svdp_invoice_statements s ON s.id=i.statement_id
             JOIN {$wpdb->prefix}svdp_conferences c ON c.id=i.conference_id
             WHERE i.statement_id IN ($placeholders) ORDER BY i.invoice_date,i.id",
            ...$statement_ids
        ));
        $files = self::write_export_files($batch_id, $invoices, $settings);
        if (is_wp_error($files)) {
            return $files;
        }
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}svdp_invoice_statements SET accounting_batch_id=%d WHERE id IN ($placeholders) AND accounting_batch_id IS NULL", $batch_id, ...$statement_ids));
        $total = array_sum(array_map(fn($i) => (float) $i->amount, $invoices));
        $wpdb->update($wpdb->prefix . 'svdp_accounting_batches', [
            'iif_file_path' => $files['iif_relative'], 'manifest_file_path' => $files['manifest_relative'],
            'statement_count' => count($statements), 'invoice_count' => count($invoices), 'total_amount' => $total,
            'bookkeeping_email_snapshot' => $settings['bookkeeping_email'],
        ], ['id' => $batch_id]);
        self::email_export($batch_id);
        return true;
    }

    private static function write_export_files($batch_id, $invoices, $settings) {
        $uploads = wp_upload_dir();
        $relative_dir = self::BASE_SUBDIR . '/batch-' . (int) $batch_id;
        $absolute_dir = trailingslashit($uploads['basedir']) . $relative_dir;
        if (!wp_mkdir_p($absolute_dir)) {
            return new WP_Error('accounting_storage_failed', 'The accounting export directory could not be created.');
        }
        $iif = "!TRNS\tTRNSTYPE\tDATE\tACCNT\tNAME\tAMOUNT\tDOCNUM\tMEMO\n!SPL\tTRNSTYPE\tDATE\tACCNT\tNAME\tAMOUNT\tDOCNUM\tMEMO\tINVITEM\n!ENDTRNS\n";
        $manifest = "Statement,Invoice,Organization,Neighbor,Amount,Email Status,Warning\n";
        foreach ($invoices as $invoice) {
            $customer = self::clean_iif($invoice->quickbooks_customer_name ?: $invoice->conference_name);
            $date = date('m/d/Y', strtotime($invoice->invoice_date));
            $memo = self::clean_iif($invoice->first_name . ' ' . $invoice->last_name . '; Voucher ' . $invoice->voucher_id . '; Statement ' . $invoice->statement_number);
            $amount = number_format((float) $invoice->amount, 2, '.', '');
            $iif .= "TRNS\tINVOICE\t$date\t" . self::clean_iif($settings['quickbooks_ar_account']) . "\t$customer\t$amount\t" . self::clean_iif($invoice->invoice_number) . "\t$memo\n";
            $lines = self::get_invoice_export_lines($invoice, $settings);
            foreach ($lines['lines'] as $line) {
                $line_amount = number_format(-1 * $line['amount'], 2, '.', '');
                $iif .= "SPL\tINVOICE\t$date\t" . self::clean_iif($settings['quickbooks_income_account']) . "\t$customer\t$line_amount\t" . self::clean_iif($invoice->invoice_number) . "\t" . self::clean_iif($line['description']) . "\t" . self::clean_iif($line['item']) . "\n";
            }
            $iif .= "ENDTRNS\n";
            $warnings = array_filter([$lines['warning'], $invoice->statement_email_error]);
            $manifest .= self::csv([$invoice->statement_number, $invoice->invoice_number, $invoice->conference_name, $invoice->first_name . ' ' . $invoice->last_name, $amount, $invoice->statement_email_status, implode('; ', $warnings)]) . "\n";
        }
        $iif_rel = $relative_dir . '/quickbooks-invoices.iif';
        $manifest_rel = $relative_dir . '/statement-manifest.csv';
        if (file_put_contents(trailingslashit($uploads['basedir']) . $iif_rel, $iif) === false || file_put_contents(trailingslashit($uploads['basedir']) . $manifest_rel, $manifest) === false) {
            return new WP_Error('accounting_export_write_failed', 'The QuickBooks package could not be written.');
        }
        return ['iif_relative' => $iif_rel, 'manifest_relative' => $manifest_rel];
    }

    private static function get_invoice_export_lines($invoice, $settings) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT l.requested_name_snapshot item_name, e.fulfilled_quantity, e.line_total
             FROM {$wpdb->prefix}svdp_voucher_requested_lines l
             JOIN {$wpdb->prefix}svdp_voucher_fulfillment_entries e ON e.requested_line_id=l.id
             WHERE l.voucher_id=%d AND e.fulfilled_quantity > 0 ORDER BY l.sort_order,l.id,e.id",
            $invoice->voucher_id
        ));
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = [
                'description' => $row->item_name . ' × ' . (int) $row->fulfilled_quantity,
                'amount' => round((float) $row->line_total * 0.5, 2),
                'item' => $settings['quickbooks_voucher_item'],
            ];
        }
        if ((float) $invoice->delivery_fee > 0) {
            $lines[] = ['description' => 'Delivery', 'amount' => (float) $invoice->delivery_fee, 'item' => $settings['quickbooks_delivery_item']];
        }
        $warning = '';
        if (!$lines) {
            $lines[] = ['description' => 'Voucher assistance – invoice detail unavailable', 'amount' => (float) $invoice->amount, 'item' => $settings['quickbooks_voucher_item']];
            $warning = 'Historical fulfillment detail unavailable; exported as a reconciled assistance line.';
        } else {
            $sum = array_sum(array_column($lines, 'amount'));
            $difference = round((float) $invoice->amount - $sum, 2);
            if ($difference != 0.0) {
                $last = count($lines) - 1;
                $lines[$last]['amount'] = round($lines[$last]['amount'] + $difference, 2);
                $warning = 'Final detail line includes a rounding reconciliation of ' . number_format($difference, 2) . '.';
            }
        }
        return ['lines' => $lines, 'warning' => $warning];
    }

    public static function email_export($batch_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_accounting_batches';
        $batch = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $batch_id));
        if (!$batch || !$batch->bookkeeping_email_snapshot || !$batch->iif_file_path) {
            return false;
        }
        $uploads = wp_upload_dir();
        $attachments = [trailingslashit($uploads['basedir']) . $batch->iif_file_path, trailingslashit($uploads['basedir']) . $batch->manifest_file_path];
        $sent = wp_mail($batch->bookkeeping_email_snapshot, 'SVdP QuickBooks package ' . $batch->batch_key . ' (part 1)', 'Attached are the QuickBooks invoice import and statement manifest.', [], $attachments);
        $pdf_paths = $wpdb->get_col($wpdb->prepare("SELECT pdf_file_path FROM {$wpdb->prefix}svdp_invoice_statements WHERE accounting_batch_id=%d AND pdf_file_path IS NOT NULL ORDER BY id", $batch_id));
        $parts = [];
        $part = [];
        $bytes = 0;
        foreach ($pdf_paths as $relative) {
            $absolute = trailingslashit($uploads['basedir']) . $relative;
            $size = file_exists($absolute) ? filesize($absolute) : 0;
            if ($part && $bytes + $size > 15 * 1024 * 1024) {
                $parts[] = $part;
                $part = [];
                $bytes = 0;
            }
            if ($size > 0 && $size <= 15 * 1024 * 1024) {
                $part[] = $absolute;
                $bytes += $size;
            }
        }
        if ($part) {
            $parts[] = $part;
        }
        foreach ($parts as $index => $pdf_part) {
            $part_sent = wp_mail($batch->bookkeeping_email_snapshot, 'SVdP QuickBooks package ' . $batch->batch_key . ' (part ' . ($index + 2) . ')', 'Attached are the statement PDFs for this accounting package.', [], $pdf_part);
            $sent = $sent && $part_sent;
        }
        $wpdb->update($table, ['email_status' => $sent ? 'sent' : 'failed', 'email_attempts' => (int) $batch->email_attempts + 1, 'email_sent_at' => $sent ? current_time('mysql') : null, 'email_last_error' => $sent ? null : 'WordPress mail delivery failed.'], ['id' => $batch_id]);
        return $sent;
    }

    public static function audit($event, $resource_type, $resource_id, $summary, $before = null, $after = null, $error = null) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'svdp_accounting_audit', [
            'event_type' => sanitize_key($event), 'decision' => $error ? 'deny' : 'allow',
            'resource_type' => sanitize_key($resource_type), 'resource_id' => $resource_id ?: null,
            'actor_user_id' => get_current_user_id() ?: null, 'actor_source' => get_current_user_id() ? 'user' : 'cron',
            'before_value' => $before === null ? null : wp_json_encode($before),
            'after_value' => $after === null ? null : wp_json_encode($after),
            'error_message' => is_array($error) ? implode('; ', $error) : $error,
            'human_summary' => sanitize_text_field($summary), 'created_at' => current_time('mysql'),
        ]);
    }

    private static function clean_iif($value) { return str_replace(["\t", "\r", "\n"], ' ', (string) $value); }
    private static function csv($values) { $h = fopen('php://temp', 'r+'); fputcsv($h, $values); rewind($h); return rtrim(stream_get_contents($h)); }
    private static function format_batch($batch) { return ['id'=>(int)$batch->id,'batchKey'=>$batch->batch_key,'status'=>$batch->status,'cutoffDate'=>$batch->cutoff_date]; }
}
