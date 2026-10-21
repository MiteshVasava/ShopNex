<?php
require_once 'includes/config.php';
require_login();

$pdo = db();

if (empty($_SESSION['cart'])) {
    redirect('cart.php');
}

$u = user();
$ids = array_keys($_SESSION['cart']);

if (empty($ids)) {
    redirect('cart.php');
}

$ph = implode(',', array_fill(0, count($ids), '?'));

$st = $pdo->prepare("SELECT * FROM products WHERE id IN ($ph)");
$st->execute($ids);
$items = $st->fetchAll();

if (!$items) {
    $_SESSION['cart'] = [];
    unset($_SESSION['coupon']);
    flash('danger', 'Your cart is empty.');
    redirect('cart.php');
}

/*
 * Calculate cart subtotal
 */
$subtotal = 0;

foreach ($items as $p) {
    $qty = (int)($_SESSION['cart'][$p['id']] ?? 0);

    if ($qty > 0) {
        $subtotal += (float)$p['price'] * $qty;
    }
}

$discount = 0;
$coupon_code = $_SESSION['coupon']['code'] ?? '';

/*
 * Handle POST requests
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    /*
     * Read POST values directly.
     * This replaces the broken post_str() function.
     */
    $coupon_code = trim((string)($_POST['coupon'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $payment_method = trim(
        (string)($_POST['payment_method'] ?? 'Cash on Delivery')
    );

    /*
     * Allow only the payment methods displayed in the form.
     */
    $allowed_payment_methods = [
        'Cash on Delivery',
        'Demo Card',
        'Demo UPI'
    ];

    if (!in_array($payment_method, $allowed_payment_methods, true)) {
        $payment_method = 'Cash on Delivery';
    }

    /*
     * Apply coupon
     */
    if ($coupon_code !== '') {

        $st = $pdo->prepare(
            'SELECT * FROM coupons
             WHERE code = ?
             AND status = 1
             AND (expires_at IS NULL OR expires_at >= CURDATE())'
        );

        $st->execute([$coupon_code]);

        $c = $st->fetch();

        if (!$c) {

            unset($_SESSION['coupon']);
            $discount = 0;

            flash(
                'danger',
                'Invalid or expired coupon.'
            );

        } elseif ($subtotal < (float)$c['min_order']) {

            unset($_SESSION['coupon']);
            $discount = 0;

            flash(
                'danger',
                'Minimum order for this coupon is ' .
                money($c['min_order']) .
                '.'
            );

        } else {

            if ($c['type'] === 'percent') {

                $maxDiscount = $c['max_discount'] !== null
                    ? (float)$c['max_discount']
                    : PHP_INT_MAX;

                $discount = min(
                    $subtotal * ((float)$c['amount'] / 100),
                    $maxDiscount
                );

            } else {

                $discount = min(
                    (float)$c['amount'],
                    $subtotal
                );
            }

            $_SESSION['coupon'] = [
                'id' => $c['id'],
                'code' => $c['code'],
                'discount' => $discount
            ];

            flash(
                'success',
                'Coupon applied.'
            );
        }

    } elseif (isset($_POST['coupon'])) {

        /*
         * Empty coupon field removes an existing coupon.
         */
        unset($_SESSION['coupon']);

        $discount = 0;
    }

    /*
     * Place order
     */
    if (isset($_POST['place'])) {

        $discount = (float)(
            $_SESSION['coupon']['discount'] ?? 0
        );

        $shipping = $subtotal >= 999 ? 0 : 79;

        $total = max(
            0,
            $subtotal + $shipping - $discount
        );

        /*
         * Address validation
         */
        if ($address === '') {

            flash(
                'danger',
                'Delivery address is required.'
            );

            redirect('checkout.php');
        }

        /*
         * Start database transaction
         */
        $pdo->beginTransaction();

        try {

            /*
             * Check stock before creating order
             */
            foreach ($items as $p) {

                $qty = (int)(
                    $_SESSION['cart'][$p['id']] ?? 0
                );

                if ($qty <= 0) {
                    continue;
                }

                $stockCheck = $pdo->prepare(
                    'SELECT stock
                     FROM products
                     WHERE id = ?
                     FOR UPDATE'
                );

                $stockCheck->execute([
                    $p['id']
                ]);

                $currentStock = $stockCheck->fetchColumn();

                if (
                    $currentStock === false ||
                    (int)$currentStock < $qty
                ) {

                    throw new Exception(
                        'Insufficient stock for "' .
                        $p['name'] .
                        '". Please review your cart.'
                    );
                }
            }

            /*
             * Create order
             */
            $order = $pdo->prepare(
                'INSERT INTO orders
                (
                    user_id,
                    subtotal,
                    shipping,
                    discount,
                    total,
                    address_text,
                    payment_method,
                    payment_status,
                    status,
                    delivery_date
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                )'
            );

            $order->execute([
                $u['id'],
                $subtotal,
                $shipping,
                $discount,
                $total,
                $address,
                $payment_method,
                'pending',
                'placed'
            ]);

            $oid = $pdo->lastInsertId();

            /*
             * Insert order items
             */
            $oi = $pdo->prepare(
                'INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    quantity,
                    unit_price
                )
                VALUES (?, ?, ?, ?)'
            );

            /*
             * Reduce product stock
             */
            $up = $pdo->prepare(
                'UPDATE products
                 SET stock = stock - ?
                 WHERE id = ?
                 AND stock >= ?'
            );

            foreach ($items as $p) {

                $q = (int)(
                    $_SESSION['cart'][$p['id']] ?? 0
                );

                if ($q <= 0) {
                    continue;
                }

                /*
                 * Save order item
                 */
                $oi->execute([
                    $oid,
                    $p['id'],
                    $q,
                    $p['price']
                ]);

                /*
                 * Reduce stock
                 */
                $up->execute([
                    $q,
                    $p['id'],
                    $q
                ]);

                if ($up->rowCount() !== 1) {

                    throw new Exception(
                        'Stock changed. Please review your cart.'
                    );
                }
            }

            /*
             * Create customer notification
             */
            $n = $pdo->prepare(
                'INSERT INTO notifications
                (
                    user_id,
                    title,
                    message
                )
                VALUES (?, ?, ?)'
            );

            $n->execute([
                $u['id'],
                'Order placed',
                'Your order #' .
                $oid .
                ' has been placed successfully.'
            ]);

            /*
             * Complete transaction
             */
            $pdo->commit();

            /*
             * Clear cart and coupon only after successful order
             */
            $_SESSION['cart'] = [];

            unset($_SESSION['coupon']);

            flash(
                'success',
                'Order #' .
                $oid .
                ' placed successfully.'
            );

            redirect('orders.php');

        } catch (Throwable $e) {

            /*
             * Roll back if anything fails
             */
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash(
                'danger',
                $e->getMessage()
            );

            redirect('checkout.php');
        }
    }
}

/*
 * Calculate final checkout totals
 */
$coupon = $_SESSION['coupon'] ?? null;

$discount = (float)(
    $coupon['discount'] ?? 0
);

$shipping = $subtotal >= 999 ? 0 : 79;

$total = max(
    0,
    $subtotal + $shipping - $discount
);

$title = 'Checkout';

require 'includes/header.php';
?>

<div class="row g-4">

    <!-- Checkout Form -->
    <div class="col-lg-7">

        <div class="panel p-4">

            <h2>Checkout</h2>

            <form method="post">

                <!-- CSRF -->
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >

                <!-- Address -->
                <label class="form-label">
                    Delivery address
                </label>

                <textarea
                    name="address"
                    class="form-control mb-3"
                    rows="3"
                    required
                    placeholder="Full delivery address"
                ><?= e($_POST['address'] ?? '') ?></textarea>


                <div class="row g-2">

                    <!-- Payment -->
                    <div class="col-md-6">

                        <label class="form-label">
                            Payment
                        </label>

                        <select
                            name="payment_method"
                            class="form-select"
                        >

                            <option value="Cash on Delivery">
                                Cash on Delivery
                            </option>

                            <option value="Demo Card">
                                Demo Card
                            </option>

                            <option value="Demo UPI">
                                Demo UPI
                            </option>

                        </select>

                    </div>


                    <!-- Coupon -->
                    <div class="col-md-6">

                        <label class="form-label">
                            Coupon
                        </label>

                        <div class="input-group">

                            <input
                                type="text"
                                name="coupon"
                                class="form-control"
                                value="<?= e($coupon_code) ?>"
                                placeholder="WELCOME10"
                            >

                            <button
                                type="submit"
                                class="btn btn-outline-purple"
                            >
                                Apply
                            </button>

                        </div>

                    </div>

                </div>


                <!-- Place Order -->
                <button
                    type="submit"
                    name="place"
                    value="1"
                    class="btn btn-purple w-100 mt-4"
                >

                    Place Order • <?= money($total) ?>

                </button>

            </form>

        </div>

    </div>


    <!-- Order Summary -->
    <div class="col-lg-5">

        <div class="panel p-4">

            <h5>
                Order summary
            </h5>


            <?php foreach ($items as $p): ?>

                <?php
                $qty = (int)(
                    $_SESSION['cart'][$p['id']] ?? 0
                );
                ?>

                <?php if ($qty > 0): ?>

                    <div
                        class="d-flex justify-content-between py-2"
                    >

                        <span>

                            <?= e($p['name']) ?>

                            ×

                            <?= $qty ?>

                        </span>


                        <strong>

                            <?= money(
                                (float)$p['price'] * $qty
                            ) ?>

                        </strong>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>


            <hr>


            <!-- Subtotal -->
            <div
                class="d-flex justify-content-between"
            >

                <span>
                    Subtotal
                </span>

                <strong>
                    <?= money($subtotal) ?>
                </strong>

            </div>


            <!-- Shipping -->
            <div
                class="d-flex justify-content-between"
            >

                <span>
                    Shipping
                </span>

                <strong>

                    <?=
                    $shipping
                        ? money($shipping)
                        : 'Free'
                    ?>

                </strong>

            </div>


            <!-- Discount -->
            <div
                class="d-flex justify-content-between"
            >

                <span>
                    Discount
                </span>

                <strong>
                    -<?= money($discount) ?>
                </strong>

            </div>


            <hr>


            <!-- Total -->
            <div
                class="d-flex justify-content-between fs-5"
            >

                <strong>
                    Total
                </strong>

                <strong class="text-purple">
                    <?= money($total) ?>
                </strong>

            </div>

        </div>

    </div>

</div>

<?php require 'includes/footer.php'; ?>