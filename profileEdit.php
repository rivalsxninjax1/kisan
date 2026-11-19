<?php
require __DIR__ . '/includes/bootstrap.php';

require_login();
$user = current_user();
$c    = role_cols($user['role']);
$isFarmer = $user['role'] === 'farmer';

$errors = ['profile' => [], 'photo' => [], 'password' => []];
$old = [
    'name'    => $user['name'],
    'mobile'  => $user['mobile'],
    'email'   => $user['email'],
    'address' => $user['address'],
];

if (is_post()) {
    csrf_check();
    $action = post_str('action');

    /* ---- Update contact details ---- */
    if ($action === 'update_profile') {
        foreach ($old as $key => $unused) {
            $old[$key] = post_str($key);
        }
        if (str_len($old['name']) < 2 || str_len($old['name']) > 100) {
            $errors['profile'][] = 'Please enter your full name (2 to 100 characters).';
        }
        if (!valid_mobile($old['mobile'])) {
            $errors['profile'][] = 'Mobile number must be exactly 10 digits.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || str_len($old['email']) > 190) {
            $errors['profile'][] = 'Please enter a valid email address.';
        }
        if (str_len($old['address']) < 3 || str_len($old['address']) > 255) {
            $errors['profile'][] = 'Please enter your address.';
        }
        if (!$errors['profile']) {
            $dupe = db_val(
                'SELECT COUNT(*) FROM `' . $c['table'] . '` WHERE `' . $c['email'] . '` = ? AND `' . $c['id'] . '` <> ?',
                'si',
                [$old['email'], $user['id']]
            );
            if ($dupe > 0) {
                $errors['profile'][] = 'Another account already uses that email.';
            }
        }
        if (!$errors['profile']) {
            db_run(
                'UPDATE `' . $c['table'] . '` SET `' . $c['name'] . '` = ?, `' . $c['mobile'] . '` = ?, `' . $c['email'] . '` = ?, `'
                . $c['address'] . '` = ? WHERE `' . $c['id'] . '` = ?',
                'ssssi',
                [$old['name'], $old['mobile'], $old['email'], $old['address'], $user['id']]
            );
            refresh_session_user();
            flash('success', 'Your details have been updated.');
            redirect('profileEdit.php');
        }
    }

    /* ---- Upload / remove profile photo (farmers) ---- */
    if ($isFarmer && $action === 'update_pic') {
        list($ext, $imgError) = check_uploaded_image($_FILES['profilePic'] ?? ['error' => UPLOAD_ERR_NO_FILE], 2 * 1048576);
        if ($imgError) {
            $errors['photo'][] = $imgError;
        } else {
            $dir = __DIR__ . '/images/profileImages/';
            foreach (['jpg', 'png'] as $other) {
                if (is_file($dir . 'profile' . $user['id'] . '.' . $other)) {
                    @unlink($dir . 'profile' . $user['id'] . '.' . $other);
                }
            }
            $name = 'profile' . $user['id'] . '.' . $ext;
            if (!move_uploaded_file($_FILES['profilePic']['tmp_name'], $dir . $name)) {
                $errors['photo'][] = 'The photo could not be saved. Check that images/profileImages is writable.';
            } else {
                db_run('UPDATE farmer SET picStatus = 1, picExt = ? WHERE fid = ?', 'si', [$ext, $user['id']]);
                refresh_session_user();
                flash('success', 'Profile photo updated.');
                redirect('profileEdit.php');
            }
        }
    }

    if ($isFarmer && $action === 'remove_pic') {
        $dir = __DIR__ . '/images/profileImages/';
        foreach (['jpg', 'png'] as $ext) {
            if (is_file($dir . 'profile' . $user['id'] . '.' . $ext)) {
                @unlink($dir . 'profile' . $user['id'] . '.' . $ext);
            }
        }
        db_run("UPDATE farmer SET picStatus = 0, picExt = 'png' WHERE fid = ?", 'i', [$user['id']]);
        refresh_session_user();
        flash('success', 'Profile photo removed.');
        redirect('profileEdit.php');
    }

    /* ---- Change password ---- */
    if ($action === 'change_password') {
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        $hash = db_val('SELECT `' . $c['password'] . '` FROM `' . $c['table'] . '` WHERE `' . $c['id'] . '` = ?', 'i', [$user['id']]);
        if (!$hash || !password_verify($current, (string) $hash)) {
            $errors['password'][] = 'Your current password is not correct.';
        }
        if (strlen($new) < 8) {
            $errors['password'][] = 'The new password must be at least 8 characters.';
        }
        if ($new !== $confirm) {
            $errors['password'][] = 'The new passwords do not match.';
        }
        if (!$errors['password']) {
            db_run(
                'UPDATE `' . $c['table'] . '` SET `' . $c['password'] . '` = ? WHERE `' . $c['id'] . '` = ?',
                'si',
                [password_hash($new, PASSWORD_DEFAULT), $user['id']]
            );
            session_regenerate_id(true);
            flash('success', 'Your password has been changed.');
            redirect('profileEdit.php');
        }
    }
}

