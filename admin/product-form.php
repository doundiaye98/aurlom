<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$existing = $id ? get_product($id) : null;
if ($id && !$existing) {
    flash_set('error', 'Produit introuvable.');
    redirect('admin/index.php');
}

$product = $existing ? $existing : array(
    'name' => '',
    'description' => '',
    'price' => '',
    'image' => '',
    'stock' => 10,
    'category_id' => '',
    'item_condition' => 'neuf',
    'is_featured' => 0,
    'is_active' => 1,
);
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $product['name'] = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $product['description'] = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $product['price'] = str_replace(',', '.', trim(isset($_POST['price']) ? $_POST['price'] : ''));
    $product['image'] = trim(isset($_POST['image']) ? $_POST['image'] : '');
    $product['stock'] = (int) (isset($_POST['stock']) ? $_POST['stock'] : 0);
    $product['category_id'] = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
    $product['item_condition'] = isset($_POST['item_condition']) ? $_POST['item_condition'] : 'neuf';
    if (!isset(product_conditions()[$product['item_condition']])) {
        $product['item_condition'] = 'neuf';
    }
    $product['is_featured'] = empty($_POST['is_featured']) ? 0 : 1;
    $product['is_active'] = empty($_POST['is_active']) ? 0 : 1;

    if ($product['name'] === '') {
        $errors[] = 'Le nom est obligatoire.';
    }
    if (mb_strlen($product['name']) > 150) {
        $errors[] = 'Le nom est trop long (150 caractères max).';
    }
    if ($product['description'] === '') {
        $errors[] = 'La description est obligatoire.';
    }
    if (!is_numeric($product['price']) || (float) $product['price'] < 0) {
        $errors[] = 'Prix invalide.';
    }
    if ($product['stock'] < 0) {
        $errors[] = 'Stock invalide.';
    }

    try {
        $product['image'] = handle_image_upload($product['image']);
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    if ($product['image'] === '') {
        $errors[] = 'Ajoutez une image (fichier ou URL).';
    }

    if (!$errors) {
        $slug = unique_product_slug($product['name'], $id);
        $catId = $product['category_id'] ? $product['category_id'] : null;
        if ($id) {
            $stmt = db()->prepare(
                'UPDATE products
                 SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, image = ?, stock = ?, item_condition = ?, is_featured = ?, is_active = ?
                 WHERE id = ?'
            );
            $stmt->execute(array(
                $catId,
                $product['name'],
                $slug,
                $product['description'],
                $product['price'],
                $product['image'],
                $product['stock'],
                $product['item_condition'],
                $product['is_featured'],
                $product['is_active'],
                $id,
            ));
            flash_set('success', 'Produit mis à jour.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO products (category_id, name, slug, description, price, image, stock, item_condition, is_featured, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute(array(
                $catId,
                $product['name'],
                $slug,
                $product['description'],
                $product['price'],
                $product['image'],
                $product['stock'],
                $product['item_condition'],
                $product['is_featured'],
                $product['is_active'],
            ));
            flash_set('success', 'Produit créé.');
        }
        redirect('admin/index.php');
    }
}

$categories = get_categories();
$pageTitle = $id ? 'Modifier le produit' : 'Nouveau produit';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>
    <a class="back-link" href="<?php echo e(url('admin/index.php')); ?>"><i class="bi bi-arrow-left"></i> Produits</a>
    <h1 class="section-title mt-3 mb-4"><?php echo $id ? 'Modifier' : 'Nouveau produit'; ?></h1>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" class="card-panel" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="mb-3">
                    <label class="form-label" for="name">Nom</label>
                    <input class="form-control" id="name" name="name" required maxlength="150" value="<?php echo e($product['name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="category_id">Catégorie</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Sans catégorie</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat['id']; ?>" <?php echo (string) $product['category_id'] === (string) $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo e($product['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="item_condition">État</label>
                    <select class="form-select" id="item_condition" name="item_condition">
                        <?php foreach (product_conditions() as $code => $label): ?>
                            <option value="<?php echo e($code); ?>" <?php echo $product['item_condition'] === $code ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="price">Prix (€)</label>
                        <input class="form-control" id="price" name="price" required value="<?php echo e($product['price']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="stock">Stock boutique</label>
                        <input class="form-control" type="number" id="stock" name="stock" min="0" required value="<?php echo e($product['stock']); ?>">
                    </div>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo !empty($product['is_featured']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_featured">Mettre en vitrine</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo !empty($product['is_active']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Visible dans le catalogue</label>
                </div>
            </div>
            <div class="col-lg-5">
                <label class="form-label">Image</label>
                <div class="image-preview mb-3">
                    <img id="image-preview" src="<?php echo $product['image'] ? e(product_image_src($product['image'])) : ''; ?>" alt="" <?php echo $product['image'] ? '' : 'hidden'; ?>>
                    <span id="image-preview-empty" class="text-muted" <?php echo $product['image'] ? 'hidden' : ''; ?>>Aperçu</span>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="image_file">Fichier (jpg, png, webp — 2 Mo max)</label>
                    <input class="form-control" type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="image">Ou URL</label>
                    <input class="form-control" id="image" name="image" value="<?php echo e($product['image']); ?>" placeholder="https://…">
                </div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/index.php')); ?>">Annuler</a>
    </form>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
