<?php
$voucher_id = intval($voucher['id'] ?? 0);
if ($voucher_id <= 0) {
    return;
}

$document_languages = SVDP_Voucher_I18n::get_supported_languages();
$default_document_language = SVDP_Voucher_I18n::normalize_language(SVDP_Voucher_I18n::DEFAULT_LANGUAGE);
$document_url = rest_url('svdp/v1/cashier/vouchers/' . $voucher_id);
$open_document_url = add_query_arg([
    'view' => 'neighbor-document',
    'language' => $default_document_language,
], $document_url);
$print_document_url = add_query_arg([
    'view' => 'neighbor-document',
    'language' => $default_document_language,
    'auto_print' => '1',
], $document_url);
$email_document_url = rest_url('svdp/v1/cashier/vouchers/' . $voucher_id . '/email');
$recipient_email = sanitize_email((string) ($voucher['vincentian_email'] ?? ''));
$can_email_document = $recipient_email !== '';
$language_field_id = 'svdpNeighborVoucherLanguage-' . $voucher_id;
?>
<section class="svdp-cashier-info-panel" data-neighbor-document-controls>
    <div class="svdp-cashier-panel-header">
        <div>
            <h3>Neighbor Voucher</h3>
            <p>Open, print, or email the shared neighbor-facing voucher in English, Spanish, or Burmese.</p>
        </div>
    </div>

    <div class="svdp-form-group">
        <label for="<?php echo esc_attr($language_field_id); ?>">Language</label>
        <select id="<?php echo esc_attr($language_field_id); ?>" data-neighbor-voucher-language>
            <?php foreach ($document_languages as $language_code => $language_config): ?>
                <?php
                $option_label = trim((string) ($language_config['native_label'] ?? $language_code));
                $english_label = trim((string) ($language_config['label'] ?? ''));

                if ($english_label !== '' && $english_label !== $option_label) {
                    $option_label .= ' (' . $english_label . ')';
                }
                ?>
                <option value="<?php echo esc_attr($language_code); ?>" <?php selected($language_code, $default_document_language); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="svdp-help-text">The selected language applies to open, print, and email PDF delivery.</small>
    </div>

    <div class="svdp-document-links">
        <a
            class="svdp-document-link"
            href="<?php echo esc_url($open_document_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
            data-neighbor-document-action="open"
            data-document-url="<?php echo esc_url($document_url); ?>"
        >
            Open Neighbor Voucher
        </a>
        <a
            class="svdp-document-link"
            href="<?php echo esc_url($print_document_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
            data-neighbor-document-action="print"
            data-document-url="<?php echo esc_url($document_url); ?>"
        >
            Print Neighbor Voucher
        </a>
        <button
            type="button"
            class="svdp-document-link"
            data-neighbor-document-action="email"
            data-email-url="<?php echo esc_url($email_document_url); ?>"
            data-email-recipient="<?php echo esc_attr($recipient_email); ?>"
            data-idle-label="Email Neighbor Voucher"
            <?php disabled(!$can_email_document); ?>
        >
            Email Neighbor Voucher
        </button>
    </div>

    <?php if ($can_email_document): ?>
        <small class="svdp-help-text">Email sends the selected-language PDF to <?php echo esc_html($recipient_email); ?>.</small>
    <?php else: ?>
        <small class="svdp-help-text">No requestor email is stored for this voucher yet.</small>
    <?php endif; ?>
</section>
