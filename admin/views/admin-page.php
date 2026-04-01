<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=svdp-vouchers&tab=analytics" class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
            Analytics
        </a>
        <a href="?page=svdp-vouchers&tab=conferences" class="nav-tab <?php echo $active_tab === 'conferences' ? 'nav-tab-active' : ''; ?>">
            Conferences
        </a>
        <a href="?page=svdp-vouchers&tab=furniture-catalog" class="nav-tab <?php echo $active_tab === 'furniture-catalog' ? 'nav-tab-active' : ''; ?>">
            Furniture Catalog
        </a>
        <a href="?page=svdp-vouchers&tab=furniture-settings" class="nav-tab <?php echo $active_tab === 'furniture-settings' ? 'nav-tab-active' : ''; ?>">
            Furniture Reasons
        </a>
        <a href="?page=svdp-vouchers&tab=invoices" class="nav-tab <?php echo $active_tab === 'invoices' ? 'nav-tab-active' : ''; ?>">
            Invoices
        </a>
        <a href="?page=svdp-vouchers&tab=statements" class="nav-tab <?php echo $active_tab === 'statements' ? 'nav-tab-active' : ''; ?>">
            Statements
        </a>
        <a href="?page=svdp-vouchers&tab=managers" class="nav-tab <?php echo $active_tab === 'managers' ? 'nav-tab-active' : ''; ?>">
            Managers
        </a>
        <a href="?page=svdp-vouchers&tab=override-reasons" class="nav-tab <?php echo $active_tab === 'override-reasons' ? 'nav-tab-active' : ''; ?>">
            Override Reasons
        </a>
        <a href="?page=svdp-vouchers&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            Settings
        </a>
    </h2>

    <div class="svdp-admin-content">
        <?php
        switch ($active_tab) {
            case 'analytics':
                include 'tab-analytics.php';
                break;
            case 'conferences':
                include 'tab-conferences.php';
                break;
            case 'furniture-catalog':
                include 'tab-furniture-catalog.php';
                break;
            case 'furniture-settings':
                include 'tab-furniture-settings.php';
                break;
            case 'invoices':
                include 'tab-invoices.php';
                break;
            case 'statements':
                include 'tab-statements.php';
                break;
            case 'managers':
                include 'managers-tab.php';
                break;
            case 'override-reasons':
                include 'override-reasons-tab.php';
                break;
            case 'settings':
                include 'tab-settings.php';
                break;
        }
        ?>
    </div>
</div>
