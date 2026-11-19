<?php
require __DIR__ . '/includes/bootstrap.php';

require_login();
$user = current_user();

// Farmers can delete their own products.
if (is_post() && post_str('action') === 'delete_product') {
    csrf_check();
    require_role('farmer');
    $pid   = (int) post_str('pid');
    $owned = db_row('SELECT pid, pimage FROM fproduct WHERE pid = ? AND fid = ?', 'ii', [$pid, $user['id']]);

    if (!$owned) {
        flash('error', 'You can only delete your own products.');
    } elseif ((int) db_val('SELECT COUNT(*) FROM `transaction` WHERE pid = ?', 'i', [$pid]) > 0) {
        flash('error', 'This product already has orders, so it cannot be deleted.');
    } else {
        db_run('DELETE FROM fproduct WHERE pid = ? AND fid = ?', 'ii', [$pid, $user['id']]);
        if (preg_match('/^p\d+_[0-9a-f]{16}\.(jpg|png)$/', (string) $owned['pimage'])) {
            @unlink(__DIR__ . '/productimages/' . basename($owned['pimage']));
        }
        flash('success', 'Product deleted.');
    }
    redirect('profileView.php#products');
}

$isFarmer = $user['role'] === 'farmer';
$products = [];
$orders   = [];
$orderCount = 0;
$avgRating  = null;

if ($isFarmer) {
    $products = db_all(
        'SELECT pid, product, pcat, price, unit, pimage FROM fproduct WHERE fid = ? ORDER BY pid DESC',
        'i',
        [$user['id']]
    );
    $orderCount = (int) db_val(
        'SELECT COUNT(*) FROM `transaction` t JOIN fproduct p ON p.pid = t.pid WHERE p.fid = ?',
        'i',
        [$user['id']]
    );
    $avg = db_val(
        'SELECT AVG(r.rating) FROM review r JOIN fproduct p ON p.pid = r.pid WHERE p.fid = ?',
        'i',
        [$user['id']]
    );
    $avgRating = $avg !== null ? (float) $avg : null;
} else {
    $orderCount = (int) db_val('SELECT COUNT(*) FROM `transaction` WHERE bid = ?', 'i', [$user['id']]);
}

$pageTitle = 'My profile';
$active    = 'profile';
require __DIR__ . '/includes/header.php';
?>

<section class="profile-hero">
    <div class="wrap profile-head">
        <img class="avatar avatar-lg" src="<?= e(avatar_url($user['pic'])) ?>" alt="Profile photo">
        <div>
            <span class="chip chip-sun"><?= $isFarmer ? 'Farmer' : 'Buyer' ?></span>
            <h1><?= e($user['name']) ?></h1>
            <p class="muted" style="margin:0">@<?= e($user['username']) ?></p>
        </div>
        <div class="profile-actions">
            <?php if ($isFarmer): ?>
                <a class="btn btn-primary" href="uploadProduct.php"><i class="fa fa-plus" aria-hidden="true"></i> List a product</a>
            <?php else: ?>
                <a class="btn btn-primary" href="myCart.php"><i class="fa fa-shopping-cart" aria-hidden="true"></i> My Jhola</a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="orders.php"><?= $isFarmer ? 'Orders received' : 'My orders' ?> (<?= $orderCount ?>)</a>
            <a class="btn btn-ghost" href="profileEdit.php"><i class="fa fa-pencil" aria-hidden="true"></i> Edit profile</a>
        </div>
    </div>
</section>

<section class="section-tight" style="padding-top:2.5rem">
    <div class="wrap">
        <div class="info-grid">
            <div class="card info-card">
                <span class="info-label"><i class="fa fa-envelope" aria-hidden="true"></i> Email</span>
                <span class="info-value"><?= e($user['email']) ?></span>
            </div>
            <div class="card info-card">
                <span class="info-label"><i class="fa fa-phone" aria-hidden="true"></i> Mobile</span>
                <span class="info-value"><?= e($user['mobile']) ?></span>
            </div>
            <div class="card info-card">
                <span class="info-label"><i class="fa fa-map-marker" aria-hidden="true"></i> Address</span>
                <span class="info-value"><?= e($user['address']) ?></span>
            </div>
            <?php if ($isFarmer): ?>
                <div class="card info-card">
                    <span class="info-label"><i class="fa fa-star" aria-hidden="true"></i> Average rating</span>
                    <span class="info-value">
                        <?php if ($avgRating !== null): ?>
                            <?= stars_html($avgRating) ?> <?= e(number_format($avgRating, 1)) ?>
                        <?php else: ?>
                            <span class="muted">No reviews yet</span>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($isFarmer): ?>
            <div id="products">
                <div class="row-between" style="margin-bottom:1.25rem">
                    <h2 style="margin:0">My products</h2>
                    <a class="btn btn-primary btn-sm" href="uploadProduct.php"><i class="fa fa-plus" aria-hidden="true"></i> Add product</a>
                </div>

                <?php if ($products): ?>
                    <div class="my-products">
                        <?php foreach ($products as $p): ?>
                            <article class="card my-product">
                                <a href="review.php?pid=<?= (int) $p['pid'] ?>"><img src="<?= e(product_image($p['pimage'])) ?>" alt=""></a>
                                <div>
                                    <h3><a href="review.php?pid=<?= (int) $p['pid'] ?>" style="color:inherit;text-decoration:none"><?= e($p['product']) ?></a></h3>
                                    <p class="muted small" style="margin:0"><?= e($p['pcat']) ?> &middot; <?= e(money($p['price'])) ?> / <?= e($p['unit']) ?></p>
                                </div>
                                <div class="row-actions">
                                    <a class="btn btn-ghost btn-sm" href="uploadProduct.php?pid=<?= (int) $p['pid'] ?>">Edit</a>
                                    <form method="post" action="profileView.php" class="inline-form" data-confirm="Delete &quot;<?= e($p['product']) ?>&quot;? This cannot be undone.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="pid" value="<?= (int) $p['pid'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty">
                        <i class="fa fa-leaf" aria-hidden="true"></i>
                        <h3>You have not listed anything yet</h3>
                        <p>Add your first product and it will appear on the Digital Bazar straight away.</p>
                        <a class="btn btn-primary" href="uploadProduct.php">List a product</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                <h3>Ready to shop?</h3>
                <p>Browse fresh produce from farmers and add what you like to your Jhola.</p>
                <a class="btn btn-primary" href="market.php">Browse the Bazar</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
