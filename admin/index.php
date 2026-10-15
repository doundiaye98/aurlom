<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $product = get_product($id);
        $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute(array($id));
        flash_set('success', $product ? '« ' . $product['name'] . ' » a été supprimé.' : 'Produit supprimé.');
        redirect('admin/index.php');
    }
    if (isset($_POST['toggle_id'])) {
        $id = (int) $_POST['toggle_id'];
        db()->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute(array($id));
        flash_set('success', 'Visibilité du produit mise à jour.');
        redirect('admin/index.php');
    }
}

$filters = array(
    'q' => trim(isset($_GET['q']) ? $_GET['q'] : ''),
    'category_id' => isset($_GET['category_id']) ? $_GET['category_id'] : '',
    'is_active' => isset($_GET['is_active']) ? $_GET['is_active'] : '',
    'item_condition' => isset($_GET['item_condition']) ? $_GET['item_condition'] : '',
);
$products = get_products($filters);
$categories = get_categories();
$orderCount = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$visibleCount = (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();

$pageTitle = 'Produits';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="section-title mb-1">Produits</h1>
            <p class="text-muted mb-0">
                <?php echo count($products); ?> résultat(s) · <?php echo $visibleCount; ?> en vitrine ·
                <a href="<?php echo e(url('admin/categories.php')); ?>">Catégories</a> ·
                <a href="<?php echo e(url('admin/orders.php')); ?>">Commandes (<?php echo $orderCount; ?>)</a> ·
                <a href="<?php echo e(url('admin/reviews.php')); ?>">Notes</a> ·
                <a href="<?php echo e(url('admin/users.php')); ?>">Utilisateurs</a>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="<?php echo e(url('admin/product-form.php')); ?>"><i class="bi bi-plus-lg"></i> Nouveau produit</a>
        </div>
    </div>

    <form class="card-panel mb-4" method="get">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Recherche</label>
                <input class="form-control" id="q" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Nom ou description">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category_id">Catégorie</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">Toutes</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int) $cat['id']; ?>" <?php echo (string) $filters['category_id'] === (string) $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo e($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="item_condition">État</label>
                <select class="form-select" id="item_condition" name="item_condition">
                    <option value="">Tous</option>
                    <?php foreach (product_conditions() as $code => $label): ?>
                        <option value="<?php echo e($code); ?>" <?php echo $filters['item_condition'] === $code ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="is_active">Statut</label>
                <select class="form-select" id="is_active" name="is_active">
                    <option value="">Tous</option>
                    <option value="1" <?php echo $filters['is_active'] === '1' ? 'selected' : ''; ?>>Visible</option>
                    <option value="0" <?php echo $filters['is_active'] === '0' ? 'selected' : ''; ?>>Masqué</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">OK</button>
            </div>
        </div>
    </form>

    <?php if (!$products): ?>
        <div class="empty-state">
            <p>Aucun produit. Créez-en un ou élargissez les filtres.</p>
            <a class="btn btn-primary" href="<?php echo e(url('admin/product-form.php')); ?>">Ajouter un produit</a>
        </div>
    <?php else: ?>
        <div class="table-responsive cart-table">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th></th>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>État</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr class="<?php echo empty($product['is_active']) ? 'row-inactive' : ''; ?>">
                        <td><img class="cart-thumb" src="<?php echo e(product_image_src($product['image'])); ?>" alt=""></td>
                        <td>
                            <strong><?php echo e($product['name']); ?></strong>
                            <div class="small text-muted">#<?php echo (int) $product['id']; ?> · <?php echo e($product['slug']); ?></div>
                        </td>
                        <td><?php echo e($product['category_name'] ? $product['category_name'] : '—'); ?></td>
                        <td><?php echo e(condition_label($product['item_condition'])); ?></td>
                        <td><?php echo e(format_price($product['price'])); ?></td>
                        <td>
                            <span class="badge <?php echo (int) $product['stock'] === 0 ? 'text-bg-danger' : ((int) $product['stock'] <= 5 ? 'text-bg-warning' : 'text-bg-success'); ?>">
                                <?php echo (int) $product['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($product['is_active'])): ?>
                                <span class="badge text-bg-success">Visible</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Masqué</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(url('product.php?id=' . (int) $product['id'])); ?>">Voir</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(url('admin/product-form.php?id=' . (int) $product['id'])); ?>">Modifier</a>
                            <form method="post" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="toggle_id" value="<?php echo (int) $product['id']; ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit"><?php echo !empty($product['is_active']) ? 'Masquer' : 'Publier'; ?></button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Supprimer définitivement ce produit ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="delete_id" value="<?php echo (int) $product['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
