<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('profileView.php');
}

$error    = '';
$username = '';
$role     = (($_GET['role'] ?? '') === 'farmer') ? 'farmer' : 'buyer';

if (is_post()) {
    csrf_check();
    $username = post_str('username');
    $password = (string) ($_POST['password'] ?? '');
    $role     = (post_str('role') === 'farmer') ? 'farmer' : 'buyer';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $c   = role_cols($role);
        $row = db_row(
            'SELECT * FROM `' . $c['table'] . '` WHERE `' . $c['username'] . '` = ? LIMIT 1',
            's',
            [$username]
        );
        if ($row && password_verify($password, $row[$c['password']])) {
            login_user($row, $role);
            flash('success', 'Welcome back, ' . $row[$c['name']] . '!');
            $target = $_SESSION['after_login'] ?? '';
            unset($_SESSION['after_login']);
            redirect(safe_target($target));
        }
        $error = 'Invalid username or password for a ' . $role . ' account.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>

<div class="auth">
    <aside class="auth-visual">
        <div>
            <h2>Good to see you again.</h2>
            <p>Log in to manage your products, fill your Jhola and join the conversation on the KISAN blog.</p>
        </div>
    </aside>

    <section class="auth-panel">
        <div class="auth-card">
            <h1>Log in</h1>
            <p class="muted">Choose your account type and enter your details.</p>

            <?php if ($error): ?>
                <div class="form-errors" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
                <?= csrf_field() ?>

                <div class="segmented" role="radiogroup" aria-label="Account type">
                    <input type="radio" id="role-buyer" name="role" value="buyer" <?= $role === 'buyer' ? 'checked' : '' ?>>
                    <label for="role-buyer"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Buyer</label>
                    <input type="radio" id="role-farmer" name="role" value="farmer" <?= $role === 'farmer' ? 'checked' : '' ?>>
                    <label for="role-farmer"><i class="fa fa-leaf" aria-hidden="true"></i> Farmer</label>
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Log in</button>
            </form>

            <p class="auth-alt">New to KISAN? <a href="register.php">Create an account</a></p>
            <p class="demo-hint"><strong>Trying it out?</strong> Use <code>demo_buyer</code> or <code>demo_farmer</code> with password <code>Demo@1234</code> (from the sample database).</p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
