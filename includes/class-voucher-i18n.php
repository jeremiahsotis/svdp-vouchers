<?php
/**
 * Shared language helpers for neighbor-facing voucher documents.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Voucher_I18n {

    /**
     * Default document language.
     */
    const DEFAULT_LANGUAGE = 'en';

    /**
     * Return the supported document languages for neighbor-facing vouchers.
     *
     * @return array
     */
    public static function get_supported_languages() {
        return [
            'en' => [
                'label' => 'English',
                'native_label' => 'English',
            ],
            'es' => [
                'label' => 'Spanish',
                'native_label' => 'Español',
            ],
            'my' => [
                'label' => 'Burmese',
                'native_label' => 'မြန်မာ',
            ],
        ];
    }

    /**
     * Normalize one inbound language selection to a supported document code.
     *
     * @param mixed $language Raw language input.
     * @return string
     */
    public static function normalize_language($language) {
        $language = trim((string) $language);
        if ($language === '') {
            return self::DEFAULT_LANGUAGE;
        }

        if (function_exists('remove_accents')) {
            $language = remove_accents($language);
        }

        $language = strtolower(str_replace('_', '-', $language));

        $aliases = [
            'en' => 'en',
            'en-us' => 'en',
            'en-gb' => 'en',
            'english' => 'en',
            'es' => 'es',
            'es-es' => 'es',
            'es-mx' => 'es',
            'es-us' => 'es',
            'spanish' => 'es',
            'espanol' => 'es',
            'español' => 'es',
            'my' => 'my',
            'my-mm' => 'my',
            'mm' => 'my',
            'burmese' => 'my',
            'myanmar' => 'my',
            'မြန်မာ' => 'my',
        ];

        return $aliases[$language] ?? self::DEFAULT_LANGUAGE;
    }

    /**
     * Resolve the HTML lang attribute for one supported document language.
     *
     * @param mixed $language Raw language input.
     * @return string
     */
    public static function get_html_lang($language) {
        $language = self::normalize_language($language);

        return $language === 'my' ? 'my' : $language;
    }

    /**
     * Resolve the document font stack for one supported language.
     *
     * @param mixed $language Raw language input.
     * @return string
     */
    public static function get_font_family($language) {
        $language = self::normalize_language($language);

        if ($language === 'my') {
            return 'Padauk, Myanmar Text, Noto Sans Myanmar, Noto Sans, DejaVu Sans, sans-serif';
        }

        return 'DejaVu Sans, Helvetica, Arial, sans-serif';
    }

    /**
     * Whether label styling should use uppercase Latin badge treatment.
     *
     * @param mixed $language Raw language input.
     * @return bool
     */
    public static function should_uppercase_labels($language) {
        return self::normalize_language($language) !== 'my';
    }

    /**
     * Return translated copy for the shared neighbor-facing voucher template.
     *
     * @param mixed $language Raw language input.
     * @return array
     */
    public static function get_neighbor_voucher_copy($language) {
        $language = self::normalize_language($language);

        return wp_parse_args(self::get_language_copy($language), self::get_language_copy(self::DEFAULT_LANGUAGE));
    }

    /**
     * Translate one normalized voucher type for the document.
     *
     * @param string $voucher_type Voucher type slug.
     * @param mixed  $language Raw language input.
     * @return string
     */
    public static function get_voucher_type_label($voucher_type, $language) {
        $copy = self::get_neighbor_voucher_copy($language);

        return SVDP_Voucher::normalize_voucher_type($voucher_type) === 'furniture'
            ? $copy['voucher_type_furniture']
            : $copy['voucher_type_clothing'];
    }

    /**
     * Translate the delivery status pill for the document.
     *
     * @param bool  $delivery_required Whether delivery is included.
     * @param mixed $language Raw language input.
     * @return string
     */
    public static function get_delivery_label($delivery_required, $language) {
        $copy = self::get_neighbor_voucher_copy($language);

        return $delivery_required
            ? $copy['delivery_included']
            : $copy['delivery_not_included'];
    }

    /**
     * Format one requested-item count label.
     *
     * @param int   $count Item count.
     * @param mixed $language Raw language input.
     * @return string
     */
    public static function format_item_count($count, $language) {
        $count = max(0, intval($count));
        $language = self::normalize_language($language);

        switch ($language) {
            case 'es':
                return sprintf('%d %s', $count, $count === 1 ? 'artículo' : 'artículos');

            case 'my':
                return sprintf('ပစ္စည်း %d ခု', $count);

            case 'en':
            default:
                return sprintf('%d item%s', $count, $count === 1 ? '' : 's');
        }
    }

    /**
     * Format the household summary for the document.
     *
     * @param int   $adults Adult count.
     * @param int   $children Child count.
     * @param mixed $language Raw language input.
     * @return string
     */
    public static function format_household($adults, $children, $language) {
        $adults = max(0, intval($adults));
        $children = max(0, intval($children));
        $language = self::normalize_language($language);

        switch ($language) {
            case 'es':
                return sprintf(
                    '%d %s, %d %s',
                    $adults,
                    $adults === 1 ? 'adulto' : 'adultos',
                    $children,
                    $children === 1 ? 'niño' : 'niños'
                );

            case 'my':
                return sprintf('အရွယ်ရောက်သူ %d ဦး၊ ကလေး %d ဦး', $adults, $children);

            case 'en':
            default:
                return sprintf(
                    '%d adult%s, %d child%s',
                    $adults,
                    $adults === 1 ? '' : 's',
                    $children,
                    $children === 1 ? '' : 'ren'
                );
        }
    }

    /**
     * Return one translation map for a supported language.
     *
     * @param string $language Normalized language code.
     * @return array
     */
    private static function get_language_copy($language) {
        switch ($language) {
            case 'es':
                return [
                    'document_title' => 'Vale para el vecino',
                    'document_heading' => 'Vale para el vecino',
                    'document_intro' => 'Traiga este vale cuando llegue para recoger sus artículos o recibir la entrega.',
                    'label_neighbor' => 'Vecino',
                    'label_date_of_birth' => 'Fecha de nacimiento',
                    'label_conference' => 'Conferencia',
                    'label_voucher_type' => 'Tipo de vale',
                    'label_household' => 'Hogar',
                    'label_delivery' => 'Entrega',
                    'label_created' => 'Creado',
                    'label_valid_through' => 'Válido hasta',
                    'label_estimated_amount_approved' => 'Monto estimado aprobado',
                    'heading_requested_items' => 'Artículos solicitados',
                    'empty_requested_items' => 'No se registraron artículos solicitados para este vale.',
                    'delivery_included_note' => 'La entrega está incluida con este vale.',
                    'footer_note' => 'Este vale para el vecino incluye solo el monto aprobado, el estado de la entrega y los artículos solicitados.',
                    'delivery_included' => 'Incluida',
                    'delivery_not_included' => 'No incluida',
                    'voucher_type_clothing' => 'Ropa',
                    'voucher_type_furniture' => 'Muebles',
                    'clothing_voucher_items' => 'Artículos del vale de ropa',
                    'requested_furniture_items' => 'Artículos de muebles solicitados',
                ];

            case 'my':
                return [
                    'document_title' => 'အိမ်နီးချင်းအတွက် ဘောက်ချာ',
                    'document_heading' => 'အိမ်နီးချင်းအတွက် ဘောက်ချာ',
                    'document_intro' => 'ပစ္စည်းလာယူရန် သို့မဟုတ် ပို့ဆောင်မှုလက်ခံရန် လာသောအခါ ဤဘောက်ချာကို ယူဆောင်လာပါ။',
                    'label_neighbor' => 'အိမ်နီးချင်း',
                    'label_date_of_birth' => 'မွေးသက္ကရာဇ်',
                    'label_conference' => 'ကွန်ဖရင့်',
                    'label_voucher_type' => 'ဘောက်ချာအမျိုးအစား',
                    'label_household' => 'အိမ်ထောင်စု',
                    'label_delivery' => 'ပို့ဆောင်မှု',
                    'label_created' => 'ထုတ်ပေးသည့်နေ့',
                    'label_valid_through' => 'သက်တမ်းကုန်ဆုံးမည့်နေ့',
                    'label_estimated_amount_approved' => 'အတည်ပြုထားသော ခန့်မှန်းပမာဏ',
                    'heading_requested_items' => 'တောင်းဆိုထားသော ပစ္စည်းများ',
                    'empty_requested_items' => 'ဤဘောက်ချာအတွက် တောင်းဆိုထားသော ပစ္စည်းများကို မှတ်တမ်းတင်ထားခြင်း မရှိပါ။',
                    'delivery_included_note' => 'ဤဘောက်ချာတွင် ပို့ဆောင်မှု ပါဝင်ပါသည်။',
                    'footer_note' => 'ဤအိမ်နီးချင်းအတွက် ဘောက်ချာတွင် အတည်ပြုထားသော ခန့်မှန်းပမာဏ၊ ပို့ဆောင်မှုအခြေအနေနှင့် တောင်းဆိုထားသော ပစ္စည်းများကိုသာ ဖော်ပြထားပါသည်။',
                    'delivery_included' => 'ပါဝင်သည်',
                    'delivery_not_included' => 'မပါဝင်ပါ',
                    'voucher_type_clothing' => 'အဝတ်အစား',
                    'voucher_type_furniture' => 'ပရိဘောဂ',
                    'clothing_voucher_items' => 'အဝတ်အစား ဘောက်ချာပစ္စည်းများ',
                    'requested_furniture_items' => 'တောင်းဆိုထားသော ပရိဘောဂပစ္စည်းများ',
                ];

            case 'en':
            default:
                return [
                    'document_title' => 'Neighbor Voucher',
                    'document_heading' => 'Neighbor Voucher',
                    'document_intro' => 'Bring this voucher with you when you arrive for pickup or delivery.',
                    'label_neighbor' => 'Neighbor',
                    'label_date_of_birth' => 'Date of Birth',
                    'label_conference' => 'Conference',
                    'label_voucher_type' => 'Voucher Type',
                    'label_household' => 'Household',
                    'label_delivery' => 'Delivery',
                    'label_created' => 'Created',
                    'label_valid_through' => 'Valid Through',
                    'label_estimated_amount_approved' => 'Estimated Amount Approved',
                    'heading_requested_items' => 'Requested Items',
                    'empty_requested_items' => 'No requested items were recorded for this voucher.',
                    'delivery_included_note' => 'Delivery is included with this voucher.',
                    'footer_note' => 'This neighbor-facing voucher includes the approved amount, delivery status, and requested items only.',
                    'delivery_included' => 'Included',
                    'delivery_not_included' => 'Not included',
                    'voucher_type_clothing' => 'Clothing',
                    'voucher_type_furniture' => 'Furniture',
                    'clothing_voucher_items' => 'Clothing voucher items',
                    'requested_furniture_items' => 'Requested furniture items',
                ];
        }
    }
}
