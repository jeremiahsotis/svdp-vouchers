<?php
$login_url = wp_login_url(is_singular() ? get_permalink() : home_url('/'));
?>
<div class="svdp-cashier-shell" data-svdp-cashier-shell x-data x-cloak>
    <div class="svdp-cashier-session-overlay" x-show="$store.cashier && $store.cashier.sessionLost">
        <div class="svdp-cashier-session-card">
            <h2>Re-authentication Required</h2>
            <p>Your cashier session is no longer authenticated. Sign in again to resume the live shell.</p>
            <a class="svdp-btn svdp-btn-primary" href="<?php echo esc_url($login_url); ?>">Sign In Again</a>
        </div>
    </div>

    <header class="svdp-cashier-shell-header">
        <div class="svdp-cashier-shell-title">
            <h1>Cashier Station</h1>
            <p>Clothing checkout stays live in one screen, and furniture vouchers can be reviewed or resolved here without leaving the shell.</p>
        </div>

        <div class="svdp-cashier-shell-utility">
            <div class="svdp-shell-status" :class="'is-' + ($store.cashier ? $store.cashier.keepaliveState : 'idle')">
                <span x-text="$store.cashier ? $store.cashier.keepaliveLabel : 'Connecting'">Connecting</span>
            </div>
            <button type="button" class="svdp-btn svdp-btn-secondary" @click="$store.cashier.emergencyOpen = !$store.cashier.emergencyOpen">
                Emergency Voucher
            </button>
        </div>
    </header>

    <div id="svdpCashierFlash" class="svdp-message" style="display: none;"></div>

    <form id="svdpCashierFilters" class="svdp-cashier-toolbar" onsubmit="return false;">
        <input type="hidden" id="svdpSelectedVoucherId" name="selected_id" value="">

        <div class="svdp-search-box">
            <label class="screen-reader-text" for="svdpCashierSearch">Search vouchers</label>
            <input type="search" id="svdpCashierSearch" name="search" placeholder="Search by name, DOB, or conference...">
        </div>

        <div class="svdp-toolbar-group">
            <label class="screen-reader-text" for="svdpCashierFilter">Filter vouchers</label>
            <select id="svdpCashierFilter" name="filter">
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="redeemed">Redeemed</option>
                <option value="expired">Expired</option>
                <option value="coat-available">Coat Available</option>
            </select>
        </div>

        <div class="svdp-toolbar-group">
            <label class="screen-reader-text" for="svdpCashierSort">Sort vouchers</label>
            <select id="svdpCashierSort" name="sort">
                <option value="date-desc">Newest First</option>
                <option value="date-asc">Oldest First</option>
                <option value="name-asc">Name A-Z</option>
                <option value="name-desc">Name Z-A</option>
            </select>
        </div>
    </form>

    <div class="svdp-cashier-shell-body">
        <section class="svdp-cashier-list-pane">
            <div
                id="svdpCashierListRegion"
                class="svdp-cashier-list-region"
                hx-get="<?php echo esc_url(rest_url('svdp/v1/cashier/vouchers')); ?>"
                hx-trigger="load, every 30s, svdp:list-refresh from:body, keyup changed delay:250ms from:#svdpCashierSearch, change from:#svdpCashierFilter, change from:#svdpCashierSort"
                hx-include="#svdpCashierFilters"
                hx-target="this"
                hx-swap="innerHTML"
            >
                <div class="svdp-loading">
                    <div class="svdp-spinner"></div>
                    <p>Loading vouchers...</p>
                </div>
            </div>
        </section>

        <aside class="svdp-cashier-side-pane">
            <section class="svdp-cashier-side-card" x-show="$store.cashier && $store.cashier.emergencyOpen" x-transition>
                <?php include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/cashier/partials/emergency-panel.php'; ?>
            </section>

            <section id="svdpCashierDetailPanel" class="svdp-cashier-side-card svdp-cashier-detail-panel">
                <?php
                $message = 'Select a voucher from the list to review its details.';
                include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/cashier/partials/detail-empty.php';
                ?>
            </section>
        </aside>
    </div>

    <?php include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/cashier/partials/override-modal.php'; ?>
</div>
