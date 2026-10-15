<article class="product-card js-product-card h-100">
    <a href="<?php echo e(url('product.php?id=' . (int) $product['id'])); ?>" class="product-card-image">
        <img src="<?php echo e(product_image_src($product['image'])); ?>" alt="<?php echo e($product['name']); ?>">
        <span class="card-shine" aria-hidden="true"></span>
        <span class="condition-badge <?php echo $product['item_condition'] === 'reconditionne' ? 'is-refurb' : 'is-new'; ?>">
            <?php echo e(condition_label($product['item_condition'])); ?>
        </span>
    </a>
    <div class="product-card-body">
        <?php if (!empty($product['category_name'])): ?>
            <div class="product-kicker"><?php echo e($product['category_name']); ?></div>
        <?php endif; ?>
        <h3>
            <a href="<?php echo e(url('product.php?id=' . (int) $product['id'])); ?>">
                <?php echo e($product['name']); ?>
            </a>
        </h3>
        <div class="rating-line">
            <?php if (!empty($product['review_count'])): ?>
                <?php echo stars_html($product['avg_rating']); ?>
                <span><?php echo number_format((float) $product['avg_rating'], 1, ',', ' '); ?> (<?php echo (int) $product['review_count']; ?>)</span>
            <?php else: ?>
                <span>Nouveau</span>
            <?php endif; ?>
        </div>
        <div class="product-card-foot">
            <p class="price mb-0"><?php echo e(format_price($product['price'])); ?></p>
            <form class="js-add-to-cart" method="post" action="<?php echo e(url('cart-action.php')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <input type="hidden" name="qty" value="1">
                <button class="btn btn-add" type="submit" <?php echo (int) $product['stock'] < 1 ? 'disabled' : ''; ?>>
                    <?php echo (int) $product['stock'] < 1 ? 'Rupture' : 'Ajouter'; ?>
                </button>
            </form>
        </div>
    </div>
</article>
