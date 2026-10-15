<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
$stmt->execute(array($user['id']));
$orders = $stmt->fetchAll();

$pageTitle = 'Mon compte';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h1 class="section-title mb-2">Mon compte</h1>
    <p class="text-muted mb-4">
        <?php echo e($user['name']); ?> · <?php echo e($user['email']); ?>
        · Rôle : <strong><?php echo $user['role'] === 'admin' ? 'administrateur' : 'utilisateur'; ?></strong>
    </p>

    <h2 class="h5 mb-3">Mes commandes</h2>
    <?php if (!$orders): ?>
        <div class="empty-state">
            <p>Aucune commande pour le moment.</p>
            <a class="btn btn-primary" href="<?php echo e(url('index.php')); ?>">Voir la boutique</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $itemStmt->execute(array($order['id']));
            $items = $itemStmt->fetchAll();
            ?>
            <div class="card-panel mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <strong>Commande n°<?php echo (int) $order['id']; ?></strong>
                    <span class="badge text-bg-light"><?php echo e($order['status']); ?></span>
                </div>
                <ul class="small mb-2">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <?php echo e($item['product_name']); ?> × <?php echo (int) $item['quantity']; ?>
                            <?php if (!empty($item['product_id'])): ?>
                                · <a href="<?php echo e(url('product.php?id=' . (int) $item['product_id'])); ?>">Noter</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between">
                    <span class="text-muted"><?php echo e($order['created_at']); ?></span>
                    <strong><?php echo e(format_price($order['total'])); ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
