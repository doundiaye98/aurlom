<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand"><?php echo e(SHOP_NAME); ?></div>
                <p><?php echo e(SHOP_TAGLINE); ?> Matériel testé en magasin, prêt à partir.</p>
            </div>
            <div>
                <h3>Boutique</h3>
                <a href="<?php echo e(url('index.php#vitrine')); ?>">Vitrine</a>
                <a href="<?php echo e(url('index.php#produits')); ?>">Catalogue</a>
                <a href="<?php echo e(url('cart.php')); ?>">Panier</a>
            </div>
            <div>
                <h3>Compte</h3>
                <a href="<?php echo e(url('login.php')); ?>">Connexion</a>
                <a href="<?php echo e(url('register.php')); ?>">Créer un compte</a>
                <a href="<?php echo e(url('account.php')); ?>">Mes commandes</a>
            </div>
            <div>
                <h3>Service</h3>
                <p class="mb-1">Livraison ou retrait boutique</p>
                <p class="mb-0">Neuf 12 mois · Reconditionné 6 mois</p>
            </div>
        </div>
        <div class="footer-bottom">
            <span><?php echo e(SHOP_NAME); ?> — revendeur informatique</span>
            <span>Paiement à la réception</span>
        </div>
    </div>
</footer>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cart-toast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="cart-toast-body">Ajouté au panier</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo e(asset('js/app.js')); ?>?v=7"></script>
</body>
</html>