$pageTitle = 'Account settings';
$active    = 'profile';
require __DIR__ . '/includes/header.php';

/** Prints a list of validation errors, if any. */
function show_errors(array $list): void
{
    if (!$list) {
        return;
    }
    echo '<div class="form-errors" role="alert"><ul>';
    foreach ($list as $msg) {
        echo '<li>' . e($msg) . '</li>';
    }
    echo '</ul></div>';
}
?>

<section class="page-head">
    <div class="wrap">
        <div class="row-between">
            <div>
                <h1>Account settings</h1>
                <p class="lead muted">Update your details, photo and password.</p>
            </div>
            <a class="btn btn-ghost" href="profileView.php">Back to profile</a>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="wrap">
        <div class="settings-grid">

            <div class="card card-pad">
                <h2>Your details</h2>
                <?php show_errors($errors['profile']); ?>
                <form method="post" action="profileEdit.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-grid">
                        <div class="field">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?= e($user['username']) ?>" disabled>
                        </div>
                        <div class="field">
                            <label for="name">Full name</label>
                            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="mobile">Mobile number</label>
                            <input type="text" id="mobile" name="mobile" value="<?= e($old['mobile']) ?>" inputmode="numeric" maxlength="10" required>
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
                        </div>
                        <div class="field span-2">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?= e($old['address']) ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>

            <?php if ($isFarmer): ?>
            <div class="card card-pad">
                <h2>Profile photo</h2>
                <?php show_errors($errors['photo']); ?>
                <div class="photo-row">
                    <img class="avatar avatar-lg" src="<?= e(avatar_url($user['pic'])) ?>" alt="Current profile photo">
                    <form method="post" action="profileEdit.php" enctype="multipart/form-data" style="flex:1;min-width:240px">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_pic">
                        <label class="file-drop" for="profilePic" style="margin-bottom:.75rem">
                            <i class="fa fa-camera" aria-hidden="true"></i>
                            <div>
                                <strong>Choose a photo</strong>
                                <span id="pic-name">JPG or PNG, up to 2 MB</span>
                            </div>
                            <input type="file" id="profilePic" name="profilePic" accept="image/jpeg,image/png" data-file-label="#pic-name">
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm">Upload photo</button>
                    </form>
                </div>
                <?php if ($user['pic'] !== 'profile0.png'): ?>
                    <form method="post" action="profileEdit.php" class="inline-form" data-confirm="Remove your profile photo?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove_pic">
                        <button type="submit" class="btn btn-danger btn-sm">Remove photo</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="card card-pad" id="password">
                <h2>Change password</h2>
                <?php show_errors($errors['password']); ?>
                <form method="post" action="profileEdit.php#password" data-password-match novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="field">
                        <label for="current">Current password</label>
                        <input type="password" id="current" name="current" autocomplete="current-password" required>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="new">New password</label>
                            <input type="password" id="new" name="new" minlength="8" autocomplete="new-password" data-pw-new required>
                        </div>
                        <div class="field">
                            <label for="confirm">Confirm new password</label>
                            <input type="password" id="confirm" name="confirm" minlength="8" autocomplete="new-password" data-pw-confirm required>
                        </div>
                        <p class="hint span-2" data-pw-hint>Use at least 8 characters.</p>
                    </div>
                    <button type="submit" class="btn btn-primary">Update password</button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
