(function($) {
    'use strict';

    $(document).ready(function() {
        initializeInvoicesTab();
        initializeStatementsTab();
    });

    function initializeInvoicesTab() {
        const form = $('#svdp-invoice-filter-form');
        if (form.length === 0) {
            return;
        }

        form.on('submit', function(event) {
            event.preventDefault();
            loadInvoices();
        });

        $('#svdp-reset-invoice-filters').on('click', function() {
            form[0].reset();
            $('#svdp-invoice-statement-status').val('all');
            loadInvoices();
        });

        loadInvoices();
    }

    function initializeStatementsTab() {
        const form = $('#svdp-statement-form');
        if (form.length === 0) {
            return;
        }

        loadDefaultStatementRange();

        $('#svdp-preview-statement-invoices').on('click', function() {
            previewEligibleInvoices();
        });

        $('#svdp-statement-conference, #svdp-statement-period-start, #svdp-statement-period-end').on('change', function() {
            clearStatementMessages();
        });

        form.on('submit', function(event) {
            event.preventDefault();
            generateStatement();
        });
    }

    function loadInvoices() {
        const loading = $('#svdp-invoice-loading');
        const errorBox = $('#svdp-invoice-error');

        loading.prop('hidden', false);
        errorBox.prop('hidden', true).empty();

        requestRest({
            url: svdpAdmin.restUrl + 'admin/invoices',
            method: 'GET',
            data: {
                conferenceId: $('#svdp-invoice-conference').val(),
                periodStart: $('#svdp-invoice-period-start').val(),
                periodEnd: $('#svdp-invoice-period-end').val(),
                statementStatus: $('#svdp-invoice-statement-status').val()
            }
        }).done(function(response) {
            renderInvoiceResults(response.invoices || []);
            const count = response.meta && typeof response.meta.count !== 'undefined' ? response.meta.count : 0;
            const totalAmount = response.meta && typeof response.meta.totalAmount !== 'undefined' ? response.meta.totalAmount : 0;
            $('#svdp-invoice-summary').text(count + ' invoice' + (count === 1 ? '' : 's') + ' found. Total: ' + formatCurrency(totalAmount));
        }).fail(function(xhr) {
            renderInvoiceResults([]);
            $('#svdp-invoice-summary').text('Unable to load invoices.');
            errorBox.html('<p>' + escapeHtml(getErrorMessage(xhr, 'Unable to load invoices.')) + '</p>').prop('hidden', false);
        }).always(function() {
            loading.prop('hidden', true);
        });
    }

    function previewEligibleInvoices() {
        const conferenceId = $('#svdp-statement-conference').val();
        const periodStart = $('#svdp-statement-period-start').val();
        const periodEnd = $('#svdp-statement-period-end').val();
        const loading = $('#svdp-statement-loading');
        const errorBox = $('#svdp-statement-error');

        clearStatementMessages();

        if (!conferenceId) {
            renderStatementPreview([]);
            $('#svdp-statement-preview-summary').text('Select a conference to preview eligible invoices.');
            return;
        }

        loading.prop('hidden', false);

        requestRest({
            url: svdpAdmin.restUrl + 'admin/invoices',
            method: 'GET',
            data: {
                conferenceId: conferenceId,
                periodStart: periodStart,
                periodEnd: periodEnd,
                statementStatus: 'unstatemented'
            }
        }).done(function(response) {
            const invoices = response.invoices || [];
            renderStatementPreview(invoices);

            const count = response.meta && typeof response.meta.count !== 'undefined' ? response.meta.count : invoices.length;
            const totalAmount = response.meta && typeof response.meta.totalAmount !== 'undefined' ? response.meta.totalAmount : 0;
            $('#svdp-statement-preview-summary').text(count + ' eligible invoice' + (count === 1 ? '' : 's') + '. Total: ' + formatCurrency(totalAmount));
        }).fail(function(xhr) {
            renderStatementPreview([]);
            $('#svdp-statement-preview-summary').text('Unable to preview eligible invoices.');
            errorBox.html('<p>' + escapeHtml(getErrorMessage(xhr, 'Unable to preview eligible invoices.')) + '</p>').prop('hidden', false);
        }).always(function() {
            loading.prop('hidden', true);
        });
    }

    function generateStatement() {
        const conferenceId = $('#svdp-statement-conference').val();
        const periodStart = $('#svdp-statement-period-start').val();
        const periodEnd = $('#svdp-statement-period-end').val();
        const loading = $('#svdp-statement-loading');
        const errorBox = $('#svdp-statement-error');
        const successBox = $('#svdp-statement-success');

        clearStatementMessages();

        if (!conferenceId) {
            errorBox.html('<p>Select a conference before generating a statement.</p>').prop('hidden', false);
            return;
        }

        if (!window.confirm('Generate a statement for the selected conference and date range?')) {
            return;
        }

        loading.prop('hidden', false);

        requestRest({
            url: svdpAdmin.restUrl + 'admin/statements/generate',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                conferenceId: conferenceId,
                periodStart: periodStart,
                periodEnd: periodEnd
            })
        }).done(function(response) {
            const link = response.statementUrl
                ? ' <a href="' + escapeAttribute(response.statementUrl) + '" target="_blank" rel="noopener noreferrer">Open Statement</a>'
                : '';

            successBox.html(
                '<p>Statement ' + escapeHtml(response.statementNumber || '') + ' generated for ' +
                String(response.invoiceCount || 0) + ' invoice' + (Number(response.invoiceCount || 0) === 1 ? '' : 's') +
                '. Total: ' + escapeHtml(formatCurrency(response.totalAmount || 0)) + '.' + link + '</p>'
            ).prop('hidden', false);

            previewEligibleInvoices();
            window.setTimeout(function() {
                window.location.reload();
            }, 1200);
        }).fail(function(xhr) {
            errorBox.html('<p>' + escapeHtml(getErrorMessage(xhr, 'Unable to generate statement.')) + '</p>').prop('hidden', false);
        }).always(function() {
            loading.prop('hidden', true);
        });
    }

    function loadDefaultStatementRange() {
        const startInput = $('#svdp-statement-period-start');
        const endInput = $('#svdp-statement-period-end');
        if (startInput.length === 0 || endInput.length === 0) {
            return;
        }

        requestRest({
            url: svdpAdmin.restUrl + 'admin/statements/default-range',
            method: 'GET'
        }).done(function(response) {
            if (response.periodStart) {
                startInput.val(response.periodStart);
            }

            if (response.periodEnd) {
                endInput.val(response.periodEnd);
            }
        });
    }

    function renderInvoiceResults(invoices) {
        const tbody = $('#svdp-invoice-results');
        if (tbody.length === 0) {
            return;
        }

        if (!invoices.length) {
            tbody.html('<tr><td colspan="7">No invoices matched the selected filters.</td></tr>');
            return;
        }

        const rows = invoices.map(function(invoice) {
            const statementLabel = invoice.statement_number ? escapeHtml(invoice.statement_number) : 'Unstatemented';
            const documentLink = invoice.stored_file_url
                ? '<a href="' + escapeAttribute(invoice.stored_file_url) + '" target="_blank" rel="noopener noreferrer">Open Invoice</a>'
                : '<span class="description">Missing file</span>';

            return '<tr>' +
                '<td>' + escapeHtml(invoice.invoice_number) + '</td>' +
                '<td>' + escapeHtml(invoice.conference_name || '') + '</td>' +
                '<td>' + escapeHtml(invoice.neighbor_name || '') + '</td>' +
                '<td>' + escapeHtml(invoice.invoice_date || '') + '</td>' +
                '<td>' + escapeHtml(formatCurrency(invoice.amount || 0)) + '</td>' +
                '<td>' + statementLabel + '</td>' +
                '<td>' + documentLink + '</td>' +
            '</tr>';
        });

        tbody.html(rows.join(''));
    }

    function renderStatementPreview(invoices) {
        const tbody = $('#svdp-statement-preview-results');
        if (tbody.length === 0) {
            return;
        }

        if (!invoices.length) {
            tbody.html('<tr><td colspan="5">No eligible invoices were found for the current selection.</td></tr>');
            return;
        }

        const rows = invoices.map(function(invoice) {
            const documentLink = invoice.stored_file_url
                ? '<a href="' + escapeAttribute(invoice.stored_file_url) + '" target="_blank" rel="noopener noreferrer">Open Invoice</a>'
                : '<span class="description">Missing file</span>';

            return '<tr>' +
                '<td>' + escapeHtml(invoice.invoice_number) + '</td>' +
                '<td>' + escapeHtml(invoice.neighbor_name || '') + '</td>' +
                '<td>' + escapeHtml(invoice.invoice_date || '') + '</td>' +
                '<td>' + escapeHtml(formatCurrency(invoice.amount || 0)) + '</td>' +
                '<td>' + documentLink + '</td>' +
            '</tr>';
        });

        tbody.html(rows.join(''));
    }

    function clearStatementMessages() {
        $('#svdp-statement-error').prop('hidden', true).empty();
        $('#svdp-statement-success').prop('hidden', true).empty();
    }

    function requestRest(options) {
        return $.ajax($.extend({}, options, {
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', svdpAdmin.restNonce);
            }
        }));
    }

    function getErrorMessage(xhr, fallback) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }

        return fallback;
    }

    function formatCurrency(value) {
        const numericValue = Number(value || 0);
        return '$' + numericValue.toFixed(2);
    }

    function escapeHtml(value) {
        return String(value === null || typeof value === 'undefined' ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value);
    }

})(jQuery);
