<?php
/**
 * Voucher correction audit admin tab.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!SVDP_Permissions::user_can_view_audit_log()) {
    wp_die(esc_html__('You do not have permission to view voucher correction audit logs.', 'svdp-vouchers'));
}

$raw_args = [
    'voucher_id' => isset($_GET['voucher_id']) ? wp_unslash($_GET['voucher_id']) : '',
    'neighbor' => isset($_GET['neighbor']) ? wp_unslash($_GET['neighbor']) : '',
    'field_name' => isset($_GET['field_name']) ? wp_unslash($_GET['field_name']) : '',
    'manager' => isset($_GET['manager']) ? wp_unslash($_GET['manager']) : '',
    'actor' => isset($_GET['actor']) ? wp_unslash($_GET['actor']) : '',
    'reason' => isset($_GET['reason']) ? wp_unslash($_GET['reason']) : '',
    'date_from' => isset($_GET['date_from']) ? wp_unslash($_GET['date_from']) : '',
    'date_to' => isset($_GET['date_to']) ? wp_unslash($_GET['date_to']) : '',
    'page' => isset($_GET['audit_page']) ? wp_unslash($_GET['audit_page']) : 1,
    'per_page' => isset($_GET['per_page']) ? wp_unslash($_GET['per_page']) : 25,
];

$args = SVDP_Voucher_Correction_Audit::sanitize_args($raw_args);
$total_rows = SVDP_Voucher_Correction_Audit::count_rows($args);
$total_pages = max(1, (int) ceil($total_rows / $args['per_page']));
$current_page = min($args['page'], $total_pages);
$args['page'] = $current_page;
$rows = SVDP_Voucher_Correction_Audit::get_rows($args);
$allowed_fields = SVDP_Voucher_Correction_Audit::get_allowed_fields();
$base_url = admin_url('admin.php');
$query_args = [
    'page' => 'svdp-vouchers',
    'tab' => 'voucher-correction-audit',
    'voucher_id' => $args['voucher_id'] ? $args['voucher_id'] : null,
    'neighbor' => $args['neighbor'] !== '' ? $args['neighbor'] : null,
    'field_name' => $args['field_name'] !== '' ? $args['field_name'] : null,
    'manager' => $args['manager'] !== '' ? $args['manager'] : null,
    'actor' => $args['actor'] !== '' ? $args['actor'] : null,
    'reason' => $args['reason'] !== '' ? $args['reason'] : null,
    'date_from' => $args['date_from'] !== '' ? $args['date_from'] : null,
    'date_to' => $args['date_to'] !== '' ? $args['date_to'] : null,
    'per_page' => $args['per_page'],
];
$pagination_base_args = array_filter($query_args, static function($value) {
    return $value !== null && $value !== '';
});
?>

<div class="svdp-card">
    <h2><?php esc_html_e('Voucher Correction Audit', 'svdp-vouchers'); ?></h2>

    <form method="get" class="svdp-audit-filters">
        <input type="hidden" name="page" value="svdp-vouchers">
        <input type="hidden" name="tab" value="voucher-correction-audit">

        <div class="svdp-admin-grid">
            <label class="svdp-admin-field">
                <span><?php esc_html_e('Voucher ID', 'svdp-vouchers'); ?></span>
                <input type="number" min="1" name="voucher_id" value="<?php echo esc_attr($args['voucher_id'] ?: ''); ?>">
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Neighbor', 'svdp-vouchers'); ?></span>
                <input type="search" name="neighbor" value="<?php echo esc_attr($args['neighbor']); ?>">
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Field', 'svdp-vouchers'); ?></span>
                <select name="field_name">
                    <option value=""><?php esc_html_e('Any field', 'svdp-vouchers'); ?></option>
                    <?php foreach ($allowed_fields as $field) : ?>
                        <option value="<?php echo esc_attr($field); ?>" <?php selected($args['field_name'], $field); ?>>
                            <?php echo esc_html(str_replace('_', ' ', $field)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Manager', 'svdp-vouchers'); ?></span>
                <input type="search" name="manager" value="<?php echo esc_attr($args['manager']); ?>">
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Actor', 'svdp-vouchers'); ?></span>
                <input type="search" name="actor" value="<?php echo esc_attr($args['actor']); ?>">
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Reason', 'svdp-vouchers'); ?></span>
                <input type="search" name="reason" value="<?php echo esc_attr($args['reason']); ?>">
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Date From', 'svdp-vouchers'); ?></span>
                <input type="date" name="date_from" value="<?php echo esc_attr($args['date_from']); ?>">
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Date To', 'svdp-vouchers'); ?></span>
                <input type="date" name="date_to" value="<?php echo esc_attr($args['date_to']); ?>">
            </label>

            <label class="svdp-admin-field">
                <span><?php esc_html_e('Per Page', 'svdp-vouchers'); ?></span>
                <input type="number" min="1" max="100" name="per_page" value="<?php echo esc_attr($args['per_page']); ?>">
            </label>
        </div>

        <p class="svdp-inline-actions">
            <button type="submit" class="button button-primary"><?php esc_html_e('Filter', 'svdp-vouchers'); ?></button>
            <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'svdp-vouchers', 'tab' => 'voucher-correction-audit'], $base_url)); ?>">
                <?php esc_html_e('Clear Filters', 'svdp-vouchers'); ?>
            </a>
        </p>
    </form>
</div>

<div class="svdp-card">
    <div class="svdp-inline-actions svdp-space-between">
        <p class="svdp-results-summary">
            <?php
            printf(
                esc_html(_n('%s audit row', '%s audit rows', $total_rows, 'svdp-vouchers')),
                esc_html(number_format_i18n($total_rows))
            );
            ?>
        </p>

        <?php if ($total_pages > 1) : ?>
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php if ($current_page > 1) : ?>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($pagination_base_args, ['audit_page' => $current_page - 1]), $base_url)); ?>">
                            <?php esc_html_e('Previous', 'svdp-vouchers'); ?>
                        </a>
                    <?php endif; ?>
                    <span class="paging-input">
                        <?php
                        printf(
                            esc_html__('Page %1$s of %2$s', 'svdp-vouchers'),
                            esc_html(number_format_i18n($current_page)),
                            esc_html(number_format_i18n($total_pages))
                        );
                        ?>
                    </span>
                    <?php if ($current_page < $total_pages) : ?>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($pagination_base_args, ['audit_page' => $current_page + 1]), $base_url)); ?>">
                            <?php esc_html_e('Next', 'svdp-vouchers'); ?>
                        </a>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Summary', 'svdp-vouchers'); ?></th>
                <th><?php esc_html_e('Metadata', 'svdp-vouchers'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)) : ?>
                <tr>
                    <td colspan="2"><?php esc_html_e('No voucher correction audit rows found.', 'svdp-vouchers'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($row['human_summary']); ?></strong>
                        </td>
                        <td>
                            <?php
                            $actor = $row['actor_name'] !== '' ? $row['actor_name'] : ($row['actor_user_id'] ? '#' . $row['actor_user_id'] : __('Unknown', 'svdp-vouchers'));
                            $manager = $row['manager_name_snapshot'] !== '' ? $row['manager_name_snapshot'] : __('None recorded', 'svdp-vouchers');
                            $reason = $row['reason_text_snapshot'] !== '' ? $row['reason_text_snapshot'] : __('None recorded', 'svdp-vouchers');
                            $neighbor = $row['neighbor_name'] !== '' ? $row['neighbor_name'] : __('Unknown neighbor', 'svdp-vouchers');
                            $conference = $row['conference_name'] !== '' ? $row['conference_name'] : __('Unknown conference', 'svdp-vouchers');
                            ?>
                            <div><?php echo esc_html(sprintf(__('Voucher #%1$d: %2$s', 'svdp-vouchers'), $row['voucher_id'], $neighbor)); ?></div>
                            <div><?php echo esc_html(sprintf(__('Field: %s', 'svdp-vouchers'), $row['field_name'])); ?></div>
                            <div><?php echo esc_html(sprintf(__('Manager: %s', 'svdp-vouchers'), $manager)); ?></div>
                            <div><?php echo esc_html(sprintf(__('Actor: %s', 'svdp-vouchers'), $actor)); ?></div>
                            <div><?php echo esc_html(sprintf(__('Reason: %s', 'svdp-vouchers'), $reason)); ?></div>
                            <div><?php echo esc_html(sprintf(__('Conference: %s', 'svdp-vouchers'), $conference)); ?></div>
                            <div><?php echo esc_html(sprintf(__('Timestamp: %s', 'svdp-vouchers'), $row['created_at'])); ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
