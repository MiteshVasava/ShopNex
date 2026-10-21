<?php
require_once '../includes/config.php';
require_role('admin');

$pdo = db();

$title = 'Admin Dashboard';

/*
 * Dashboard statistics
 */
$stats = [
    'Products' => $pdo->query(
        'SELECT COUNT(*) FROM products'
    )->fetchColumn(),

    'Customers' => $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role='customer'"
    )->fetchColumn(),

    'Staff' => $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role='staff'"
    )->fetchColumn(),

    'Orders' => $pdo->query(
        'SELECT COUNT(*) FROM orders'
    )->fetchColumn()
];

require '../includes/header.php';
?>

<h1 class="section-title">
    Admin Dashboard
</h1>


<!-- Statistics -->
<div class="row g-3 mb-4">

    <?php foreach ($stats as $k => $v): ?>

        <div class="col-6 col-lg-3">

            <div class="card dashboard-card shadow-sm p-3">

                <small class="text-muted">
                    <?= e($k) ?>
                </small>

                <div class="display-6 fw-bold text-purple">
                    <?= (int)$v ?>
                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>


<!-- Management -->
<div class="row g-4">

    <!-- Product Management -->
    <div class="col-lg-8">

        <div class="bg-white border rounded-4 p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>
                    <h4 class="mb-1">
                        Product Management
                    </h4>

                    <p class="text-muted mb-0">
                        Add, edit and manage products in your store.
                    </p>
                </div>

            </div>


            <div class="d-flex flex-wrap gap-2">

                <!-- Add Product -->
                <a
                    href="products.php?add=1"
                    class="btn btn-purple"
                >
                    <i class="bi bi-plus-lg"></i>
                    Add Product
                </a>


                <!-- Manage Products -->
                <a
                    href="products.php"
                    class="btn btn-outline-purple"
                >
                    <i class="bi bi-box-seam"></i>
                    Manage Products
                </a>


                <!-- Categories -->
                <a
                    href="categories.php"
                    class="btn btn-outline-purple"
                >
                    <i class="bi bi-grid"></i>
                    Categories
                </a>


                <!-- Inventory -->
                <a
                    href="inventory.php"
                    class="btn btn-outline-purple"
                >
                    <i class="bi bi-boxes"></i>
                    Inventory
                </a>

            </div>

        </div>

    </div>


    <!-- Quick Actions -->
    <div class="col-lg-4">

        <div class="bg-white border rounded-4 p-4">

            <h5 class="mb-3">
                Quick Actions
            </h5>


            <div class="d-grid gap-2">

                <a
                    href="orders.php"
                    class="btn btn-outline-purple text-start"
                >
                    <i class="bi bi-cart-check me-2"></i>
                    Manage Orders
                </a>


                <a
                    href="customers.php"
                    class="btn btn-outline-purple text-start"
                >
                    <i class="bi bi-people me-2"></i>
                    Customers
                </a>


                <a href="website.php" class="btn btn-outline-purple text-start"><i class="bi bi-palette me-2"></i>Manage Site</a>

                <a
                    href="staff.php"
                    class="btn btn-outline-purple text-start"
                >
                    <i class="bi bi-person-badge me-2"></i>
                    Staff & Permissions
                </a>


                <a
                    href="coupons.php"
                    class="btn btn-outline-purple text-start"
                >
                    <i class="bi bi-ticket-perforated me-2"></i>
                    Coupons
                </a>


                <a
                    href="support.php"
                    class="btn btn-outline-purple text-start"
                >
                    <i class="bi bi-headset me-2"></i>
                    Support
                </a>


                <a
                    href="reports.php"
                    class="btn btn-outline-purple text-start"
                >
                    <i class="bi bi-bar-chart me-2"></i>
                    Reports
                </a>

            </div>

        </div>

    </div>

</div>


<!-- Product Management Information -->
<div class="row mt-4">

    <div class="col-12">

        <div class="bg-white border rounded-4 p-4">

            <div class="d-flex align-items-center gap-3">

                <div
                    class="rounded-3 p-3"
                    style="background:#f0e8ff;"
                >
                    <i
                        class="bi bi-info-circle text-purple fs-4"
                    ></i>
                </div>

                <div>

                    <h5 class="mb-1">
                        Product Management
                    </h5>

                    <p class="text-muted mb-0">
                        Use <strong>Add Product</strong> to create a new
                        product. Use <strong>Manage Products</strong> to
                        edit existing products and perform product
                        management operations.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<?php
require '../includes/footer.php';
?>