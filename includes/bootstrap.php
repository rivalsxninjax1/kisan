<?php
/**
 * Shared bootstrap. Every page starts with:
 *
 *     require __DIR__ . '/includes/bootstrap.php';
 *
 * It starts the session, connects to the database and provides the helpers
 * used across the site: output escaping, prepared-statement shortcuts, CSRF
 * protection, flash messages and login guards.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/../db.php';

const PRODUCT_CATEGORIES = ['Fruit', 'Vegetable', 'Grains'];
const PRODUCT_UNITS      = ['kg', 'dozen', 'piece', 'litre', 'quintal'];

/* -------------------------------------------------------------------------
 * Friendly error page for anything uncaught (e.g. a failed query)
 * ---------------------------------------------------------------------- */
set_exception_handler(function ($ex) {
    error_log((string) $ex);
    if (!headers_sent()) {
        http_response_code(500);
    }
    $detail = KISAN_DEBUG ? e($ex->getMessage()) : '';
    echo '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Something went wrong - KISAN</title>';
    echo '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:12vh auto;padding:0 1.5rem;line-height:1.6">';
    echo '<h1>Something went wrong</h1>';
    echo '<p>Sorry, we could not complete that request. If you just set the project up, make sure you imported the latest <code>agroculture.sql</code>.</p>';
    if ($detail !== '') {
        echo '<p style="color:#a33"><small>' . $detail . '</small></p>';
    }
    echo '<p><a href="index.php">Back to home</a></p></body>';
    exit;
});

/* -------------------------------------------------------------------------
 * Output helpers
 * ---------------------------------------------------------------------- */

/** Escape a value for safe output inside HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape multi-line text and keep its line breaks. */
function nl($value): string
{
    return nl2br(e($value));
}

function money($amount): string
{
    $text = number_format((float) $amount, 2, '.', ',');
    $text = preg_replace('/\.00$/', '', $text);
    return 'Rs. ' . $text;
}

function when($timestamp): string
{
    $t = strtotime((string) $timestamp);
    return $t ? date('M j, Y \a\t g:i a', $t) : '';
}

function str_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function stars_html(float $value, int $max = 5): string
{
    $out = '<span class="stars" aria-label="' . e(number_format($value, 1)) . ' out of ' . $max . '">';
    for ($i = 1; $i <= $max; $i++) {
        if ($value >= $i - 0.25) {
            $cls = 'fa-star';
        } elseif ($value >= $i - 0.75) {
            $cls = 'fa-star-half-o';
        } else {
            $cls = 'fa-star-o';
        }
        $out .= '<i class="fa ' . $cls . '" aria-hidden="true"></i>';
    }
    return $out . '</span>';
}

/** Product photo URL, falling back to a placeholder if the file is missing. */
function product_image($file): string
{
    $file = basename((string) $file);
    if ($file !== '' && is_file(__DIR__ . '/../productimages/' . $file)) {
        return 'productimages/' . rawurlencode($file);
    }
    return 'images/placeholder.svg';
}

/** Profile photo URL, falling back to the default avatar. */
function avatar_url($file): string
{
    $file = basename((string) $file);
    $path = __DIR__ . '/../images/profileImages/' . $file;
    if ($file !== '' && is_file($path)) {
        return 'images/profileImages/' . rawurlencode($file) . '?v=' . filemtime($path);
    }
    return 'images/profileImages/profile0.png';
}

/* -------------------------------------------------------------------------
 * Request helpers
 * ---------------------------------------------------------------------- */

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_str(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function get_int(string $key): int
{
    $v = $_GET[$key] ?? 0;
    return is_string($v) ? (int) $v : 0;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function valid_username(string $u): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $u);
}

function valid_mobile(string $m): bool
{
    return (bool) preg_match('/^[0-9]{10}$/', $m);
}

/* -------------------------------------------------------------------------
 * Flash messages (one-time notices shown after a redirect)
 * ---------------------------------------------------------------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flash_pull(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

/* -------------------------------------------------------------------------
 * CSRF protection for every POST form
 * ---------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Stops the request if the CSRF token is missing or wrong. */
function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        flash('error', 'Your session expired. Please try again.');
        redirect(safe_target(current_page_target()));
    }
}

/* -------------------------------------------------------------------------
 * Database helpers (always use prepared statements)
 * ---------------------------------------------------------------------- */

