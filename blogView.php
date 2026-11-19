<?php
require __DIR__ . '/includes/bootstrap.php';

// Reading is public. Liking and commenting need a login.
// Every action ends with a redirect, so refreshing the page never re-sends a comment.
if (is_post()) {
    csrf_check();
    require_login();
    $user   = current_user();
    $action = post_str('action');
    $blogId = (int) post_str('blog_id');

    if (!db_val('SELECT COUNT(*) FROM blogdata WHERE blogId = ?', 'i', [$blogId])) {
        flash('error', 'That post no longer exists.');
        redirect('blogView.php');
    }

    if ($action === 'like') {
        $liked = db_val(
            'SELECT COUNT(*) FROM likedata WHERE blogId = ? AND userRole = ? AND blogUserId = ?',
            'isi',
            [$blogId, $user['role'], $user['id']]
        );
        if ($liked) {
            db_run('DELETE FROM likedata WHERE blogId = ? AND userRole = ? AND blogUserId = ?', 'isi', [$blogId, $user['role'], $user['id']]);
            db_run('UPDATE blogdata SET likes = GREATEST(likes - 1, 0) WHERE blogId = ?', 'i', [$blogId]);
        } else {
            db_run('INSERT INTO likedata (blogId, userRole, blogUserId) VALUES (?, ?, ?)', 'isi', [$blogId, $user['role'], $user['id']]);
            db_run('UPDATE blogdata SET likes = likes + 1 WHERE blogId = ?', 'i', [$blogId]);
        }
    } elseif ($action === 'comment') {
        $comment = post_str('comment');
        if ($comment === '' || str_len($comment) > 500) {
            flash('error', 'Comments must be between 1 and 500 characters.');
        } else {
            db_run(
                'INSERT INTO blogfeedback (blogId, comment, commentUser, commentPic) VALUES (?, ?, ?, ?)',
                'isss',
                [$blogId, $comment, $user['username'], $user['pic']]
            );
        }
    }
    redirect('blogView.php#post-' . $blogId);
}

$posts = db_all('SELECT blogId, blogUser, blogTitle, blogContent, blogTime, likes FROM blogdata ORDER BY blogId DESC LIMIT 50');

$comments = [];
$likedIds = [];
if ($posts) {
    foreach (db_all('SELECT blogId, comment, commentUser, commentPic, commentTime FROM blogfeedback ORDER BY commentId ASC') as $row) {
        $comments[(int) $row['blogId']][] = $row;
    }
    if (is_logged_in()) {
        $me = current_user();
        foreach (db_all('SELECT blogId FROM likedata WHERE userRole = ? AND blogUserId = ?', 'si', [$me['role'], $me['id']]) as $row) {
            $likedIds[(int) $row['blogId']] = true;
        }
    }
}

$pageTitle = 'Blog';
$active    = 'blog';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <div class="row-between">
            <div>
                <p class="eyebrow">KISAN Blog</p>
                <h1>Stories from the field</h1>
                <p class="lead muted">Tips, seasonal advice and news from farmers and buyers.</p>
            </div>
            <a class="btn btn-primary" href="blogWrite.php"><i class="fa fa-pencil" aria-hidden="true"></i> Write a post</a>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="narrow">
        <?php if ($posts): ?>
            <?php foreach ($posts as $post): ?>
                <?php
                    $id       = (int) $post['blogId'];
                    $thread   = $comments[$id] ?? [];
                    $isLiked  = isset($likedIds[$id]);
                ?>
                <article class="card post" id="post-<?= $id ?>">
                    <header class="post-head">
                        <h2><?= e($post['blogTitle']) ?></h2>
                        <div class="post-meta">
                            <i class="fa fa-user" aria-hidden="true"></i>
                            <span>by <strong><?= e($post['blogUser']) ?></strong></span>
                            <span>&middot;</span>
                            <time><?= e(when($post['blogTime'])) ?></time>
                        </div>
                    </header>

                    <div class="post-body"><?= nl($post['blogContent']) ?></div>

                    <footer class="post-actions">
                        <form method="post" action="blogView.php" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="like">
                            <input type="hidden" name="blog_id" value="<?= $id ?>">
                            <button type="submit" class="like-btn <?= $isLiked ? 'is-liked' : '' ?>" aria-pressed="<?= $isLiked ? 'true' : 'false' ?>">
                                <i class="fa <?= $isLiked ? 'fa-thumbs-up' : 'fa-thumbs-o-up' ?>" aria-hidden="true"></i>
                                <?= $isLiked ? 'Liked' : 'Like' ?> &middot; <?= (int) $post['likes'] ?>
                            </button>
                        </form>
                        <span class="muted small"><i class="fa fa-comment-o" aria-hidden="true"></i> <?= count($thread) ?> comment<?= count($thread) === 1 ? '' : 's' ?></span>
                    </footer>

                    <div class="comments">
                        <?php foreach ($thread as $cm): ?>
                            <div class="comment">
                                <img class="avatar avatar-sm" src="<?= e(avatar_url($cm['commentPic'])) ?>" alt="">
                                <div class="comment-bubble">
                                    <div class="comment-meta"><strong><?= e($cm['commentUser']) ?></strong> <?= e(when($cm['commentTime'])) ?></div>
                                    <p><?= nl($cm['comment']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (is_logged_in()): ?>
                            <form method="post" action="blogView.php" class="comment-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="comment">
                                <input type="hidden" name="blog_id" value="<?= $id ?>">
                                <textarea name="comment" maxlength="500" placeholder="Write a comment..." aria-label="Write a comment" required></textarea>
                                <button type="submit" class="btn btn-primary btn-sm">Post</button>
                            </form>
                        <?php else: ?>
                            <p class="muted small" style="margin:0"><a href="login.php">Log in</a> to like or comment.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <i class="fa fa-comments-o" aria-hidden="true"></i>
                <h3>No posts yet</h3>
                <p>Be the first to share something with the community.</p>
                <a class="btn btn-primary" href="blogWrite.php">Write a post</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
