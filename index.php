<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Boutique';
$categoryId = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
$etat = isset($_GET['etat']) ? $_GET['etat'] : '';
if (!isset(product_conditions()[$etat])) {
    $etat = '';
}
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');
$categories = get_categories();
$filtered = $categoryId || $etat || $search !== '';
$allActive = get_products(array('active_only' => true));
$countsByCat = array();
foreach ($allActive as $item) {
    $cid = (int) $item['category_id'];
    if (!isset($countsByCat[$cid])) {
        $countsByCat[$cid] = 0;
    }
    $countsByCat[$cid]++;
}
$products = get_products(array(
    'active_only' => true,
    'category_id' => $categoryId,
    'q' => $search,
    'item_condition' => $etat,
));
$vitrine = !$filtered ? get_products(array('active_only' => true, 'featured_only' => true)) : array();
if (!$filtered && !$vitrine) {
    $vitrine = array_slice($allActive, 0, 4);
}
$spotlight = $vitrine ? $vitrine[0] : null;
$vitrineRest = $vitrine ? array_slice($vitrine, 1) : array();
$activeCategory = null;
foreach ($categories as $cat) {
    if ((int) $cat['id'] === $categoryId) {
        $activeCategory = $cat;
        break;
    }
}
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <p class="eyebrow">Boutique informatique</p>
                <h1>PC, écrans et composants neufs ou reconditionnés.</h1>
                <p class="lead">Chaque article reconditionné est testé en magasin. Commandez en ligne, faites-vous livrer ou retirez sur place.</p>
                <div class="hero-cta">
                    <a href="#vitrine" class="btn btn-primary btn-lg">Voir la vitrine</a>
                    <a href="#produits" class="btn btn-outline-primary btn-lg">Catalogue</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-panel">
                    <img src="https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1200&q=80" alt="Matériel informatique Aurloum">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="trust-strip">
    <div class="container">
        <div class="trust-row">
            <div><i class="bi bi-patch-check"></i> Reconditionné contrôlé</div>
            <div><i class="bi bi-shield-check"></i> Garantie affichée</div>
            <div><i class="bi bi-shop"></i> Retrait magasin</div>
            <div><i class="bi bi-truck"></i> Livraison à domicile</div>
        </div>
    </div>
</section>

