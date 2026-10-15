<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $userId = (int) (isset($_POST['user_id']) ? $_POST['user_id'] : 0);
    $note = trim(isset($_POST['admin_note']) ? $_POST['admin_note'] : '');
    if ($userId) {
        db()->prepare('UPDATE users SET admin_note = ? WHERE id = ? AND role = ?')->execute(array($note, $userId, 'user'));
        flash_set('success', 'Note client enregistrée.');
        redirect('admin/customers.php?id=' . $userId);
    }
    redirect('admin/customers.php');
}

$customer = null;
$orders = array();
$lastContact = null;
if ($id) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
    $stmt->execute(array($id));
    $customer = $stmt->fetch() ?: null;
    if (!$customer) {
        flash_set('error', 'Client introuvable.');
        redirect('admin/customers.php');
    }
    $orderStmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
    $orderStmt->execute(array($id));
    $orders = $orderStmt->fetchAll();
    $lastContact = $orders ? $orders[0] : null;
}

$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$clients = array();
if (!$customer) {
    $sql = "SELECT u.id, u.name, u.email, u.created_at, u.admin_note,
                   COUNT(o.id) AS order_count,
                   COALESCE(SUM(o.total), 0) AS spent,
                   MAX(o.created_at) AS last_order_at
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id
            WHERE u.role = 'user'";
    $params = array();
    if ($q !== '') {
        $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' GROUP BY u.id, u.name, u.email, u.created_at, u.admin_note ORDER BY last_order_at DESC, u.name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
}

$pageTitle = $customer ? $customer['name'] : 'Relation clients';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>

    <?php if ($customer): ?>
        <a class="back-link" href="<?php echo e(url('admin/customers.php')); ?>"><i class="bi bi-arrow-left"></i> Tous les clients</a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3 mb-4">
            <div>
                <h1 class="section-title mb-1"><?php echo e($customer['name']); ?></h1>
                <p class="text-muted mb-0">
                    <a href="mailto:<?php echo e($customer['email']); ?>"><?php echo e($customer['email']); ?></a>
                    · inscrit le <?php echo e($customer['created_at']); ?>
                </p>
            </div>
            <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/orders.php?user_id=' . (int) $customer['id'])); ?>">Commandes du client</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card-panel mb-4">
                    <h2 class="h5 mb-3">Coordonnées</h2>
                    <?php if ($lastContact): ?>
                        <p class="mb-1"><strong><?php echo e($lastContact['customer_name']); ?></strong></p>
                        <p class="mb-1"><?php echo e($lastContact['email']); ?></p>
                        <p class="mb-1"><?php echo e($lastContact['phone']); ?></p>
                        <p class="mb-0"><?php echo e($lastContact['address']); ?>, <?php echo e($lastContact['city']); ?></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">Pas encore de commande : seules l’adresse e-mail du compte est connue.</p>
                    <?php endif; ?>
                </div>
                <form method="post" class="card-panel">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo (int) $customer['id']; ?>">
                    <h2 class="h5 mb-3">Suivi interne</h2>
                    <label class="form-label" for="admin_note">Note administrateur</label>
                    <textarea class="form-control mb-3" id="admin_note" name="admin_note" rows="5" placeholder="Rappel, SAV, préférence…"><?php echo e(isset($customer['admin_note']) ? $customer['admin_note'] : ''); ?></textarea>
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                </form>
            </div>
            <div class="col-lg-7">
                <div class="card-panel">
                    <h2 class="h5 mb-3">Historique des commandes</h2>
                    <?php if (!$orders): ?>
                        <p class="text-muted mb-0">Aucune commande.</p>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
                            $itemStmt->execute(array($order['id']));
                            $items = $itemStmt->fetchAll();
                            ?>
                            <div class="crm-order">
                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <strong>Commande n°<?php echo (int) $order['id']; ?></strong>
                                    <span class="text-muted"><?php echo e($order['created_at']); ?></span>
                                </div>
                                <ul class="small mb-2">
                                    <?php foreach ($items as $item): ?>
                                        <li><?php echo e($item['product_name']); ?> × <?php echo (int) $item['quantity']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="d-flex justify-content-between">
                                    <span><?php echo e($order['status']); ?></span>
                                    <strong><?php echo e(format_price($order['total'])); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="section-title mb-1">Relation clients</h1>
                <p class="text-muted mb-0">Comptes clients, historique d’achat et notes de suivi.</p>
            </div>
        </div>

        <form class="card-panel mb-4" method="get">
            <div class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label" for="q">Rechercher un client</label>
                    <input class="form-control" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Nom ou e-mail">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">Rechercher</button>
                </div>
            </div>
        </form>

        <div class="table-responsive cart-table">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Commandes</th>
                        <th>Dépensé</th>
                        <th>Dernière commande</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($c['name']); ?></strong>
                            <div class="small text-muted"><?php echo e($c['email']); ?></div>
                            <?php if (!empty($c['admin_note'])): ?>
                                <div class="small"><?php echo e(excerpt($c['admin_note'], 80)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int) $c['order_count']; ?></td>
                        <td><?php echo e(format_price($c['spent'])); ?></td>
                        <td><?php echo $c['last_order_at'] ? e($c['last_order_at']) : '—'; ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo e(url('admin/customers.php?id=' . (int) $c['id'])); ?>">Fiche</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$clients): ?>
                    <tr><td colspan="5" class="text-muted">Aucun client.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
