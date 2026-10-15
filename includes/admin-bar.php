<?php
$adminPage = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
if ($adminPage === 'product-form.php') {
    $adminPage = 'index.php';
}
$adminLinks = array(
    array('dashboard.php', 'Tableau de bord', 'bi-grid'),
    array('stock.php', 'Stock', 'bi-boxes'),
    array('customers.php', 'Clients', 'bi-people'),
    array('orders.php', 'Commandes', 'bi-bag-check'),
    array('index.php', 'Produits', 'bi-laptop'),
    array('categories.php', 'Catégories', 'bi-tags'),
    array('reviews.php', 'Notes', 'bi-star'),
    array('users.php', 'Comptes', 'bi-shield-lock'),
);
?>
<nav class="admin-bar" aria-label="Back-office">
    <?php foreach ($adminLinks as $link): ?>
        <a class="<?php echo $adminPage === $link[0] ? 'is-active' : ''; ?>" href="<?php echo e(url('admin/' . $link[0])); ?>">
            <i class="bi <?php echo e($link[2]); ?>"></i>
            <?php echo e($link[1]); ?>
        </a>
    <?php endforeach; ?>
</nav>
