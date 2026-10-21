<?php

require_once '../includes/config.php';

require_staff_or_admin();

$pdo = db();

$title = 'Staff Dashboard';


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$stats = [];
if (user_can('products')) {
    $stats['Products'] = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
}
if (user_can('orders')) {
    $stats['Pending Orders'] = $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE status IN ('placed','confirmed','processing')"
    )->fetchColumn();
}
if (user_can('customers') || user_can('support')) {
    $stats['Customers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
}
if (user_can('inventory')) {
    $stats['Low Stock'] = $pdo->query('SELECT COUNT(*) FROM products WHERE stock < 5')->fetchColumn();
}


require '../includes/header.php';

?>


<style>

/* =========================================================
   SHOPNEX STAFF DASHBOARD
   ========================================================= */

.staff-dashboard {
    padding-bottom: 55px;
}


/* Header */

.staff-title {
    font-weight: 800;
    color: #17122e;
    letter-spacing: -0.5px;
}

.staff-subtitle {
    color: #77738a;
}


/* Statistic Cards */

.staff-stat-card {
    background: #ffffff;
    border: 1px solid #eeeaf7;
    border-radius: 18px;
    padding: 22px;

    box-shadow:
        0 7px 22px rgba(55, 35, 100, .055);

    height: 100%;

    transition: .2s ease;
}

.staff-stat-card:hover {
    transform: translateY(-2px);

    box-shadow:
        0 11px 28px rgba(55, 35, 100, .09);
}

.staff-stat-label {
    color: #77738a;
    font-size: 14px;
}

.staff-stat-number {
    color: #702cff;
    font-size: 38px;
    font-weight: 800;
    line-height: 1.1;
    margin-top: 5px;
}


/* Main Management Card */

.staff-management {
    background: #ffffff;
    border: 1px solid #eeeaf7;
    border-radius: 20px;
    padding: 25px;

    box-shadow:
        0 7px 24px rgba(55, 35, 100, .055);
}

.staff-section-title {
    color: #17122e;
    font-weight: 750;
}

.staff-section-subtitle {
    color: #858092;
    font-size: 14px;
}


/* Action Cards */

.staff-action {
    display: block;

    background: #ffffff;

    border: 1px solid #e7e1f2;
    border-radius: 15px;

    padding: 18px;

    text-decoration: none;

    transition: .2s ease;

    height: 100%;
}

.staff-action:hover {
    text-decoration: none;

    border-color: #b99aff;

    background: #faf8ff;

    transform: translateY(-2px);

    box-shadow:
        0 8px 20px rgba(112,44,255,.08);
}


.staff-action-icon {
    width: 44px;
    height: 44px;

    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f0e8ff;

    color: #702cff;

    font-size: 20px;

    margin-bottom: 13px;
}


.staff-action-title {
    color: #211a36;
    font-weight: 700;
    margin-bottom: 4px;
}


.staff-action-text {
    color: #858092;
    font-size: 13px;
    margin: 0;
}


/* Add Product */

.staff-add-product {
    border-color: #d4c0ff;

    background:
        linear-gradient(
            135deg,
            #f8f3ff,
            #ffffff
        );
}

.staff-add-product .staff-action-icon {
    background: #702cff;
    color: #ffffff;
}


/* Quick Operations */

.quick-card {
    background: #ffffff;

    border: 1px solid #eeeaf7;
    border-radius: 20px;

    padding: 25px;

    box-shadow:
        0 7px 24px rgba(55, 35, 100, .055);
}


/* Buttons */

.staff-button {
    border-radius: 10px;
    font-weight: 600;
    padding: 9px 15px;
}


/* Responsive */

@media (max-width: 767px) {

    .staff-management,
    .quick-card {
        padding: 18px;
        border-radius: 16px;
    }

    .staff-stat-card {
        padding: 18px;
    }

    .staff-stat-number {
        font-size: 32px;
    }

}

</style>


<div class="staff-dashboard">


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <div class="mb-4">

        <div class="text-uppercase text-muted small fw-semibold mb-1">
            STORE OPERATIONS
        </div>

        <h1 class="staff-title mb-1">
            Staff Dashboard
        </h1>

        <p class="staff-subtitle mb-0">
            Manage products, inventory, orders and customer support.
        </p>

    </div>


    <!-- =====================================================
         STATISTICS
         ===================================================== -->

    <?php if ($stats): ?>
    <div class="row g-3 mb-4">

        <?php foreach ($stats as $label => $value): ?>

            <div class="col-6 col-lg-3">

                <div class="staff-stat-card">

                    <div class="staff-stat-label">
                        <?= e($label) ?>
                    </div>

                    <div class="staff-stat-number">
                        <?= (int)$value ?>
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>
    <?php endif; ?>


    <!-- Only show actions granted to this staff account. The target pages
         still enforce permissions server-side. -->
    <?php
    $canProducts = user_can('products');
    $canInventory = user_can('inventory');
    $canOrders = user_can('orders');
    $canSupport = user_can('support');
    $canManageSite = user_can('manage_site');
    $canManageStaff = user_can('manage_staff');
    $hasOperationalAccess = $canProducts || $canInventory || $canOrders || $canSupport || $canManageSite || $canManageStaff;
    ?>

    <?php if ($canProducts || $canInventory || $canOrders): ?>
    <div class="staff-management mb-4">
        <div class="mb-4">
            <h4 class="staff-section-title mb-1">Your permitted operations</h4>
            <p class="staff-section-subtitle mb-0">Only sections assigned to your account by an administrator are shown here.</p>
        </div>
        <div class="row g-3">
            <?php if ($canProducts): ?>
            <div class="col-md-6 col-lg-3">
                <a href="products.php" class="staff-action staff-add-product">
                    <div class="staff-action-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="staff-action-title">Manage Products</div>
                    <p class="staff-action-text">View and manage the store catalog.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="products.php?add=1" class="staff-action">
                    <div class="staff-action-icon"><i class="bi bi-plus-lg"></i></div>
                    <div class="staff-action-title">Add Product</div>
                    <p class="staff-action-text">Add a product to the store catalog.</p>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($canInventory): ?>
            <div class="col-md-6 col-lg-3">
                <a href="inventory.php" class="staff-action">
                    <div class="staff-action-icon"><i class="bi bi-boxes"></i></div>
                    <div class="staff-action-title">Inventory</div>
                    <p class="staff-action-text">Check stock levels and update inventory.</p>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($canOrders): ?>
            <div class="col-md-6 col-lg-3">
                <a href="orders.php" class="staff-action">
                    <div class="staff-action-icon"><i class="bi bi-cart-check"></i></div>
                    <div class="staff-action-title">Orders</div>
                    <p class="staff-action-text">Process and update customer orders.</p>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canProducts || $canInventory || $canOrders || $canSupport || $canManageSite || $canManageStaff): ?>
    <div class="quick-card">
        <div class="mb-3">
            <h5 class="staff-section-title mb-1">Quick Operations</h5>
            <p class="staff-section-subtitle mb-0">Quick access to your permitted tasks.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($canProducts): ?>
                <a href="products.php" class="btn btn-purple staff-button"><i class="bi bi-box-seam me-1"></i>Products</a>
                <a href="products.php?add=1" class="btn btn-outline-purple staff-button"><i class="bi bi-plus-circle me-1"></i>Add Product</a>
            <?php endif; ?>
            <?php if ($canInventory): ?>
                <a href="inventory.php" class="btn btn-outline-purple staff-button"><i class="bi bi-boxes me-1"></i>Inventory</a>
            <?php endif; ?>
            <?php if ($canOrders): ?>
                <a href="orders.php" class="btn btn-outline-purple staff-button"><i class="bi bi-cart-check me-1"></i>Orders</a>
            <?php endif; ?>
            <?php if ($canSupport): ?>
                <a href="support.php" class="btn btn-outline-purple staff-button"><i class="bi bi-headset me-1"></i>Support</a>
            <?php endif; ?>
            <?php if ($canManageSite): ?>
                <a href="../admin/website.php" class="btn btn-outline-purple staff-button"><i class="bi bi-palette me-1"></i>Manage Site</a>
            <?php endif; ?>
            <?php if ($canManageStaff): ?>
                <a href="team.php" class="btn btn-outline-purple staff-button"><i class="bi bi-people me-1"></i>Staff Accounts</a>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif (!$hasOperationalAccess): ?>
    <div class="staff-management">
        <div class="d-flex align-items-start gap-3">
            <div class="staff-action-icon mb-0"><i class="bi bi-shield-lock"></i></div>
            <div>
                <h4 class="staff-section-title mb-1">No operations assigned</h4>
                <p class="staff-section-subtitle mb-0">Your account currently has no management permissions. Contact an administrator to request access.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>


<?php

require '../includes/footer.php';

?>