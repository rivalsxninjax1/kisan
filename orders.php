<?php
require __DIR__ . '/includes/bootstrap.php';

require_login();
$user     = current_user();
$isFarmer = $user['role'] === 'farmer';

if ($isFarmer) {
    // Orders other people placed for this farmer's products.
    $orders = db_all(
        'SELECT t.*, p.product, p.price, p.unit, p.pimage
           FROM `transaction` t
           JOIN fproduct p ON p.pid = t.pid
          WHERE p.fid = ?
       ORDER BY t.tid DESC',
        'i',
        [$user['id']]
    );
} else {
    $orders = db_all(
        'SELECT t.*, p.product, p.price, p.unit, p.pimage, f.fname
           FROM `transaction` t
           JOIN fproduct p ON p.pid = t.pid
           JOIN farmer f ON f.fid = p.fid
          WHERE t.bid = ?
       ORDER BY t.tid DESC',
        'i',
        [$user['id']]
    );
}

$pageTitle = $isFarmer ? 'Orders received' : 'My orders';
$active    = 'profile';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <div class="row-between">
            <div>
                <h1><?= $isFarmer ? 'Orders received' : 'My orders' ?></h1>
                <p class="lead muted">
                    <?= $isFarmer
                        ? 'Contact each buyer to confirm the quantity and arrange delivery.'
                        : 'Every order you have placed. The farmer will contact you to arrange delivery.' ?>
                </p>
            </div>
            <a class="btn btn-ghost" href="profileView.php">Back to profile</a>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="wrap narrow" style="width:min(900px,100% - 2.5rem)">
        <?php if ($orders): ?>
            <div class="order-list">
                <?php foreach ($orders as $o): ?>
                    <article class="card order">
                        <div class="order-top">
                            <div class="order-product">
                                <img src="<?= e(product_image($o['pimage'])) ?>" alt="">
                                <div>
                                    <h3><?= e($o['product']) ?></h3>
                                    <span class="muted small"><?= e(money($o['price'])) ?> / <?= e($o['unit']) ?></span>
                                </div>
                            </div>
                            <div class="muted small">
                                Order #<?= (int) $o['tid'] ?> &middot; <?= e(when($o['orderTime'])) ?>
                            </div>
                        </div>
                        <dl class="order-meta">
                            <div>
                                <dt><?= $isFarmer ? 'Buyer' : 'Deliver to' ?></dt>
                                <dd><?= e($o['name']) ?></dd>
                            </div>
                            <div>
                                <dt>Address</dt>
                                <dd><?= e($o['addr']) ?>, <?= e($o['city']) ?> <?= e($o['pincode']) ?></dd>
                            </div>
                            <div>
                                <dt>Contact</dt>
                                <dd><?= e($o['mobile']) ?><br><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></dd>
                            </div>
                            <?php if (!$isFarmer): ?>
                                <div>
                                    <dt>Sold by</dt>
                                    <dd><?= e($o['fname']) ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fa fa-inbox" aria-hidden="true"></i>
                <h3>No orders yet</h3>
                <p><?= $isFarmer ? 'When a buyer orders one of your products it will show up here.' : 'Find something fresh in the Digital Bazar and place your first order.' ?></p>
                <?php if (!$isFarmer): ?><a class="btn btn-primary" href="market.php">Browse the Bazar</a><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