function db_query(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** All rows as an array of associative arrays. */
function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt   = db_query($sql, $types, $params);
    $result = $stmt->get_result();
    $rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/** First row, or null. */
function db_row(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows ? $rows[0] : null;
}

/** First column of the first row, or null. */
function db_val(string $sql, string $types = '', array $params = [])
{
    $row = db_row($sql, $types, $params);
    return $row ? reset($row) : null;
}

/** Runs INSERT/UPDATE/DELETE and returns the number of affected rows. */
function db_run(string $sql, string $types = '', array $params = []): int
{
    $stmt = db_query($sql, $types, $params);
    $n    = $stmt->affected_rows;
    $stmt->close();
    return (int) $n;
}

function db_insert_id(): int
{
    global $conn;
    return (int) $conn->insert_id;
}

/* -------------------------------------------------------------------------
 * Users and login
 * ---------------------------------------------------------------------- */

/** Column names for the farmer or buyer table. */
function role_cols(string $role): array
{
    if ($role === 'farmer') {
        return [
            'table' => 'farmer', 'id' => 'fid', 'name' => 'fname', 'username' => 'fusername',
            'password' => 'fpassword', 'email' => 'femail', 'mobile' => 'fmobile', 'address' => 'faddress',
        ];
    }
    return [
        'table' => 'buyer', 'id' => 'bid', 'name' => 'bname', 'username' => 'busername',
        'password' => 'bpassword', 'email' => 'bemail', 'mobile' => 'bmobile', 'address' => 'baddress',
    ];
}

function build_session_user(array $row, string $role): array
{
    $c   = role_cols($role);
    $pic = 'profile0.png';
    if ($role === 'farmer' && (int) ($row['picStatus'] ?? 0) === 1) {
        $pic = 'profile' . $row['fid'] . '.' . $row['picExt'];
    }
    return [
        'id'       => (int) $row[$c['id']],
        'role'     => $role,
        'username' => $row[$c['username']],
        'name'     => $row[$c['name']],
        'email'    => $row[$c['email']],
        'mobile'   => $row[$c['mobile']],
        'address'  => $row[$c['address']],
        'pic'      => $pic,
    ];
}

/** Logs a user in. A fresh session id prevents session fixation. */
function login_user(array $row, string $role): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = build_session_user($row, $role);
}

/** Reloads the logged-in user's details from the database. */
function refresh_session_user(): void
{
    $u = current_user();
    if (!$u) {
        return;
    }
    $c   = role_cols($u['role']);
    $row = db_row('SELECT * FROM `' . $c['table'] . '` WHERE `' . $c['id'] . '` = ? LIMIT 1', 'i', [$u['id']]);
    if ($row) {
        $_SESSION['user'] = build_session_user($row, $u['role']);
    }
}

function current_user(): ?array
{
    return (isset($_SESSION['user']) && is_array($_SESSION['user'])) ? $_SESSION['user'] : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_farmer(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'farmer';
}

function is_buyer(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'buyer';
}

/** Only allows redirects to a page inside this site. */
function safe_target($target): string
{
    $target = is_string($target) ? $target : '';
    if (preg_match('/^[A-Za-z0-9_\-]+\.php(\?[A-Za-z0-9_=&%.\-]*)?(#[A-Za-z0-9_\-]*)?$/', $target)) {
        return $target;
    }
    return 'profileView.php';
}

/** The page being requested, e.g. "buyNow.php?pid=3" (relative to the site root). */
function current_page_target(): string
{
    $page  = basename((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return $query !== '' ? $page . '?' . $query : $page;
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    $_SESSION['after_login'] = current_page_target();
    flash('error', 'Please log in to continue.');
    redirect('login.php');
}

function require_role(string $role): void
{
    require_login();
    if (current_user()['role'] !== $role) {
        flash('error', $role === 'buyer'
            ? 'This page is only for buyer accounts.'
            : 'This page is only for farmer accounts.');
        redirect('profileView.php');
    }
}

/* -------------------------------------------------------------------------
 * Image uploads
 * ---------------------------------------------------------------------- */

/**
 * Checks an uploaded image by looking at its real contents, not its file name.
 * Returns [extension, null] when valid, or [null, error message].
 */
function check_uploaded_image(array $file, int $maxBytes): array
{
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) {
        return [null, 'Please choose an image first.'];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return [null, 'The upload failed. Please try again.'];
    }
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        return [null, 'That image is too large (maximum ' . round($maxBytes / 1048576) . ' MB).'];
    }
    if (!is_uploaded_file((string) $file['tmp_name'])) {
        return [null, 'Invalid upload.'];
    }
    $info  = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
    if (!$info || !isset($types[$info[2]])) {
        return [null, 'Only JPG and PNG images are allowed.'];
    }
    return [$types[$info[2]], null];
}

/* -------------------------------------------------------------------------
 * Shared UI pieces
 * ---------------------------------------------------------------------- */

/** A product card used on the home page and in the bazar. */
function product_card(array $p): string
{
    $href = 'review.php?pid=' . (int) $p['pid'];
    ob_start();
    ?>
    <article class="card product-card">
        <a class="card-media" href="<?= e($href) ?>">
            <img src="<?= e(product_image($p['pimage'] ?? '')) ?>" alt="<?= e($p['product']) ?>" loading="lazy">
            <span class="chip"><?= e($p['pcat']) ?></span>
        </a>
        <div class="card-body">
            <h3 class="card-title"><a href="<?= e($href) ?>"><?= e($p['product']) ?></a></h3>
            <p class="muted small"><i class="fa fa-user" aria-hidden="true"></i> <?= e($p['fname'] ?? 'Farmer') ?></p>
            <div class="card-foot">
                <span class="price"><?= e(money($p['price'])) ?> <small>/ <?= e($p['unit'] ?? 'kg') ?></small></span>
                <a class="btn btn-sm btn-primary" href="<?= e($href) ?>">View</a>
            </div>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}
