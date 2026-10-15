<?php
require_once __DIR__ . '/includes/init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute(array($id));
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'Commande introuvable.');
    redirect('index.php');
}

if (!empty($order['user_id']) && !is_admin()) {
    $u = current_user();
    if (!$u || (int) $u['id'] !== (int) $order['user_id']) {
        flash_set('error', 'Cette commande ne vous appartient pas.');
        redirect(is_logged_in() ? 'account.php' : 'login.php');
    }
}

$itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemStmt->execute(array($id));
$items = $itemStmt->fetchAll();

$pageTitle = 'Commande confirmée';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="confirm-box">
        <p class="eyebrow">Merci</p>
        <h1>Commande n°<?php echo (int) $order['id']; ?></h1>
        <p class="lead">Elle est enregistrée. Nous vous contactons pour la livraison.</p>
        <ul class="list-unstyled mb-4">
            <li><strong><?php echo e($order['customer_name']); ?></strong></li>
            <li><?php echo e($order['address']); ?>, <?php echo e($order['city']); ?></li>
            <li><?php echo e($order['email']); ?> · <?php echo e($order['phone']); ?></li>
        </ul>
        <div class="mb-4">
            <?php foreach ($items as $item): ?>
                <div class="d-flex justify-content-between">
                    <span><?php echo e($item['product_name']); ?> × <?php echo (int) $item['quantity']; ?></span>
                    <span><?php echo e(format_price($item['price'] * $item['quantity'])); ?></span>
                </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between">
                <strong>Total</strong>
                <strong><?php echo e(format_price($order['total'])); ?></strong>
            </div>
        </div>
        <a class="btn btn-primary" href="<?php echo e(url('index.php')); ?>">Retour à la boutique</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