<?php if ($spotlight): ?>
<section id="vitrine" class="section-block section-vitrine">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Sélection boutique</p>
                <h2 class="section-title">Vitrine</h2>
            </div>
            <a class="section-link" href="#produits">Voir tout le catalogue <i class="bi bi-arrow-down"></i></a>
        </div>

        <article class="spotlight js-product-card">
            <a class="spotlight-image" href="<?php echo e(url('product.php?id=' . (int) $spotlight['id'])); ?>">
                <img src="<?php echo e(product_image_src($spotlight['image'])); ?>" alt="<?php echo e($spotlight['name']); ?>">
                <span class="spotlight-flag">Choix vitrine</span>
            </a>
            <div class="spotlight-body">
                <div class="spotlight-meta">
                    <?php if (!empty($spotlight['category_name'])): ?>
                        <span><?php echo e($spotlight['category_name']); ?></span>
                    <?php endif; ?>
                    <span class="condition-badge static <?php echo $spotlight['item_condition'] === 'reconditionne' ? 'is-refurb' : 'is-new'; ?>">
                        <?php echo e(condition_label($spotlight['item_condition'])); ?>
                    </span>
                </div>
                <h3>
                    <a href="<?php echo e(url('product.php?id=' . (int) $spotlight['id'])); ?>">
                        <?php echo e($spotlight['name']); ?>
                    </a>
                </h3>
                <div class="rating-line">
                    <?php if (!empty($spotlight['review_count'])): ?>
                        <?php echo stars_html($spotlight['avg_rating']); ?>
                        <span><?php echo number_format((float) $spotlight['avg_rating'], 1, ',', ' '); ?> / 5 · <?php echo (int) $spotlight['review_count']; ?> avis</span>
                    <?php else: ?>
                        <span>Pas encore de note</span>
                    <?php endif; ?>
                </div>
                <p class="spotlight-text"><?php echo e(excerpt($spotlight['description'], 160)); ?></p>
                <p class="warranty mb-3"><?php echo e(condition_warranty($spotlight['item_condition'])); ?></p>
                <div class="spotlight-foot">
                    <p class="product-price mb-0"><?php echo e(format_price($spotlight['price'])); ?></p>
                    <form class="js-add-to-cart" method="post" action="<?php echo e(url('cart-action.php')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo (int) $spotlight['id']; ?>">
                        <input type="hidden" name="qty" value="1">
                        <button class="btn btn-primary" type="submit" <?php echo (int) $spotlight['stock'] < 1 ? 'disabled' : ''; ?>>
                            <?php echo (int) $spotlight['stock'] < 1 ? 'Rupture' : 'Ajouter au panier'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </article>

        <?php if ($vitrineRest): ?>
            <h3 class="vitrine-more">Aussi en vitrine</h3>
            <div class="row g-3 g-md-4">
                <?php foreach ($vitrineRest as $product): ?>
                <div class="col-6 col-md-4">
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section id="produits" class="section-block section-catalog">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Stock boutique</p>
                <h2 class="section-title">Catalogue</h2>
            </div>
            <p class="result-pill"><?php echo count($products); ?> / <?php echo count($allActive); ?> article<?php echo count($allActive) > 1 ? 's' : ''; ?></p>
        </div>

        <form class="catalog-panel" method="get" action="<?php echo e(url('index.php')); ?>#produits">
            <div class="cat-grid">
                <a class="cat-tile <?php echo !$categoryId ? 'active' : ''; ?>" href="<?php echo e(url('index.php' . ($etat ? '?etat=' . urlencode($etat) : '') . '#produits')); ?>">
                    <i class="bi bi-grid"></i>
                    <strong>Tous</strong>
                    <span><?php echo count($allActive); ?></span>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a class="cat-tile <?php echo $categoryId === (int) $cat['id'] ? 'active' : ''; ?>" href="<?php echo e(url('index.php?cat=' . (int) $cat['id'] . ($etat ? '&etat=' . urlencode($etat) : '') . '#produits')); ?>">
                        <i class="bi <?php echo e(category_icon($cat['slug'])); ?>"></i>
                        <strong><?php echo e($cat['name']); ?></strong>
                        <span><?php echo isset($countsByCat[(int) $cat['id']]) ? (int) $countsByCat[(int) $cat['id']] : 0; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="filter-bar">
                <div class="chip-row">
                    <a class="chip <?php echo $etat === '' ? 'active' : ''; ?>" href="<?php echo e(url('index.php' . ($categoryId ? '?cat=' . $categoryId : '') . '#produits')); ?>">Neuf + reconditionné</a>
                    <?php foreach (product_conditions() as $code => $label): ?>
                        <a class="chip <?php echo $etat === $code ? 'active' : ''; ?>" href="<?php echo e(url('index.php?' . ($categoryId ? 'cat=' . $categoryId . '&' : '') . 'etat=' . $code . '#produits')); ?>">
                            <?php echo e($label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="filter-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="<?php echo e($search); ?>" placeholder="Rechercher un modèle (Dell, SSD…)">
                    <?php if ($categoryId): ?><input type="hidden" name="cat" value="<?php echo (int) $categoryId; ?>"><?php endif; ?>
                    <?php if ($etat): ?><input type="hidden" name="etat" value="<?php echo e($etat); ?>"><?php endif; ?>
                    <button class="btn btn-primary" type="submit">Rechercher</button>
                </div>
            </div>
            <?php if ($filtered): ?>
                <div class="active-filters">
                    <span>Filtres :</span>
                    <?php if ($activeCategory): ?><em><?php echo e($activeCategory['name']); ?></em><?php endif; ?>
                    <?php if ($etat): ?><em><?php echo e(condition_label($etat)); ?></em><?php endif; ?>
                    <?php if ($search !== ''): ?><em>« <?php echo e($search); ?> »</em><?php endif; ?>
                    <a href="<?php echo e(url('index.php#produits')); ?>">Tout afficher</a>
                </div>
            <?php endif; ?>
        </form>

        <div class="row g-3 g-md-4">
            <?php foreach ($products as $product): ?>
            <div class="col-6 col-md-6 col-xl-3">
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
            <?php if (!$products): ?>
            <div class="col-12">
                <div class="empty-state">
                    <p>Aucun article pour ces filtres.</p>
                    <a class="btn btn-primary" href="<?php echo e(url('index.php#produits')); ?>">Réinitialiser</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
