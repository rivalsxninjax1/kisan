<?php
require __DIR__ . '/includes/bootstrap.php';

require_login();
$user = current_user();

$errors = [];
$title  = '';
$content = '';

if (is_post()) {
    csrf_check();
    $title   = post_str('blogTitle');
    $content = post_str('blogContent');

    if (str_len($title) < 3 || str_len($title) > 150) {
        $errors[] = 'The title must be between 3 and 150 characters.';
    }
    if (str_len($content) < 10 || str_len($content) > 5000) {
        $errors[] = 'The post must be between 10 and 5000 characters.';
    }

    if (!$errors) {
        db_run(
            'INSERT INTO blogdata (blogUser, blogTitle, blogContent) VALUES (?, ?, ?)',
            'sss',
            [$user['username'], $title, $content]
        );
        flash('success', 'Your post is live.');
        redirect('blogView.php#post-' . db_insert_id());
    }
}

$pageTitle = 'Write a post';
$active    = 'blog';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <div class="row-between">
            <div>
                <h1>Write a post</h1>
                <p class="lead muted">Share a tip, a harvest update or a story with the community.</p>
            </div>
            <a class="btn btn-ghost" href="blogView.php">View posts</a>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="narrow">
        <div class="card card-pad">
            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <ul>
                        <?php foreach ($errors as $msg): ?>
                            <li><?= e($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="blogWrite.php" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="blogTitle">Title</label>
                    <input type="text" id="blogTitle" name="blogTitle" value="<?= e($title) ?>" maxlength="150" required>
                </div>
                <div class="field">
                    <label for="blogContent">Your post</label>
                    <textarea id="blogContent" name="blogContent" rows="12" maxlength="5000" required><?= e($content) ?></textarea>
                    <p class="hint">Plain text. Leave a blank line between paragraphs.</p>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Publish</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
