<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Panier';
$items = cart_items();
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h1 class="section-title mb-4">Votre panier</h1>
    <?php if (!$items): ?>
        <div class="empty-state">
            <p>Le panier est vide.</p>
            <a class="btn btn-primary" href="<?php echo e(url('index.php')); ?>">Continuer vos achats</a>
        </div>
    <?php else: ?>
        <div class="table-responsive cart-table">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $p = $item['product']; ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img class="cart-thumb" src="<?php echo e(product_image_src($p['image'])); ?>" alt="">
                                <div>
                                    <a href="<?php echo e(url('product.php?id=' . (int) $p['id'])); ?>"><?php echo e($p['name']); ?></a>
                                    <div class="small text-muted">Stock : <?php echo (int) $p['stock']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo e(format_price($p['price'])); ?></td>
                        <td>
                            <form method="post" action="<?php echo e(url('cart-action.php')); ?>" class="d-flex gap-2">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                                <input class="form-control qty-input" type="number" name="qty" min="1" max="<?php echo (int) $p['stock']; ?>" value="<?php echo (int) $item['qty']; ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">OK</button>
                            </form>
                        </td>
                        <td><?php echo e(format_price($item['line_total'])); ?></td>
                        <td>
                            <form method="post" action="<?php echo e(url('cart-action.php')); ?>">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Retirer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
            <a class="btn btn-outline-secondary" href="<?php echo e(url('index.php')); ?>">Continuer vos achats</a>
            <div class="cart-summary">
                <div class="total">Total : <?php echo e(format_price(cart_total())); ?></div>
                <?php if (!is_logged_in()): ?>
                    <p class="small text-muted mb-2">Panier conservé en session. Connexion requise pour valider la commande.</p>
                <?php endif; ?>
                <a class="btn btn-primary btn-lg" href="<?php echo e(url('checkout.php')); ?>">
                    <?php echo is_logged_in() ? 'Passer commande' : 'Se connecter et commander'; ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
