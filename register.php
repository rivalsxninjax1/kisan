<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('profileView.php');
}

$errors = [];
$old    = ['name' => '', 'username' => '', 'mobile' => '', 'email' => '', 'address' => ''];
$role   = (($_GET['role'] ?? '') === 'farmer') ? 'farmer' : 'buyer';

if (is_post()) {
    csrf_check();
    $role = (post_str('role') === 'farmer') ? 'farmer' : 'buyer';
    foreach ($old as $key => $unused) {
        $old[$key] = post_str($key);
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm'] ?? '');

    if (str_len($old['name']) < 2 || str_len($old['name']) > 100) {
        $errors[] = 'Please enter your full name (2 to 100 characters).';
    }
    if (!valid_username($old['username'])) {
        $errors[] = 'Username must be 3 to 30 characters: letters, numbers, dots, dashes or underscores.';
    }
    if (!valid_mobile($old['mobile'])) {
        $errors[] = 'Mobile number must be exactly 10 digits.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || str_len($old['email']) > 190) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (str_len($old['address']) < 3 || str_len($old['address']) > 255) {
        $errors[] = 'Please enter your address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    $c = role_cols($role);

    if (!$errors) {
        // Usernames are unique across farmers AND buyers (blog posts and likes use them).
        $taken = db_val('SELECT COUNT(*) FROM farmer WHERE fusername = ?', 's', [$old['username']])
               + db_val('SELECT COUNT(*) FROM buyer WHERE busername = ?', 's', [$old['username']]);
        if ($taken > 0) {
            $errors[] = 'That username is already taken. Please choose another.';
        }
        $emailTaken = db_val('SELECT COUNT(*) FROM `' . $c['table'] . '` WHERE `' . $c['email'] . '` = ?', 's', [$old['email']]);
        if ($emailTaken > 0) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            db_run(
                'INSERT INTO `' . $c['table'] . '` (`' . $c['name'] . '`, `' . $c['username'] . '`, `' . $c['password'] . '`, `'
                . $c['email'] . '`, `' . $c['mobile'] . '`, `' . $c['address'] . '`) VALUES (?, ?, ?, ?, ?, ?)',
                'ssssss',
                [$old['name'], $old['username'], $hash, $old['email'], $old['mobile'], $old['address']]
            );
            $row = db_row('SELECT * FROM `' . $c['table'] . '` WHERE `' . $c['id'] . '` = ?', 'i', [db_insert_id()]);
            login_user($row, $role);
            flash('success', 'Welcome to KISAN, ' . $old['name'] . '! Your account is ready.');
            redirect('profileView.php');
        } catch (mysqli_sql_exception $ex) {
            if ((int) $ex->getCode() === 1062) {
                $errors[] = 'That username or email is already registered.';
            } else {
                throw $ex;
            }
        }
    }
}

$pageTitle = 'Create an account';
require __DIR__ . '/includes/header.php';
?>

<div class="auth">
    <aside class="auth-visual">
        <div>
            <h2>Grow your market with KISAN.</h2>
            <p>Farmers list their harvest and set their own prices. Buyers shop fresh, local produce straight from the source.</p>
        </div>
    </aside>

    <section class="auth-panel">
        <div class="auth-card">
            <h1>Create your account</h1>
            <p class="muted">It only takes a minute.</p>

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

            <form method="post" action="register.php" data-password-match novalidate>
                <?= csrf_field() ?>

                <div class="segmented" role="radiogroup" aria-label="Account type">
                    <input type="radio" id="role-buyer" name="role" value="buyer" <?= $role === 'buyer' ? 'checked' : '' ?>>
                    <label for="role-buyer"><i class="fa fa-shopping-cart" aria-hidden="true"></i> I want to buy</label>
                    <input type="radio" id="role-farmer" name="role" value="farmer" <?= $role === 'farmer' ? 'checked' : '' ?>>
                    <label for="role-farmer"><i class="fa fa-leaf" aria-hidden="true"></i> I want to sell</label>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" autocomplete="name" required>
                    </div>
                    <div class="field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= e($old['username']) ?>" autocomplete="username" required>
                    </div>
                    <div class="field">
                        <label for="mobile">Mobile number</label>
                        <input type="text" id="mobile" name="mobile" value="<?= e($old['mobile']) ?>" inputmode="numeric" maxlength="10" autocomplete="tel" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
                    </div>
                    <div class="field span-2">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?= e($old['address']) ?>" autocomplete="street-address" required>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" data-pw-new required>
                    </div>
                    <div class="field">
                        <label for="confirm">Confirm password</label>
                        <input type="password" id="confirm" name="confirm" minlength="8" autocomplete="new-password" data-pw-confirm required>
                    </div>
                    <p class="hint span-2" data-pw-hint>Use at least 8 characters.</p>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Create account</button>
            </form>

            <p class="auth-alt">Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
