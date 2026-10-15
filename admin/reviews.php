<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($id && $action === 'toggle') {
        db()->prepare('UPDATE reviews SET is_published = 1 - is_published WHERE id = ?')->execute(array($id));
        flash_set('success', 'Visibilité de l’avis mise à jour.');
    } elseif ($id && $action === 'delete') {
        db()->prepare('DELETE FROM reviews WHERE id = ?')->execute(array($id));
        flash_set('success', 'Avis supprimé.');
    }
    redirect('admin/reviews.php');
}

$reviews = db()->query(
    'SELECT r.*, u.name AS user_name, u.email AS user_email, p.name AS product_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     JOIN products p ON p.id = r.product_id
     ORDER BY r.id DESC'
)->fetchAll();

$pageTitle = 'Notes';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="section-title mb-0">Notes &amp; avis</h1>
        <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/index.php')); ?>">Produits</a>
    </div>
    <?php if (!$reviews): ?>
        <div class="empty-state"><p>Aucun avis pour le moment.</p></div>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div class="card-panel mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <div>
                        <strong><?php echo e($review['product_name']); ?></strong>
                        <div class="small text-muted"><?php echo e($review['user_name']); ?> · <?php echo e($review['user_email']); ?></div>
                    </div>
                    <div>
                        <?php echo stars_html($review['rating']); ?>
                        <?php if (!empty($review['is_published'])): ?>
                            <span class="badge text-bg-success">Publié</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Masqué</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p><?php echo nl2br(e($review['comment'])); ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(url('product.php?id=' . (int) $review['product_id'])); ?>">Fiche</a>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int) $review['id']; ?>">
                        <input type="hidden" name="action" value="toggle">
                        <button class="btn btn-sm btn-outline-primary" type="submit"><?php echo !empty($review['is_published']) ? 'Masquer' : 'Publier'; ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('Supprimer cet avis ?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int) $review['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
