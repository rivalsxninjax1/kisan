<?php
require __DIR__ . '/includes/bootstrap.php';

require_role('buyer');
$bid = current_user()['id'];

// Adding and removing are POST actions with CSRF protection (the old version changed the cart from a plain link).
if (is_post()) {
    csrf_check();
    $action = post_str('action');
    $pid    = (int) post_str('pid');

    if ($action === 'add') {
        $exists = db_val('SELECT COUNT(*) FROM fproduct WHERE pid = ?', 'i', [$pid]);
        if (!$exists) {
            flash('error', 'That product is no longer available.');
            redirect('market.php');
        }
        $inCart = db_val('SELECT COUNT(*) FROM mycart WHERE bid = ? AND pid = ?', 'ii', [$bid, $pid]);
        if ($inCart) {
            flash('info', 'That product is already in your Jhola.');
        } else {
            db_run('INSERT INTO mycart (bid, pid) VALUES (?, ?)', 'ii', [$bid, $pid]);
            flash('success', 'Added to your Jhola.');
        }
        redirect('review.php?pid=' . $pid);
    }

    if ($action === 'remove') {
        db_run('DELETE FROM mycart WHERE bid = ? AND pid = ?', 'ii', [$bid, $pid]);
        flash('success', 'Removed from your Jhola.');
        redirect('myCart.php');
    }

    redirect('myCart.php');
}

$items = db_all(
    'SELECT p.pid, p.product, p.pcat, p.price, p.unit, p.pimage, f.fname
       FROM mycart c
       JOIN fproduct p ON p.pid = c.pid
       JOIN farmer f ON f.fid = p.fid
      WHERE c.bid = ?
   ORDER BY p.product',
    'i',
    [$bid]
);

$total = 0.0;
foreach ($items as $it) {
    $total += (float) $it['price'];
}

$pageTitle = 'My Jhola';
$active    = 'cart';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <h1>My Jhola</h1>
        <p class="lead muted">Your saved products. Order each one when you are ready.</p>
    </div>
</section>

<section class="section-tight">
    <div class="wrap">
        <?php if ($items): ?>
            <div class="split">
                <div class="cart-list">
                    <?php foreach ($items as $it): ?>
                        <article class="card cart-item">
                            <a href="review.php?pid=<?= (int) $it['pid'] ?>"><img class="cart-thumb" src="<?= e(product_image($it['pimage'])) ?>" alt="<?= e($it['product']) ?>"></a>
                            <div class="cart-info">
                                <h3><a href="review.php?pid=<?= (int) $it['pid'] ?>"><?= e($it['product']) ?></a></h3>
                                <p class="muted small"><?= e($it['pcat']) ?> &middot; by <?= e($it['fname']) ?></p>
                                <span class="cart-price"><?= e(money($it['price'])) ?> / <?= e($it['unit']) ?></span>
                            </div>
                            <div class="cart-actions">
                                <a class="btn btn-primary btn-sm" href="buyNow.php?pid=<?= (int) $it['pid'] ?>">Buy now</a>
                                <form method="post" action="myCart.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="pid" value="<?= (int) $it['pid'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm btn-block"><i class="fa fa-trash-o" aria-hidden="true"></i> Remove</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="card summary">
                    <h3>Summary</h3>
                    <div class="summary-row"><span>Items</span><span><?= count($items) ?></span></div>
                    <div class="summary-row summary-total"><span>Estimated total (1 unit each)</span><span><?= e(money($total)) ?></span></div>
                    <p class="muted small" style="margin:1rem 0 0">Each product is ordered separately with its own delivery details.</p>
                </aside>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                <h3>Your Jhola is empty</h3>
                <p>Browse the Digital Bazar and add products you like.</p>
                <a class="btn btn-primary" href="market.php">Browse the Bazar</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
