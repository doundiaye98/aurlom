<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    csrf_check();
    $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute(array($_POST['status'], (int) $_POST['order_id']));
    flash_set('success', 'Statut mis à jour.');
    $backUser = isset($_POST['user_id']) ? (int) $_POST['user_id'] : $userId;
    redirect($backUser ? 'admin/orders.php?user_id=' . $backUser : 'admin/orders.php');
}

if ($userId) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute(array($userId));
    $orders = $stmt->fetchAll();
    $clientStmt = db()->prepare('SELECT name, email FROM users WHERE id = ?');
    $clientStmt->execute(array($userId));
    $filterClient = $clientStmt->fetch() ?: null;
} else {
    $orders = db()->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();
    $filterClient = null;
}

$pageTitle = 'Commandes';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="section-title mb-1">Commandes</h1>
            <?php if ($filterClient): ?>
                <p class="text-muted mb-0">
                    Client : <?php echo e($filterClient['name']); ?>
                    · <a href="<?php echo e(url('admin/customers.php?id=' . $userId)); ?>">Fiche client</a>
                    · <a href="<?php echo e(url('admin/orders.php')); ?>">Toutes les commandes</a>
                </p>
            <?php else: ?>
                <p class="text-muted mb-0"><?php echo count($orders); ?> commande(s)</p>
            <?php endif; ?>
        </div>
        <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/customers.php')); ?>">Relation clients</a>
    </div>
    <?php if (!$orders): ?>
        <div class="empty-state"><p>Aucune commande.</p></div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $itemStmt->execute(array($order['id']));
            $items = $itemStmt->fetchAll();
            ?>
            <div class="card-panel mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <strong>Commande n°<?php echo (int) $order['id']; ?></strong>
                    <span class="text-muted"><?php echo e($order['created_at']); ?></span>
                </div>
                <p class="mb-2">
                    <?php echo e($order['customer_name']); ?> · <?php echo e($order['email']); ?> · <?php echo e($order['phone']); ?><br>
                    <?php echo e($order['address']); ?>, <?php echo e($order['city']); ?>
                    <?php if (!empty($order['user_id'])): ?>
                        · <a href="<?php echo e(url('admin/customers.php?id=' . (int) $order['user_id'])); ?>">Fiche client</a>
                    <?php endif; ?>
                </p>
                <ul class="small">
                    <?php foreach ($items as $item): ?>
                        <li><?php echo e($item['product_name']); ?> × <?php echo (int) $item['quantity']; ?> — <?php echo e(format_price($item['price'] * $item['quantity'])); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong>Total <?php echo e(format_price($order['total'])); ?></strong>
                    <form method="post" class="d-flex gap-2">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                        <?php if ($userId): ?>
                            <input type="hidden" name="user_id" value="<?php echo (int) $userId; ?>">
                        <?php endif; ?>
                        <select class="form-select form-select-sm" name="status">
                            <?php foreach (array('nouvelle', 'expédiée', 'livrée', 'annulée') as $status): ?>
                                <option value="<?php echo e($status); ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" type="submit">OK</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
