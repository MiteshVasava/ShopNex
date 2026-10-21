<?php
require_once 'includes/config.php';
require_login();

$pdo = db();
$u = user();

/*
|--------------------------------------------------------------------------
| Cancel / Delete Order
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($order_id <= 0) {
        flash('danger', 'Invalid order.');
        redirect('orders.php');
    }

    /*
     * Make sure the order belongs to the logged-in customer.
     */
    $check = $pdo->prepare(
        'SELECT *
         FROM orders
         WHERE id = ?
         AND user_id = ?'
    );

    $check->execute([
        $order_id,
        $u['id']
    ]);

    $order = $check->fetch();

    if (!$order) {
        flash('danger', 'Order not found.');
        redirect('orders.php');
    }


    /*
    |--------------------------------------------------------------------------
    | CANCEL ORDER
    |--------------------------------------------------------------------------
    */

    if ($action === 'cancel') {

        /*
         * Only newly placed orders can be cancelled.
         */
        if ($order['status'] !== 'placed') {

            flash(
                'danger',
                'This order can no longer be cancelled.'
            );

            redirect('orders.php');
        }


        $pdo->beginTransaction();

        try {

            /*
             * Restore product stock.
             */
            $itemsStmt = $pdo->prepare(
                'SELECT product_id, quantity
                 FROM order_items
                 WHERE order_id = ?'
            );

            $itemsStmt->execute([
                $order_id
            ]);

            $orderItems = $itemsStmt->fetchAll();


            $restore = $pdo->prepare(
                'UPDATE products
                 SET stock = stock + ?
                 WHERE id = ?'
            );


            foreach ($orderItems as $item) {

                $restore->execute([
                    (int)$item['quantity'],
                    (int)$item['product_id']
                ]);
            }


            /*
             * Change order status.
             */
            $update = $pdo->prepare(
                'UPDATE orders
                 SET status = ?
                 WHERE id = ?
                 AND user_id = ?'
            );

            $update->execute([
                'cancelled',
                $order_id,
                $u['id']
            ]);


            /*
             * Add notification.
             */
            $notification = $pdo->prepare(
                'INSERT INTO notifications
                 (user_id, title, message)
                 VALUES (?, ?, ?)'
            );

            $notification->execute([
                $u['id'],
                'Order cancelled',
                'Your order #' .
                $order_id .
                ' has been cancelled successfully.'
            ]);


            $pdo->commit();


            flash(
                'success',
                'Order #' .
                $order_id .
                ' has been cancelled.'
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash(
                'danger',
                'Unable to cancel the order.'
            );
        }


        redirect('orders.php');
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CANCELLED ORDER
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        /*
         * Only cancelled orders can be removed from order history.
         */
        if ($order['status'] !== 'cancelled') {

            flash(
                'danger',
                'Only cancelled orders can be deleted.'
            );

            redirect('orders.php');
        }


        $pdo->beginTransaction();

        try {

            /*
             * Delete order items first.
             */
            $deleteItems = $pdo->prepare(
                'DELETE FROM order_items
                 WHERE order_id = ?'
            );

            $deleteItems->execute([
                $order_id
            ]);


            /*
             * Delete the order.
             */
            $deleteOrder = $pdo->prepare(
                'DELETE FROM orders
                 WHERE id = ?
                 AND user_id = ?'
            );

            $deleteOrder->execute([
                $order_id,
                $u['id']
            ]);


            $pdo->commit();


            flash(
                'success',
                'Order #' .
                $order_id .
                ' was removed from your order history.'
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash(
                'danger',
                'Unable to delete this order.'
            );
        }


        redirect('orders.php');
    }
}


/*
|--------------------------------------------------------------------------
| Get Orders
|--------------------------------------------------------------------------
*/

$st = $pdo->prepare(
    'SELECT *
     FROM orders
     WHERE user_id = ?
     ORDER BY id DESC'
);

$st->execute([
    $u['id']
]);

$orders = $st->fetchAll();

$title = 'My Orders';

require 'includes/header.php';
?>


<style>

/* =========================================================
   SHOPNEX ORDERS PAGE
   ========================================================= */

.orders-page {
    padding-bottom: 55px;
}


/* Header */

.orders-heading {
    font-weight: 800;
    color: #17122e;
    letter-spacing: -0.5px;
}

.orders-subtitle {
    color: #77738a;
}


/* Order card */

.order-card {
    background: #fff;
    border: 1px solid #eeeaf7;
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 18px;

    box-shadow:
        0 7px 24px rgba(55, 35, 100, .055);

    transition: .2s ease;
}

.order-card:hover {
    transform: translateY(-2px);

    box-shadow:
        0 12px 30px rgba(55, 35, 100, .09);
}


/* Order number */

.order-number {
    font-size: 18px;
    font-weight: 800;
    color: #19132f;
}

.order-date {
    color: #8b8798;
    font-size: 13px;
}


/* Status */

.order-status {
    border-radius: 10px;
    padding: 8px 13px;
    font-size: 13px;
    font-weight: 700;
    text-transform: capitalize;
}


/* Status colors */

.status-placed {
    background: #eee5ff;
    color: #702cff;
}

.status-processing {
    background: #fff3d6;
    color: #9a6800;
}

.status-shipped {
    background: #e7f3ff;
    color: #1769aa;
}

.status-delivered {
    background: #e4f7eb;
    color: #24733d;
}

.status-cancelled {
    background: #ffe7e7;
    color: #b42318;
}


/* Info boxes */

.order-info {
    background: #faf9fd;
    border: 1px solid #f0edf6;
    border-radius: 13px;
    padding: 15px;
    height: 100%;
}

.order-info-label {
    color: #8b8798;
    font-size: 12px;
    margin-bottom: 5px;
}

.order-info-value {
    color: #252037;
    font-weight: 600;
}

.order-total {
    font-size: 18px;
    font-weight: 800;
    color: #17122e;
}


/* Buttons */

.order-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.order-action {
    border-radius: 10px;
    font-weight: 600;
    padding: 8px 14px;
}


/* Cancel */

.btn-cancel-order {
    background: #fff6e6;
    color: #9a6700;
    border: 1px solid #f0d49a;
}

.btn-cancel-order:hover {
    background: #ffefd0;
    color: #805600;
}


/* Delete */

.btn-delete-order {
    background: #fff0f0;
    color: #b42318;
    border: 1px solid #f0caca;
}

.btn-delete-order:hover {
    background: #ffe0e0;
    color: #941c13;
}


/* Cancelled */

.cancelled-note {
    margin-top: 15px;
    padding: 11px 14px;
    border-radius: 11px;
    background: #fff5f5;
    border: 1px solid #f4dddd;
    color: #a33b34;
    font-size: 13px;
}


/* Empty */

.empty-orders {
    background: #fff;
    border: 1px solid #eeeaf7;
    border-radius: 20px;
    padding: 60px 25px;
    text-align: center;
    box-shadow: 0 7px 24px rgba(55, 35, 100, .05);
}

.empty-icon {
    width: 65px;
    height: 65px;
    border-radius: 18px;
    margin: 0 auto 18px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f0e8ff;
    color: #702cff;

    font-size: 28px;
}


/* Mobile */

@media (max-width: 767px) {

    .order-card {
        padding: 18px;
        border-radius: 16px;
    }

    .order-status {
        align-self: flex-start;
    }

    .order-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .order-actions .btn {
        width: 100%;
    }
}

</style>


<div class="orders-page">


    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="mb-4">

        <span class="eyebrow">
            ACCOUNT
        </span>

        <h1 class="orders-heading mb-1">
            My Orders
        </h1>

        <p class="orders-subtitle mb-0">
            Track your purchases and delivery progress.
        </p>

    </div>


    <?php if ($orders): ?>


        <!-- =================================================
             ORDER LIST
             ================================================= -->

        <?php foreach ($orders as $o): ?>

            <?php

            $status = strtolower(
                trim($o['status'])
            );

            $statusClass =
                'status-' .
                preg_replace(
                    '/[^a-z0-9_-]/',
                    '',
                    $status
                );

            ?>


            <div class="order-card">


                <!-- TOP -->
                <div
                    class="d-flex justify-content-between
                           align-items-start flex-wrap gap-3"
                >

                    <div>

                        <div class="order-number">

                            Order #<?= (int)$o['id'] ?>

                        </div>

                        <div class="order-date">

                            <i class="bi bi-calendar3 me-1"></i>

                            <?= e($o['created_at']) ?>

                        </div>

                    </div>


                    <span
                        class="order-status <?= e($statusClass) ?>"
                    >

                        <?= e($o['status']) ?>

                    </span>

                </div>


                <!-- ORDER INFORMATION -->

                <div class="row g-3 mt-2">


                    <!-- TOTAL -->

                    <div class="col-md-4">

                        <div class="order-info">

                            <div class="order-info-label">
                                TOTAL
                            </div>

                            <div class="order-total">
                                <?= money($o['total']) ?>
                            </div>

                        </div>

                    </div>


                    <!-- PAYMENT -->

                    <div class="col-md-4">

                        <div class="order-info">

                            <div class="order-info-label">
                                PAYMENT
                            </div>

                            <div class="order-info-value">

                                <?= e(
                                    $o['payment_method']
                                ) ?>

                                <span class="text-muted">
                                    ·
                                </span>

                                <?= e(
                                    $o['payment_status']
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- DELIVERY -->

                    <div class="col-md-4">

                        <div class="order-info">

                            <div class="order-info-label">
                                EXPECTED DELIVERY
                            </div>

                            <div class="order-info-value">

                                <?=
                                $o['delivery_date']
                                    ? e($o['delivery_date'])
                                    : 'To be updated'
                                ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ACTIONS -->

                <div class="order-actions">


                    <!-- VIEW -->

                    <a
                        class="btn btn-sm btn-outline-purple order-action"
                        href="order-details.php?id=<?= (int)$o['id'] ?>"
                    >

                        <i class="bi bi-eye me-1"></i>

                        View Details

                    </a>


                    <!-- INVOICE -->

                    <a
                        class="btn btn-sm btn-purple order-action"
                        href="invoice.php?id=<?= (int)$o['id'] ?>"
                    >

                        <i class="bi bi-receipt me-1"></i>

                        Invoice

                    </a>


                    <?php if ($status === 'placed'): ?>

                        <!-- CANCEL -->

                        <form
                            method="post"
                            class="d-inline"
                            onsubmit="return confirm(
                                'Are you sure you want to cancel Order #<?= (int)$o['id'] ?>?'
                            );"
                        >

                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= e(csrf_token()) ?>"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$o['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="cancel"
                            >

                            <button
                                type="submit"
                                class="btn btn-sm btn-cancel-order order-action"
                            >

                                <i class="bi bi-x-circle me-1"></i>

                                Cancel Order

                            </button>

                        </form>

                    <?php endif; ?>


                    <?php if ($status === 'cancelled'): ?>

                        <!-- DELETE -->

                        <form
                            method="post"
                            class="d-inline"
                            onsubmit="return confirm(
                                'Delete Order #<?= (int)$o['id'] ?> from your order history? This cannot be undone.'
                            );"
                        >

                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= e(csrf_token()) ?>"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$o['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="delete"
                            >

                            <button
                                type="submit"
                                class="btn btn-sm btn-delete-order order-action"
                            >

                                <i class="bi bi-trash3 me-1"></i>

                                Delete

                            </button>

                        </form>

                    <?php endif; ?>


                </div>


                <?php if ($status === 'cancelled'): ?>

                    <div class="cancelled-note">

                        <i class="bi bi-info-circle me-1"></i>

                        This order has been cancelled.
                        You can remove it from your order history.

                    </div>

                <?php endif; ?>


            </div>

        <?php endforeach; ?>


    <?php else: ?>


        <!-- =================================================
             NO ORDERS
             ================================================= -->

        <div class="empty-orders">

            <div class="empty-icon">

                <i class="bi bi-bag-x"></i>

            </div>

            <h4>
                No orders yet
            </h4>

            <p class="text-secondary mb-4">
                Start shopping to see your orders here.
            </p>

            <a
                class="btn btn-purple"
                href="products.php"
            >

                <i class="bi bi-shop me-1"></i>

                Browse Products

            </a>

        </div>


    <?php endif; ?>


</div>


<?php
require 'includes/footer.php';
?>