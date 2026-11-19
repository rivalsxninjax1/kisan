<?php
require __DIR__ . '/includes/bootstrap.php';

$pid = get_int('pid');
$product = $pid > 0 ? db_row(
    'SELECT p.*, f.fname FROM fproduct p JOIN farmer f ON f.fid = p.fid WHERE p.pid = ?',
    'i',
    [$pid]
) : null;

if (!$product) {
    flash('error', 'That product could not be found.');
    redirect('market.php');
}

$reviewError = '';
$oldComment  = '';
$oldRating   = 0;

if (is_post()) {
    csrf_check();
    require_login();

    $oldComment = post_str('comment');
    $oldRating  = (int) post_str('rating');

    if ($oldRating < 1 || $oldRating > 5) {
        $reviewError = 'Please choose a star rating from 1 to 5.';
    } elseif ($oldComment === '' || str_len($oldComment) > 500) {
        $reviewError = 'Please write a short review (up to 500 characters).';
    } else {
        db_run(
            'INSERT INTO review (pid, name, rating, comment) VALUES (?, ?, ?, ?)',
            'isis',
            [$pid, current_user()['name'], $oldRating, $oldComment]
        );
        flash('success', 'Thanks for your review!');
        redirect('review.php?pid=' . $pid . '#reviews');
    }
}

$stats   = db_row('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM review WHERE pid = ?', 'i', [$pid]);
$avg     = $stats && $stats['total'] > 0 ? (float) $stats['avg_rating'] : 0.0;
$total   = $stats ? (int) $stats['total'] : 0;
$reviews = db_all('SELECT name, rating, comment FROM review WHERE pid = ? ORDER BY reviewId DESC', 'i', [$pid]);

$pageTitle = $product['product'];
$active    = 'market';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="wrap">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="market.php">Digital Bazar</a> / <span><?= e($product['product']) ?></span>
        </nav>

        <div class="product-detail">
            <div class="card product-photo">
                <img src="<?= e(product_image($product['pimage'])) ?>" alt="<?= e($product['product']) ?>">
            </div>

            <div class="product-info">
                <span class="chip chip-static"><?= e($product['pcat']) ?></span>
                <h1><?= e($product['product']) ?></h1>

                <div class="rating-line">
                    <?php if ($total > 0): ?>
                        <?= stars_html($avg) ?>
                        <span><strong><?= e(number_format($avg, 1)) ?></strong> &middot; <?= $total ?> review<?= $total === 1 ? '' : 's' ?></span>
                    <?php else: ?>
                        <span>No reviews yet</span>
                    <?php endif; ?>
                </div>

                <p class="product-price"><?= e(money($product['price'])) ?> <small>/ <?= e($product['unit']) ?></small></p>
                <p class="product-owner"><i class="fa fa-user" aria-hidden="true"></i> Sold by <strong><?= e($product['fname']) ?></strong></p>

                <div class="product-desc"><?= nl($product['pinfo']) ?></div>

                <div class="actions">
                    <?php if (is_buyer()): ?>
                        <form method="post" action="myCart.php" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="pid" value="<?= (int) $product['pid'] ?>">
                            <button type="submit" class="btn btn-ghost btn-lg"><i class="fa fa-plus" aria-hidden="true"></i> Add to Jhola</button>
                        </form>
                        <a class="btn btn-primary btn-lg" href="buyNow.php?pid=<?= (int) $product['pid'] ?>">Buy now</a>
                    <?php elseif (is_farmer()): ?>
                        <p class="note">You are logged in as a farmer. Switch to a buyer account to place orders.</p>
                    <?php else: ?>
                        <a class="btn btn-primary btn-lg" href="login.php">Log in to buy</a>
                        <a class="btn btn-ghost btn-lg" href="register.php">Create an account</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <section class="reviews" id="reviews">
            <h2>Reviews</h2>
            <div class="review-layout">
                <div>
                    <?php if ($reviews): ?>
                        <?php foreach ($reviews as $r): ?>
                            <article class="review">
                                <header>
                                    <strong><?= e($r['name']) ?></strong>
                                    <?= stars_html((float) min(5, max(0, (int) $r['rating']))) ?>
                                </header>
                                <p><?= nl($r['comment']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty">
                            <i class="fa fa-comments-o" aria-hidden="true"></i>
                            <h3>Be the first to review</h3>
                            <p>Tried this product? Tell others what you thought.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="card card-pad review-form">
                    <h3>Rate this product</h3>
                    <?php if (is_logged_in()): ?>
                        <?php if ($reviewError): ?>
                            <div class="form-errors" role="alert"><?= e($reviewError) ?></div>
                        <?php endif; ?>
                        <form method="post" action="review.php?pid=<?= (int) $pid ?>#reviews">
                            <?= csrf_field() ?>
                            <div class="field">
                                <span class="label">Your rating</span>
                                <div class="star-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $oldRating === $i ? 'checked' : '' ?>>
                                        <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>">&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="field">
                                <label for="comment">Your review</label>
                                <textarea id="comment" name="comment" maxlength="500" rows="4" placeholder="How was the quality and freshness?"><?= e($oldComment) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Submit review</button>
                        </form>
                    <?php else: ?>
                        <p class="muted">Log in to share your experience with this product.</p>
                        <a class="btn btn-primary btn-block" href="login.php">Log in</a>
                    <?php endif; ?>
                </aside>
            </div>
        </section>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
