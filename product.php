<?php
require_once __DIR__ . '/includes/init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $id ? get_product($id) : null;
if (!$product || (empty($product['is_active']) && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container py-5"><div class="empty-state"><h1>Produit introuvable</h1><a href="' . e(url('index.php')) . '">Retour à la boutique</a></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$ownReview = $user ? get_user_review($product['id'], $user['id']) : null;
$reviewErrors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_rating'])) {
    csrf_check();
    if (!$user) {
        flash_set('error', 'Connectez-vous pour noter un produit.');
        redirect('login.php');
    }
    $rating = (int) $_POST['review_rating'];
    $comment = trim(isset($_POST['review_comment']) ? $_POST['review_comment'] : '');
    if ($rating < 1 || $rating > 5) {
        $reviewErrors[] = 'Choisissez une note de 1 à 5.';
    }
    if (mb_strlen($comment) < 8) {
        $reviewErrors[] = 'Le commentaire doit faire au moins 8 caractères.';
    }
    if (!$reviewErrors) {
        save_review($product['id'], $user['id'], $rating, $comment);
        flash_set('success', 'Merci, votre note a été enregistrée.');
        redirect('product.php?id=' . (int) $product['id']);
    }
}

$reviews = get_product_reviews($product['id']);
$related = array();
if (!empty($product['category_id'])) {
    foreach (get_products(array('active_only' => true, 'category_id' => $product['category_id'])) as $item) {
        if ((int) $item['id'] !== (int) $product['id']) {
            $related[] = $item;
        }
    }
    $related = array_slice($related, 0, 4);
}

$selectedRating = $ownReview ? (int) $ownReview['rating'] : 5;
$pageTitle = $product['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="page-product">
    <div class="container">
        <nav class="product-nav" aria-label="Fil d’ariane">
            <a href="<?php echo e(url('index.php')); ?>">Boutique</a>
            <?php if (!empty($product['category_name'])): ?>
                <span>/</span>
                <a href="<?php echo e(url('index.php?cat=' . (int) $product['category_id'] . '#produits')); ?>"><?php echo e($product['category_name']); ?></a>
            <?php endif; ?>
            <span>/</span>
            <span><?php echo e($product['name']); ?></span>
        </nav>

        <div class="product-layout">
            <div class="product-gallery">
                <img src="<?php echo e(product_image_src($product['image'])); ?>" alt="<?php echo e($product['name']); ?>">
                <span class="condition-badge <?php echo $product['item_condition'] === 'reconditionne' ? 'is-refurb' : 'is-new'; ?>">
                    <?php echo e(condition_label($product['item_condition'])); ?>
                </span>
            </div>

            <div class="product-info">
                <p class="eyebrow"><?php echo e($product['category_name'] ? $product['category_name'] : 'Aurloum'); ?></p>
                <h1><?php echo e($product['name']); ?></h1>
                <div class="rating-line">
                    <?php if (!empty($product['review_count'])): ?>
                        <?php echo stars_html($product['avg_rating']); ?>
                        <a href="#avis"><?php echo number_format((float) $product['avg_rating'], 1, ',', ' '); ?> / 5 · <?php echo (int) $product['review_count']; ?> avis</a>
                    <?php else: ?>
                        <span>Pas encore de note</span>
                    <?php endif; ?>
                </div>
                <p class="product-price"><?php echo e(format_price($product['price'])); ?></p>
                <p class="product-lead"><?php echo nl2br(e($product['description'])); ?></p>

                <ul class="spec-list">
                    <li>
                        <span>État</span>
                        <strong><?php echo e(condition_label($product['item_condition'])); ?></strong>
                    </li>
                    <li>
                        <span>Garantie</span>
                        <strong><?php echo e($product['item_condition'] === 'reconditionne' ? '6 mois' : '12 mois'); ?></strong>
                    </li>
                    <li>
                        <span>Disponibilité</span>
                        <strong class="stock <?php echo (int) $product['stock'] > 0 ? 'in' : 'out'; ?>">
                            <?php echo (int) $product['stock'] > 0 ? (int) $product['stock'] . ' en boutique' : 'Rupture'; ?>
                        </strong>
                    </li>
                    <li>
                        <span>Livraison</span>
                        <strong>Domicile ou retrait</strong>
                    </li>
                </ul>

                <form class="js-add-to-cart buy-box" method="post" action="<?php echo e(url('cart-action.php')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                    <label class="form-label" for="qty">Quantité</label>
                    <div class="buy-row">
                        <input class="form-control qty-input" type="number" id="qty" name="qty" min="1" max="<?php echo max(1, (int) $product['stock']); ?>" value="1" <?php echo (int) $product['stock'] < 1 ? 'disabled' : ''; ?>>
                        <button class="btn btn-primary btn-lg" type="submit" <?php echo (int) $product['stock'] < 1 ? 'disabled' : ''; ?>>
                            <i class="bi bi-bag-plus"></i>
                            <?php echo (int) $product['stock'] < 1 ? 'Rupture de stock' : 'Ajouter au panier'; ?>
                        </button>
                    </div>
                    <p class="buy-note"><?php echo e(condition_warranty($product['item_condition'])); ?></p>
                </form>
            </div>
        </div>

        <section id="avis" class="reviews-wrap">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Avis</p>
                    <h2 class="section-title">Notes des clients</h2>
                </div>
            </div>
            <div class="reviews-grid">
                <div>
                    <?php if ($reviewErrors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($reviewErrors as $error): ?><div><?php echo e($error); ?></div><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($user): ?>
                        <form method="post" class="card-panel review-form">
                            <?php echo csrf_field(); ?>
                            <h3><?php echo $ownReview ? 'Modifier votre avis' : 'Laisser une note'; ?></h3>
                            <div class="star-pick" role="radiogroup" aria-label="Note">
                                <?php for ($n = 5; $n >= 1; $n--): ?>
                                    <input type="radio" name="review_rating" id="star<?php echo $n; ?>" value="<?php echo $n; ?>" <?php echo $selectedRating === $n ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $n; ?>" title="<?php echo $n; ?> / 5"><i class="bi bi-star-fill"></i></label>
                                <?php endfor; ?>
                            </div>
                            <label class="form-label" for="review_comment">Commentaire</label>
                            <textarea class="form-control" id="review_comment" name="review_comment" rows="4" required><?php echo e($ownReview ? $ownReview['comment'] : ''); ?></textarea>
                            <button class="btn btn-primary mt-3" type="submit">Publier</button>
                        </form>
                    <?php else: ?>
                        <div class="card-panel">
                            <p class="mb-2">Connectez-vous pour noter ce produit.</p>
                            <a class="btn btn-outline-primary" href="<?php echo e(url('login.php')); ?>">Connexion</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!$reviews): ?>
                        <div class="empty-state"><p>Aucun avis pour le moment.</p></div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-card">
                                <div class="review-top">
                                    <strong><?php echo e($review['user_name']); ?></strong>
                                    <?php echo stars_html($review['rating']); ?>
                                </div>
                                <p><?php echo nl2br(e($review['comment'])); ?></p>
                                <time><?php echo e(date('d/m/Y', strtotime($review['created_at']))); ?></time>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ($related): ?>
        <section class="related-wrap">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Même rayon</p>
                    <h2 class="section-title">Vous aimerez aussi</h2>
                </div>
            </div>
            <div class="row g-3 g-md-4">
                <?php foreach ($related as $product): ?>
                <div class="col-6 col-md-6 col-xl-3">
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
