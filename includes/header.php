<?php
/**
 * Page header. Set these before including it:
 *   $pageTitle  (string)  page title shown in the browser tab
 *   $active     (string)  'home' | 'market' | 'blog' | '' to highlight the nav link
 *   $bodyClass  (string)  optional extra class on <body>
 */
$pageTitle = $pageTitle ?? 'KISAN';
$active    = $active ?? '';
$bodyClass = $bodyClass ?? '';
$navUser   = current_user();
$cartCount = 0;
if ($navUser && $navUser['role'] === 'buyer') {
    $cartCount = (int) db_val('SELECT COUNT(*) FROM mycart WHERE bid = ?', 'i', [$navUser['id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · KISAN</title>
    <meta name="description" content="KISAN is a digital marketplace where farmers sell fresh produce directly to buyers.">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&amp;family=Inter:wght@400;500;600;700&amp;display=swap">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/kisan.css">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#content">Skip to content</a>

<header class="site-header">
    <div class="wrap nav-bar">
        <a class="brand" href="index.php">
            <img src="images/logo-sm.png" alt="" width="40" height="40">
            <span>KISAN</span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
            <i class="fa fa-bars" aria-hidden="true"></i>
        </button>

        <nav class="main-nav" id="main-nav" aria-label="Main">
            <a href="index.php" class="<?= $active === 'home' ? 'is-active' : '' ?>">Home</a>
            <a href="market.php" class="<?= $active === 'market' ? 'is-active' : '' ?>">Digital Bazar</a>
            <a href="blogView.php" class="<?= $active === 'blog' ? 'is-active' : '' ?>">Blog</a>

            <span class="nav-spacer"></span>

            <?php if ($navUser): ?>
                <?php if ($navUser['role'] === 'buyer'): ?>
                    <a href="myCart.php" class="nav-cart <?= $active === 'cart' ? 'is-active' : '' ?>">
                        <i class="fa fa-shopping-cart" aria-hidden="true"></i> Jhola
                        <?php if ($cartCount > 0): ?><span class="badge"><?= $cartCount ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="profileView.php" class="nav-user <?= $active === 'profile' ? 'is-active' : '' ?>">
                    <img class="avatar avatar-xs" src="<?= e(avatar_url($navUser['pic'])) ?>" alt="">
                    <span><?= e($navUser['name']) ?></span>
                </a>
                <form method="post" action="logout.php" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost btn-sm">Log out</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="btn btn-ghost btn-sm">Log in</a>
                <a href="register.php" class="btn btn-primary btn-sm">Join KISAN</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php $flashes = flash_pull(); ?>
<?php if ($flashes): ?>
    <div class="flash-stack" aria-live="polite">
        <?php foreach ($flashes as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>" role="status">
                <span><?= e($f['msg']) ?></span>
                <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="content" class="site-main">
