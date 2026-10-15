<?php
require_once __DIR__ . '/includes/init.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}

csrf_check();

$action = isset($_POST['action']) ? $_POST['action'] : '';
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$qty = isset($_POST['qty']) ? (int) $_POST['qty'] : 1;

try {
    if ($action === 'add') {
        $product = get_product($productId);
        if (!$product || (int) $product['stock'] < 1) {
            throw new Exception('Produit indisponible.');
        }
        $current = isset(cart()[$productId]) ? (int) cart()[$productId] : 0;
        $wanted = $current + max(1, $qty);
        if ($wanted > (int) $product['stock']) {
            throw new Exception('Stock insuffisant.');
        }
        cart_add($productId, $qty);
        $message = $product['name'] . ' ajouté au panier.';
    } elseif ($action === 'update') {
        $product = get_product($productId);
        if ($product && $qty > (int) $product['stock']) {
            throw new Exception('Stock insuffisant.');
        }
        cart_set($productId, $qty);
        $message = 'Panier mis à jour.';
    } elseif ($action === 'remove') {
        cart_set($productId, 0);
        $message = 'Article retiré.';
    } else {
        throw new Exception('Action inconnue.');
    }

    if ($isAjax) {
        json_response(array(
            'ok' => true,
            'message' => $message,
            'count' => cart_count(),
            'total' => format_price(cart_total()),
        ));
    }

    flash_set('success', $message);
    redirect($action === 'add' ? 'cart.php' : 'cart.php');
} catch (Exception $e) {
    if ($isAjax) {
        json_response(array('ok' => false, 'message' => $e->getMessage()), 400);
    }
    flash_set('error', $e->getMessage());
    redirect('cart.php');
}
