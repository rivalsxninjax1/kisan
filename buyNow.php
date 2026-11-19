<?php
require __DIR__ . '/includes/bootstrap.php';

require_role('buyer');
$user = current_user();

$pid = is_post() ? (int) post_str('pid') : get_int('pid');
$product = $pid > 0 ? db_row(
    'SELECT p.*, f.fname FROM fproduct p JOIN farmer f ON f.fid = p.fid WHERE p.pid = ?',
    'i',
    [$pid]
) : null;

if (!$product) {
    flash('error', 'That product could not be found.');
    redirect('market.php');
}

$errors = [];
$old = [
    'name'    => $user['name'],
    'city'    => '',
    'mobile'  => $user['mobile'],
    'email'   => $user['email'],
    'pincode' => '',
    'addr'    => $user['address'],
];

if (is_post()) {
    csrf_check();
    foreach ($old as $key => $unused) {
        $old[$key] = post_str($key);
    }

    if (str_len($old['name']) < 2 || str_len($old['name']) > 100) {
        $errors[] = 'Please enter your full name.';
    }
    if ($old['city'] === '' || str_len($old['city']) > 100) {
        $errors[] = 'Please enter your city.';
    }
    if (!valid_mobile($old['mobile'])) {
        $errors[] = 'Mobile number must be exactly 10 digits.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['pincode'] === '' || str_len($old['pincode']) > 20) {
        $errors[] = 'Please enter your postal / pin code.';
    }
    if (str_len($old['addr']) < 3 || str_len($old['addr']) > 255) {
        $errors[] = 'Please enter your delivery address.';
    }

    if (!$errors) {
        db_run(
            'INSERT INTO `transaction` (bid, pid, name, city, mobile, email, pincode, addr) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'iissssss',
            [$user['id'], $pid, $old['name'], $old['city'], $old['mobile'], $old['email'], $old['pincode'], $old['addr']]
        );
        // The product has been ordered, so it no longer needs to sit in the Jhola.
        db_run('DELETE FROM mycart WHERE bid = ? AND pid = ?', 'ii', [$user['id'], $pid]);

        flash('success', 'Order placed! The farmer will contact you to arrange delivery.');
        redirect('orders.php');
    }
}

$pageTitle = 'Place your order';
$active    = 'market';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <h1>Delivery details</h1>
        <p class="lead muted">Tell <?= e($product['fname']) ?> where to send your order.</p>
    </div>
</section>

<section class="section-tight">
    <div class="wrap">
        <div class="split">
            <div class="card card-pad">
                <?php if ($errors): ?>
                    <div class="form-errors" role="alert">
                        <strong>Please fix the following:</strong>
                        <ul>
                            <?php foreach ($errors as $msg): ?>
                                <li><?= e($msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="buyNow.php?pid=<?= (int) $pid ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="pid" value="<?= (int) $pid ?>">
                    <div class="form-grid">
                        <div class="field">
                            <label for="name">Full name</label>
                            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="mobile">Mobile number</label>
                            <input type="text" id="mobile" name="mobile" value="<?= e($old['mobile']) ?>" inputmode="numeric" maxlength="10" autocomplete="tel" required>
                        </div>
                        <div class="field span-2">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
                        </div>
                        <div class="field">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?= e($old['city']) ?>" autocomplete="address-level2" required>
                        </div>
                        <div class="field">
                            <label for="pincode">Postal / pin code</label>
                            <input type="text" id="pincode" name="pincode" value="<?= e($old['pincode']) ?>" autocomplete="postal-code" required>
                        </div>
                        <div class="field span-2">
                            <label for="addr">Delivery address</label>
                            <input type="text" id="addr" name="addr" value="<?= e($old['addr']) ?>" autocomplete="street-address" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Confirm order</button>
                    <a class="btn btn-ghost btn-lg" href="review.php?pid=<?= (int) $pid ?>">Cancel</a>
                </form>
            </div>

            <aside class="card summary">
                <img src="<?= e(product_image($product['pimage'])) ?>" alt="<?= e($product['product']) ?>">
                <h3><?= e($product['product']) ?></h3>
                <div class="summary-row"><span>Seller</span><span><?= e($product['fname']) ?></span></div>
                <div class="summary-row"><span>Category</span><span><?= e($product['pcat']) ?></span></div>
                <div class="summary-row summary-total"><span>Price</span><span><?= e(money($product['price'])) ?> / <?= e($product['unit']) ?></span></div>
                <p class="muted small" style="margin:1rem 0 0">The farmer will contact you to confirm the quantity and delivery.</p>
            </aside>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
