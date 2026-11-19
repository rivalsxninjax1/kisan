<?php
require __DIR__ . '/includes/bootstrap.php';

$featured = db_all(
    'SELECT p.pid, p.product, p.pcat, p.price, p.unit, p.pimage, f.fname
       FROM fproduct p
       JOIN farmer f ON f.fid = p.fid
   ORDER BY p.pid DESC
      LIMIT 6'
);

$pageTitle = 'Fresh from the farm, straight to you';
$active    = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="wrap">
        <p class="eyebrow">Your product &middot; Your value</p>
        <h1>Fresh from the field, <em>straight to your table.</em></h1>
        <p class="lead">KISAN is a digital marketplace that connects farmers directly with buyers: fair prices for the people who grow our food, and fresh local produce for everyone else.</p>

        <div class="hero-actions">
            <a class="btn btn-accent btn-lg" href="market.php"><i class="fa fa-leaf" aria-hidden="true"></i> Browse the Bazar</a>
            <?php if (!is_logged_in()): ?>
                <a class="btn btn-light btn-lg" href="register.php?role=farmer">Sell as a farmer</a>
            <?php elseif (is_farmer()): ?>
                <a class="btn btn-light btn-lg" href="uploadProduct.php">List a product</a>
            <?php else: ?>
                <a class="btn btn-light btn-lg" href="myCart.php">Open my Jhola</a>
            <?php endif; ?>
        </div>

        <ul class="hero-points">
            <li><i class="fa fa-check-circle" aria-hidden="true"></i> Direct from farmers</li>
            <li><i class="fa fa-check-circle" aria-hidden="true"></i> Fresh, local produce</li>
            <li><i class="fa fa-check-circle" aria-hidden="true"></i> Simple ordering</li>
        </ul>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-head">
            <h2>Explore the new way of trading</h2>
            <p>Everything farmers and buyers need, in one friendly place.</p>
        </div>
        <div class="feature-grid">
            <article class="feature">
                <span class="feature-icon"><i class="fa fa-shopping-cart" aria-hidden="true"></i></span>
                <h3>Digital Bazar</h3>
                <p>Browse fruit, vegetables and grains listed by farmers, with clear prices and photos.</p>
            </article>
            <article class="feature">
                <span class="feature-icon"><i class="fa fa-comments-o" aria-hidden="true"></i></span>
                <h3>KISAN Blog</h3>
                <p>Share farming tips, seasonal advice and stories with the rest of the community.</p>
            </article>
            <article class="feature">
                <span class="feature-icon"><i class="fa fa-exchange" aria-hidden="true"></i></span>
                <h3>Direct trade</h3>
                <p>Farmers set their own prices and buyers order straight from the source.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="wrap">
        <div class="section-head">
            <h2>How it works</h2>
            <p>Three simple steps from field to doorstep.</p>
        </div>
        <ol class="steps">
            <li>
                <h3>Create an account</h3>
                <p>Join as a farmer to sell, or as a buyer to shop.</p>
            </li>
            <li>
                <h3>List or browse</h3>
                <p>Farmers add products with a photo and price. Buyers fill their Jhola.</p>
            </li>
            <li>
                <h3>Order and receive</h3>
                <p>Place an order and the farmer gets your details to arrange delivery.</p>
            </li>
        </ol>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="row-between" style="margin-bottom:2rem">
            <div class="section-head">
                <h2>Fresh on the Bazar</h2>
                <p>The newest listings from our farmers.</p>
            </div>
            <a class="btn btn-ghost" href="market.php">View all products <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($featured): ?>
            <div class="grid">
                <?php foreach ($featured as $p): ?>
                    <?= product_card($p) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fa fa-leaf" aria-hidden="true"></i>
                <h3>No products yet</h3>
                <p>Farmers can list the first product in just a minute.</p>
                <a class="btn btn-primary" href="<?= is_farmer() ? 'uploadProduct.php' : 'register.php?role=farmer' ?>">Become a seller</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!is_logged_in()): ?>
<section class="cta-band">
    <div class="wrap cta-inner">
        <div>
            <h2>Ready to join the KISAN family?</h2>
            <p>Create a free account in under a minute and start trading.</p>
        </div>
        <div class="hero-actions">
            <a class="btn btn-accent btn-lg" href="register.php">Create an account</a>
            <a class="btn btn-light btn-lg" href="login.php">Log in</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
