<?php
$conferences = SVDP_Conference::get_all(false);
?>

<div class="svdp-accounting-admin-section" id="svdp-invoices-tab">
    <div class="svdp-card">
        <h2>Stored Invoices</h2>
        <p>Filter stored furniture invoices by conference, invoice date, and statement status.</p>

        <form id="svdp-invoice-filter-form" class="svdp-furniture-form" novalidate>
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-invoice-conference"><strong>Conference</strong></label>
                    <select id="svdp-invoice-conference" name="conferenceId">
                        <option value="">All conferences</option>
                        <?php foreach ($conferences as $conference): ?>
                            <option value="<?php echo esc_attr($conference->id); ?>"><?php echo esc_html($conference->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-invoice-period-start"><strong>Invoice Date From</strong></label>
                    <input type="date" id="svdp-invoice-period-start" name="periodStart">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-invoice-period-end"><strong>Invoice Date To</strong></label>
                    <input type="date" id="svdp-invoice-period-end" name="periodEnd">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-invoice-statement-status"><strong>Statement Status</strong></label>
                    <select id="svdp-invoice-statement-status" name="statementStatus">
                        <option value="all">All invoices</option>
                        <option value="unstatemented">Unstatemented only</option>
                        <option value="statemented">Already statemented</option>
                    </select>
                </div>
            </div>

            <div class="svdp-inline-actions">
                <button type="submit" class="button button-primary">Apply Filters</button>
                <button type="button" class="button" id="svdp-reset-invoice-filters">Reset</button>
                <span class="svdp-inline-loading" id="svdp-invoice-loading" hidden>
                    <span class="spinner is-active"></span>
                    Loading invoices...
                </span>
            </div>
        </form>
    </div>

    <div class="svdp-card">
        <div class="svdp-inline-actions svdp-space-between">
            <h2>Invoice Results</h2>
            <div class="svdp-results-summary" id="svdp-invoice-summary">Loading invoices...</div>
        </div>

        <div class="notice notice-error inline" id="svdp-invoice-error" hidden></div>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Conference</th>
                    <th>Neighbor</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Statement</th>
                    <th>Document</th>
                </tr>
            </thead>
            <tbody id="svdp-invoice-results">
                <tr>
                    <td colspan="7">Loading invoices...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
