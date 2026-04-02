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
$language_field_id = 'svdpNeighborVoucherLanguage-' . $voucher_id;
?>
<section class="svdp-cashier-info-panel" data-neighbor-document-controls>
    <div class="svdp-cashier-panel-header">
        <div>
            <h3>Neighbor Voucher</h3>
            <p>Open or print the shared neighbor-facing voucher in English, Spanish, or Burmese.</p>
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
        <small class="svdp-help-text">The selected language applies to both open and print.</small>
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
    </div>
</section>
