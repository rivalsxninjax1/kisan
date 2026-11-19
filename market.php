<?php
require __DIR__ . '/includes/bootstrap.php';

$cat  = isset($_GET['cat']) && is_string($_GET['cat']) ? $_GET['cat'] : 'all';
$q    = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'new';

if (!in_array($cat, PRODUCT_CATEGORIES, true)) {
    $cat = 'all';
}
$orders = ['new' => 'p.pid DESC', 'low' => 'p.price ASC', 'high' => 'p.price DESC'];
if (!isset($orders[$sort])) {
    $sort = 'new';
}

$where  = [];
$types  = '';
$params = [];
if ($cat !== 'all') {
    $where[]  = 'p.pcat = ?';
    $types   .= 's';
    $params[] = $cat;
}
if ($q !== '') {
    $like     = '%' . addcslashes($q, '%_\\') . '%';
    $where[]  = '(p.product LIKE ? OR p.pinfo LIKE ?)';
    $types   .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

$sql = 'SELECT p.pid, p.product, p.pcat, p.price, p.unit, p.pimage, f.fname
          FROM fproduct p
          JOIN farmer f ON f.fid = p.fid'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY ' . $orders[$sort] . ' LIMIT 96';
$products = db_all($sql, $types, $params);

/** Builds a link that keeps the other filters. */
function bazar_link(array $override): string
{
    global $cat, $q, $sort;
    $args = array_merge(['cat' => $cat, 'q' => $q, 'sort' => $sort], $override);
    if ($args['cat'] === 'all') {
        unset($args['cat']);
    }
    if ($args['q'] === '') {
        unset($args['q']);
    }
    if ($args['sort'] === 'new') {
        unset($args['sort']);
    }
    return 'market.php' . ($args ? '?' . http_build_query($args) : '');
}

$pageTitle = 'Digital Bazar';
$active    = 'market';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <p class="eyebrow">Digital Bazar</p>
        <h1>Fresh from the farm</h1>
        <p class="lead muted">Fruit, vegetables and grains from farmers near you. Search, filter and order directly.</p>
    </div>
</section>

<section class="section-tight">
    <div class="wrap">
        <form class="toolbar" method="get" action="market.php" role="search">
            <?php if ($cat !== 'all'): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
            <div class="search-box">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search for mango, rice, potato..." aria-label="Search products">
            </div>
            <select class="input" name="sort" aria-label="Sort products" onchange="this.form.submit()">
                <option value="new"  <?= $sort === 'new'  ? 'selected' : '' ?>>Newest first</option>
                <option value="low"  <?= $sort === 'low'  ? 'selected' : '' ?>>Price: low to high</option>
                <option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>Price: high to low</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <nav class="pills" aria-label="Categories">
            <a class="pill <?= $cat === 'all' ? 'is-active' : '' ?>" href="<?= e(bazar_link(['cat' => 'all'])) ?>">All</a>
            <?php foreach (PRODUCT_CATEGORIES as $c): ?>
                <a class="pill <?= $cat === $c ? 'is-active' : '' ?>" href="<?= e(bazar_link(['cat' => $c])) ?>"><?= e($c) ?></a>
            <?php endforeach; ?>
        </nav>

        <p class="muted result-count">
            <?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?>
            <?php if ($q !== ''): ?> matching &ldquo;<?= e($q) ?>&rdquo;<?php endif; ?>
        </p>

        <?php if ($products): ?>
            <div class="grid">
                <?php foreach ($products as $p): ?>
                    <?= product_card($p) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <i class="fa fa-search" aria-hidden="true"></i>
                <h3>Nothing found</h3>
                <p>Try a different word or category, or clear your filters.</p>
                <a class="btn btn-primary" href="market.php">Clear filters</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
