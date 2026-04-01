<?php
$conferences = SVDP_Conference::get_all(false);
$default_range = SVDP_Statement::get_default_period_range();
$recent_statements = SVDP_Statement::get_recent_statements(20);
?>

<div class="svdp-accounting-admin-section" id="svdp-statements-tab">
    <div class="svdp-card">
        <h2>Generate Statement</h2>
        <p>Create a stored accounting statement for one conference and date range. Only invoices without an existing statement attachment are eligible.</p>

        <form id="svdp-statement-form" class="svdp-furniture-form" novalidate>
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-statement-conference"><strong>Conference</strong></label>
                    <select id="svdp-statement-conference" name="conferenceId" required>
                        <option value="">Select a conference</option>
                        <?php foreach ($conferences as $conference): ?>
                            <option value="<?php echo esc_attr($conference->id); ?>"><?php echo esc_html($conference->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-statement-period-start"><strong>Period Start</strong></label>
                    <input
                        type="date"
                        id="svdp-statement-period-start"
                        name="periodStart"
                        value="<?php echo esc_attr($default_range['periodStart']); ?>"
                        required
                    >
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-statement-period-end"><strong>Period End</strong></label>
                    <input
                        type="date"
                        id="svdp-statement-period-end"
                        name="periodEnd"
                        value="<?php echo esc_attr($default_range['periodEnd']); ?>"
                        required
                    >
                </div>
            </div>

            <div class="svdp-inline-actions">
                <button type="button" class="button" id="svdp-preview-statement-invoices">Preview Eligible Invoices</button>
                <button type="submit" class="button button-primary">Generate Statement</button>
                <span class="svdp-inline-loading" id="svdp-statement-loading" hidden>
                    <span class="spinner is-active"></span>
                    Working...
                </span>
            </div>
        </form>

        <div class="notice notice-success inline" id="svdp-statement-success" hidden></div>
        <div class="notice notice-error inline" id="svdp-statement-error" hidden></div>
    </div>

    <div class="svdp-card">
        <div class="svdp-inline-actions svdp-space-between">
            <h2>Eligible Invoice Preview</h2>
            <div class="svdp-results-summary" id="svdp-statement-preview-summary">Select a conference to preview eligible invoices.</div>
        </div>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Neighbor</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Document</th>
                </tr>
            </thead>
            <tbody id="svdp-statement-preview-results">
                <tr>
                    <td colspan="5">Select a conference to preview eligible invoices.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="svdp-card">
        <h2>Recent Statements</h2>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Statement</th>
                    <th>Conference</th>
                    <th>Period</th>
                    <th>Invoices</th>
                    <th>Total</th>
                    <th>Generated</th>
                    <th>Document</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_statements)): ?>
                    <tr>
                        <td colspan="7">No statements have been generated yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_statements as $statement): ?>
                        <tr>
                            <td><?php echo esc_html($statement['statement_number']); ?></td>
                            <td><?php echo esc_html($statement['conference_name']); ?></td>
                            <td><?php echo esc_html($statement['period_start'] . ' to ' . $statement['period_end']); ?></td>
                            <td><?php echo esc_html($statement['invoice_count']); ?></td>
                            <td>$<?php echo esc_html(number_format((float) $statement['total_amount'], 2)); ?></td>
                            <td><?php echo esc_html($statement['generated_at']); ?></td>
                            <td>
                                <?php if (!empty($statement['stored_file_url'])): ?>
                                    <a href="<?php echo esc_url($statement['stored_file_url']); ?>" target="_blank" rel="noopener noreferrer">Open Statement</a>
                                <?php else: ?>
                                    <span class="description">Missing file</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
