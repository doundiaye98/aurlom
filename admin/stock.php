<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) (isset($_POST['product_id']) ? $_POST['product_id'] : 0);
    $stock = max(0, (int) (isset($_POST['stock']) ? $_POST['stock'] : 0));
    if ($id) {
        db()->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute(array($stock, $id));
        flash_set('success', 'Stock mis à jour.');
    }
    $qs = isset($_POST['return_qs']) ? (string) $_POST['return_qs'] : '';
    redirect('admin/stock.php' . ($qs !== '' ? '?' . ltrim($qs, '?') : ''));
}

$filters = array(
    'q' => trim(isset($_GET['q']) ? $_GET['q'] : ''),
    'category_id' => isset($_GET['category_id']) ? $_GET['category_id'] : '',
    'stock_level' => isset($_GET['stock_level']) ? $_GET['stock_level'] : '',
);
$products = get_products($filters);
$categories = get_categories();
$returnQs = http_build_query(array_filter($filters, function ($v) {
    return $v !== '' && $v !== null;
}));

$pageTitle = 'Stock';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="section-title mb-1">Gestion du stock</h1>
            <p class="text-muted mb-0">Ajustez les quantités. Seuil d’alerte : <?php echo (int) LOW_STOCK_THRESHOLD; ?> pièces.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/index.php')); ?>">Fiches produits</a>
    </div>

    <form class="card-panel mb-4" method="get">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="q">Recherche</label>
                <input class="form-control" id="q" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Nom du produit">
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
                <label class="form-label" for="stock_level">Niveau</label>
                <select class="form-select" id="stock_level" name="stock_level">
                    <option value="">Tous</option>
                    <option value="rupture" <?php echo $filters['stock_level'] === 'rupture' ? 'selected' : ''; ?>>Rupture</option>
                    <option value="faible" <?php echo $filters['stock_level'] === 'faible' ? 'selected' : ''; ?>>Faible</option>
                    <option value="ok" <?php echo $filters['stock_level'] === 'ok' ? 'selected' : ''; ?>>OK</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Filtrer</button>
            </div>
        </div>
    </form>

    <div class="table-responsive cart-table">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>État</th>
                    <th>Niveau</th>
                    <th>Quantité</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <strong><?php echo e($p['name']); ?></strong>
                        <?php if (empty($p['is_active'])): ?>
                            <span class="badge text-bg-light">masqué</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e(isset($p['category_name']) ? $p['category_name'] : '—'); ?></td>
                    <td><?php echo e(condition_label($p['item_condition'])); ?></td>
                    <td><?php echo stock_badge_html($p['stock']); ?></td>
                    <td>
                        <form method="post" class="stock-inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                            <input type="hidden" name="return_qs" value="<?php echo e($returnQs); ?>">
                            <input class="form-control form-control-sm" type="number" min="0" name="stock" value="<?php echo (int) $p['stock']; ?>">
                            <button class="btn btn-sm btn-outline-primary" type="submit">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
                <tr><td colspan="5" class="text-muted">Aucun produit pour ces filtres.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
