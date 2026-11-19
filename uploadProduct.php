<?php
require __DIR__ . '/includes/bootstrap.php';

require_role('farmer');
$fid = current_user()['id'];

// ?pid=5 edits an existing product (only the owner can); no pid adds a new one.
$editId  = is_post() ? (int) post_str('pid') : get_int('pid');
$editing = null;
if ($editId > 0) {
    $editing = db_row('SELECT * FROM fproduct WHERE pid = ? AND fid = ?', 'ii', [$editId, $fid]);
    if (!$editing) {
        flash('error', 'You can only edit your own products.');
        redirect('profileView.php');
    }
}

$errors = [];
$old = [
    'pname' => $editing ? $editing['product'] : '',
    'type'  => $editing ? $editing['pcat'] : '',
    'unit'  => $editing ? $editing['unit'] : 'kg',
    'price' => $editing ? (string) $editing['price'] : '',
    'pinfo' => $editing ? $editing['pinfo'] : '',
];

if (is_post()) {
    csrf_check();
    foreach ($old as $key => $unused) {
        $old[$key] = post_str($key);
    }

    if (str_len($old['pname']) < 2 || str_len($old['pname']) > 100) {
        $errors[] = 'Product name must be 2 to 100 characters.';
    }
    if (!in_array($old['type'], PRODUCT_CATEGORIES, true)) {
        $errors[] = 'Please choose a category.';
    }
    if (!in_array($old['unit'], PRODUCT_UNITS, true)) {
        $errors[] = 'Please choose a unit.';
    }
    if (!is_numeric($old['price']) || (float) $old['price'] <= 0 || (float) $old['price'] > 9999999) {
        $errors[] = 'Please enter a valid price greater than zero.';
    }
    if ($old['pinfo'] === '' || str_len($old['pinfo']) > 2000) {
        $errors[] = 'Please add a description (up to 2000 characters).';
    }

    // The photo is optional, but if one is sent it must be a real JPG or PNG.
    $newExt  = null;
    $hasFile = isset($_FILES['productPic']) && (int) $_FILES['productPic']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($hasFile) {
        list($newExt, $imgError) = check_uploaded_image($_FILES['productPic'], 3 * 1048576);
        if ($imgError) {
            $errors[] = $imgError;
        }
    }

    if (!$errors) {
        $imageName = $editing ? $editing['pimage'] : '';
        $saved     = null;

        if ($hasFile) {
            // A random server-side name: the user's file name is never used.
            $saved = 'p' . $fid . '_' . bin2hex(random_bytes(8)) . '.' . $newExt;
            $dest  = __DIR__ . '/productimages/' . $saved;
            if (!move_uploaded_file($_FILES['productPic']['tmp_name'], $dest)) {
                $errors[] = 'The photo could not be saved. Check that the productimages folder is writable.';
            } else {
                $imageName = $saved;
            }
        }
    }

    if (!$errors) {
        $price = number_format((float) $old['price'], 2, '.', '');
        $picStatus = $imageName !== '' ? 1 : 0;

        if ($editing) {
            db_run(
                'UPDATE fproduct SET product = ?, pcat = ?, pinfo = ?, price = ?, unit = ?, pimage = ?, picStatus = ? WHERE pid = ? AND fid = ?',
                'sssdssiii',
                [$old['pname'], $old['type'], $old['pinfo'], $price, $old['unit'], $imageName, $picStatus, $editId, $fid]
            );
            // Remove the replaced photo (only files this feature created).
            if ($saved && $editing['pimage'] !== '' && preg_match('/^p\d+_[0-9a-f]{16}\.(jpg|png)$/', $editing['pimage'])) {
                @unlink(__DIR__ . '/productimages/' . basename($editing['pimage']));
            }
            flash('success', 'Product updated.');
        } else {
            db_run(
                'INSERT INTO fproduct (fid, product, pcat, pinfo, price, unit, pimage, picStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                'isssdssi',
                [$fid, $old['pname'], $old['type'], $old['pinfo'], $price, $old['unit'], $imageName, $picStatus]
            );
            flash('success', 'Your product is now live on the Digital Bazar.');
        }
        redirect('profileView.php#products');
    }
}

$pageTitle = $editing ? 'Edit product' : 'List a product';
$active    = 'profile';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <h1><?= $editing ? 'Edit product' : 'List a product' ?></h1>
        <p class="lead muted">Good photos and clear descriptions help buyers trust your produce.</p>
    </div>
</section>

<section class="section-tight">
    <div class="narrow">
        <div class="card card-pad">
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

            <form method="post" action="uploadProduct.php<?= $editing ? '?pid=' . (int) $editId : '' ?>" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <?php if ($editing): ?><input type="hidden" name="pid" value="<?= (int) $editId ?>"><?php endif; ?>

                <div class="field">
                    <span class="label">Photo <span class="muted">(JPG or PNG, up to 3 MB)</span></span>
                    <?php if ($editing): ?>
                        <img src="<?= e(product_image($editing['pimage'])) ?>" alt="Current photo" style="width:160px;border-radius:12px;margin-bottom:.5rem">
                    <?php endif; ?>
                    <label class="file-drop" for="productPic">
                        <i class="fa fa-camera" aria-hidden="true"></i>
                        <div>
                            <strong><?= $editing ? 'Choose a new photo (optional)' : 'Choose a photo' ?></strong>
                            <span id="file-name">No file chosen</span>
                        </div>
                        <input type="file" id="productPic" name="productPic" accept="image/jpeg,image/png" data-file-label="#file-name">
                    </label>
                </div>

                <div class="form-grid">
                    <div class="field span-2">
                        <label for="pname">Product name</label>
                        <input type="text" id="pname" name="pname" value="<?= e($old['pname']) ?>" maxlength="100" required>
                    </div>
                    <div class="field">
                        <label for="type">Category</label>
                        <select id="type" name="type" required>
                            <option value="">Choose a category</option>
                            <?php foreach (PRODUCT_CATEGORIES as $c): ?>
                                <option value="<?= e($c) ?>" <?= $old['type'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="unit">Sold per</label>
                        <select id="unit" name="unit" required>
                            <?php foreach (PRODUCT_UNITS as $u): ?>
                                <option value="<?= e($u) ?>" <?= $old['unit'] === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field span-2">
                        <label for="price">Price (Rs.)</label>
                        <input type="number" id="price" name="price" value="<?= e($old['price']) ?>" min="0.01" step="0.01" required>
                    </div>
                    <div class="field span-2">
                        <label for="pinfo">Description</label>
                        <textarea id="pinfo" name="pinfo" rows="6" maxlength="2000" required><?= e($old['pinfo']) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><?= $editing ? 'Save changes' : 'Publish product' ?></button>
                <a class="btn btn-ghost btn-lg" href="profileView.php#products">Cancel</a>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
