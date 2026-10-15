<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pdo = db();
$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$visibleCount = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$outOfStock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 0')->fetchColumn();
$lowStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= ?');
$lowStmt->execute(array(LOW_STOCK_THRESHOLD));
$lowStock = (int) $lowStmt->fetchColumn();

$clientCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$newOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'nouvelle'")->fetchColumn();
$reviewCount = (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();

$lowProducts = get_products(array('low_stock' => 1));
$outProducts = get_products(array('stock_level' => 'rupture'));
$recentOrders = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 5')->fetchAll();
$recentClients = $pdo->query("SELECT id, name, email, created_at FROM users WHERE role = 'user' ORDER BY id DESC LIMIT 5")->fetchAll();

$pageTitle = 'Back-office';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>

    <div class="mb-4">
        <h1 class="section-title mb-1">Back-office</h1>
        <p class="text-muted mb-0">Gestion du stock et de la relation clients, réservée à l’administrateur.</p>
    </div>

    <div class="admin-kpis mb-4">
        <a class="admin-kpi" href="<?php echo e(url('admin/stock.php?stock_level=rupture')); ?>">
            <span>Rupture de stock</span>
            <strong><?php echo $outOfStock; ?></strong>
        </a>
        <a class="admin-kpi" href="<?php echo e(url('admin/stock.php?stock_level=faible')); ?>">
            <span>Stock faible</span>
            <strong><?php echo $lowStock; ?></strong>
        </a>
        <a class="admin-kpi" href="<?php echo e(url('admin/customers.php')); ?>">
            <span>Clients</span>
            <strong><?php echo $clientCount; ?></strong>
        </a>
        <a class="admin-kpi" href="<?php echo e(url('admin/orders.php')); ?>">
            <span>Commandes nouvelles</span>
            <strong><?php echo $newOrders; ?></strong>
        </a>
        <a class="admin-kpi" href="<?php echo e(url('admin/index.php')); ?>">
            <span>Produits en vitrine</span>
            <strong><?php echo $visibleCount; ?> / <?php echo $productCount; ?></strong>
        </a>
        <a class="admin-kpi" href="<?php echo e(url('admin/reviews.php')); ?>">
            <span>Notes &amp; avis</span>
            <strong><?php echo $reviewCount; ?></strong>
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Stock à surveiller</h2>
                    <a href="<?php echo e(url('admin/stock.php')); ?>">Tout le stock</a>
                </div>
                <?php $watch = array_merge($outProducts, $lowProducts); ?>
                <?php if (!$watch): ?>
                    <p class="text-muted mb-0">Tous les produits ont un stock suffisant.</p>
                <?php else: ?>
                    <ul class="admin-list">
                        <?php foreach (array_slice($watch, 0, 8) as $p): ?>
                            <li>
                                <div>
                                    <strong><?php echo e($p['name']); ?></strong>
                                    <div class="small text-muted"><?php echo e(isset($p['category_name']) ? $p['category_name'] : ''); ?></div>
                                </div>
                                <?php echo stock_badge_html($p['stock']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Dernières commandes</h2>
                    <a href="<?php echo e(url('admin/orders.php')); ?>">Toutes</a>
                </div>
                <?php if (!$recentOrders): ?>
                    <p class="text-muted mb-0">Aucune commande pour le moment.</p>
                <?php else: ?>
                    <ul class="admin-list">
                        <?php foreach ($recentOrders as $order): ?>
                            <li>
                                <div>
                                    <strong>n°<?php echo (int) $order['id']; ?> · <?php echo e($order['customer_name']); ?></strong>
                                    <div class="small text-muted"><?php echo e($order['email']); ?> · <?php echo e($order['status']); ?></div>
                                </div>
                                <span><?php echo e(format_price($order['total'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12">
            <div class="card-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Nouveaux clients</h2>
                    <a href="<?php echo e(url('admin/customers.php')); ?>">Relation clients</a>
                </div>
                <?php if (!$recentClients): ?>
                    <p class="text-muted mb-0">Aucun compte client.</p>
                <?php else: ?>
                    <ul class="admin-list">
                        <?php foreach ($recentClients as $client): ?>
                            <li>
                                <div>
                                    <strong><?php echo e($client['name']); ?></strong>
                                    <div class="small text-muted"><?php echo e($client['email']); ?></div>
                                </div>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo e(url('admin/customers.php?id=' . (int) $client['id'])); ?>">Fiche</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
