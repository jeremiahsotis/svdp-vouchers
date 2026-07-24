<?php
/**
 * Protected, price-free neighbor voucher print view.
 */
class SVDP_Neighbor_Voucher_Print {
    public static function handle() {
        $voucher_id = absint($_GET['voucher_id'] ?? 0);
        if (!SVDP_Permissions::user_can_access_cashier()) {
            SVDP_Accounting::audit('neighbor_print_denied', 'voucher', $voucher_id, 'Neighbor voucher print access was denied.', null, null, 'Cashier capability required.');
            wp_die('You do not have permission to print this voucher.', 403);
        }
        check_admin_referer('svdp_print_neighbor_voucher_' . $voucher_id);
        $voucher = SVDP_Voucher::get_cashier_voucher($voucher_id);
        if (!$voucher) {
            wp_die('Voucher not found.', 404);
        }
        $document = self::build_document($voucher);
        SVDP_Accounting::audit('neighbor_voucher_printed', 'voucher', $voucher_id, sprintf('Cashier printed the price-free neighbor copy for voucher #%d.', $voucher_id));
        nocache_headers();
        header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/documents/neighbor-voucher.php';
        exit;
    }

    public static function url($voucher_id) {
        return wp_nonce_url(admin_url('admin-post.php?action=svdp_print_neighbor_voucher&voucher_id=' . absint($voucher_id)), 'svdp_print_neighbor_voucher_' . absint($voucher_id));
    }

    public static function build_document($voucher) {
        $items = [];
        if (!empty($voucher['uses_shared_fulfillment'])) {
            foreach ((array) $voucher['fulfillment_lines'] as $line) {
                $items[] = [
                    'name' => (string) ($line['requested_name'] ?? $line['requested_item_name'] ?? $line['item_name'] ?? 'Approved item'),
                    'category' => (string) ($line['requested_group'] ?? $line['requested_category_name'] ?? $line['category_name'] ?? ''),
                    'quantity' => (int) ($line['requested_quantity'] ?? 0),
                ];
            }
        } elseif (($voucher['voucher_type'] ?? '') === 'furniture') {
            foreach ((array) ($voucher['items'] ?? []) as $line) {
                $items[] = [
                    'name' => (string) ($line['requested_item_name'] ?? 'Approved item'),
                    'category' => (string) ($line['requested_category'] ?? ''),
                    'quantity' => (int) ($line['requested_quantity'] ?? 1),
                ];
            }
        } else {
            $per_person = ($voucher['created_by'] ?? '') === 'Cashier' ? 3 : 7;
            $items = [
                ['name' => 'Adult clothing items', 'category' => '', 'quantity' => (int) $voucher['adults'] * $per_person],
                ['name' => 'Child clothing items', 'category' => '', 'quantity' => (int) $voucher['children'] * $per_person],
            ];
        }
        return [
            'voucher_number' => (int) $voucher['id'],
            'voucher_type' => (string) $voucher['voucher_type_label'],
            'status' => (string) $voucher['cashier_status_label'],
            'issued_date' => (string) $voucher['voucher_created_date'],
            'expiration_date' => (string) $voucher['expiration_date'],
            'redeemed_date' => (string) ($voucher['redeemed_date'] ?? ''),
            'organization' => (string) $voucher['conference_name'],
            'neighbor_name' => trim($voucher['first_name'] . ' ' . $voucher['last_name']),
            'dob' => (string) $voucher['dob'],
            'vincentian_name' => (string) ($voucher['vincentian_name'] ?? ''),
            'vincentian_email' => (string) ($voucher['vincentian_email'] ?? ''),
            'items' => array_values(array_filter($items, fn($item) => $item['quantity'] > 0)),
        ];
    }
}
