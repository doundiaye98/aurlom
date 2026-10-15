<?php
if (!isset($pageTitle)) {
    $pageTitle = SHOP_NAME;
}
$flash = flash_get();
$cartCount = cart_count();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title><?php echo e($pageTitle); ?> — <?php echo e(SHOP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>?v=9">
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top navbar-aurloum">
    <div class="container nav-shell">
        <a class="navbar-brand" href="<?php echo e(url('index.php')); ?>">
            <span class="brand-mark">A</span>
            <span class="brand-text">
                <?php echo e(SHOP_NAME); ?>
                <span class="brand-sub">Informatique</span>
            </span>
        </a>
        <div class="nav-tools">
            <a class="btn btn-cart d-lg-none" href="<?php echo e(url('cart.php')); ?>" aria-label="Panier">
                <i class="bi bi-bag"></i>
                <span class="badge cart-badge" id="cart-count-mobile"><?php echo (int) $cartCount; ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(url('index.php#vitrine')); ?>">Vitrine</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(url('index.php#produits')); ?>">Catalogue</a>
                </li>
                <li class="nav-item nav-search-item">
                    <form class="nav-search" method="get" action="<?php echo e(url('index.php')); ?>#produits">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" placeholder="Dell, SSD, écran…" value="<?php echo e(isset($_GET['q']) ? $_GET['q'] : ''); ?>" aria-label="Rechercher">
                    </form>
                </li>
                <?php if (is_admin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Gestion</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/dashboard.php')); ?>">Tableau de bord</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/stock.php')); ?>">Gestion du stock</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/customers.php')); ?>">Relation clients</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/index.php')); ?>">Produits</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/categories.php')); ?>">Catégories</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/orders.php')); ?>">Commandes</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/reviews.php')); ?>">Notes &amp; avis</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('admin/users.php')); ?>">Comptes &amp; rôles</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (is_logged_in()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <?php echo e(current_user()['name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo e(url('account.php')); ?>">Mon compte</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo e(url('logout.php')); ?>">Déconnexion</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(url('login.php')); ?>">Connexion</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-ghost" href="<?php echo e(url('register.php')); ?>">Créer un compte</a>
                </li>
                <?php endif; ?>
                <li class="nav-item d-none d-lg-block">
                    <a class="btn btn-cart" href="<?php echo e(url('cart.php')); ?>">
                        <i class="bi bi-bag"></i>
                        Panier
                        <span class="badge cart-badge" id="cart-count"><?php echo (int) $cartCount; ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo e($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
