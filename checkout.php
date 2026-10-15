<?php
require_once __DIR__ . '/includes/init.php';
require_login_to_checkout();

$items = cart_items();
if (!$items) {
    flash_set('error', 'Votre panier est vide.');
    redirect('cart.php');
}

$errors = array();
$authUser = current_user();
$values = array(
    'customer_name' => $authUser ? $authUser['name'] : '',
    'email' => $authUser ? $authUser['email'] : '',
    'phone' => '',
    'address' => '',
    'city' => '',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($values as $key => $unused) {
        $values[$key] = trim(isset($_POST[$key]) ? $_POST[$key] : '');
    }

    if ($values['customer_name'] === '') {
        $errors[] = 'Le nom est obligatoire.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if ($values['phone'] === '') {
        $errors[] = 'Le téléphone est obligatoire.';
    }
    if ($values['address'] === '') {
        $errors[] = 'L’adresse est obligatoire.';
    }
    if ($values['city'] === '') {
        $errors[] = 'La ville est obligatoire.';
    }

    $items = cart_items();
    if (!$items) {
        $errors[] = 'Panier vide.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            foreach ($items as $item) {
                $fresh = get_product($item['product']['id']);
                if (!$fresh || (int) $fresh['stock'] < $item['qty']) {
                    throw new Exception('Stock insuffisant pour ' . $item['product']['name'] . '.');
                }
            }

            $stmt = $pdo->prepare('INSERT INTO orders (user_id, customer_name, email, phone, address, city, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute(array(
                $authUser ? (int) $authUser['id'] : null,
                $values['customer_name'],
                $values['email'],
                $values['phone'],
                $values['address'],
                $values['city'],
                cart_total(),
                'nouvelle',
            ));
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

            foreach ($items as $item) {
                $itemStmt->execute(array(
                    $orderId,
                    $item['product']['id'],
                    $item['product']['name'],
                    $item['product']['price'],
                    $item['qty'],
                ));
                $stockStmt->execute(array($item['qty'], $item['product']['id'], $item['qty']));
                if ($stockStmt->rowCount() !== 1) {
                    throw new Exception('Stock insuffisant pour ' . $item['product']['name'] . '.');
                }
            }

            $pdo->commit();
            cart_clear();
            redirect('confirmation.php?id=' . $orderId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Commande';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h1 class="section-title mb-4">Finaliser la commande</h1>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <form method="post" class="card-panel">
                <?php echo csrf_field(); ?>
                <h2 class="h5 mb-3">Vos coordonnées</h2>
                <div class="mb-3">
                    <label class="form-label" for="customer_name">Nom complet</label>
                    <input class="form-control" id="customer_name" name="customer_name" required value="<?php echo e($values['customer_name']); ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" required value="<?php echo e($values['email']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="phone">Téléphone</label>
                        <input class="form-control" id="phone" name="phone" required value="<?php echo e($values['phone']); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="address">Adresse</label>
                    <input class="form-control" id="address" name="address" required value="<?php echo e($values['address']); ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="city">Ville</label>
                    <input class="form-control" id="city" name="city" required value="<?php echo e($values['city']); ?>">
                </div>
                <p class="small text-muted">Livraison à domicile ou retrait en boutique. Paiement à la réception (démo, aucune carte demandée).</p>
                <button class="btn btn-primary btn-lg" type="submit">Confirmer la commande</button>
            </form>
        </div>
        <div class="col-lg-5">
            <div class="card-panel">
                <h2 class="h5 mb-3">Récapitulatif</h2>
                <?php foreach ($items as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo e($item['product']['name']); ?> × <?php echo (int) $item['qty']; ?></span>
                        <strong><?php echo e(format_price($item['line_total'])); ?></strong>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Total</span>
                    <strong><?php echo e(format_price(cart_total())); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
