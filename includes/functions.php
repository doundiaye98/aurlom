<?php
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url($path)
{
    $path = '/' . ltrim($path, '/');
    return rtrim(BASE_URL, '/') . $path;
}

function asset($path)
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect($path)
{
    header('Location: ' . (strpos($path, 'http') === 0 ? $path : url($path)));
    exit;
}

function format_price($amount)
{
    return number_format((float) $amount, 2, ',', ' ') . ' €';
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check()
{
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!$token || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Requête invalide.');
    }
}

function flash_set($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function flash_get()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function cart()
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    return $_SESSION['cart'];
}

function cart_count()
{
    $total = 0;
    foreach (cart() as $qty) {
        $total += (int) $qty;
    }
    return $total;
}

function cart_add($productId, $qty)
{
    $qty = max(1, (int) $qty);
    $cart = cart();
    $id = (int) $productId;
    if (isset($cart[$id])) {
        $cart[$id] += $qty;
    } else {
        $cart[$id] = $qty;
    }
    $_SESSION['cart'] = $cart;
}

function cart_set($productId, $qty)
{
    $qty = (int) $qty;
    $cart = cart();
    $id = (int) $productId;
    if ($qty <= 0) {
        unset($cart[$id]);
    } else {
        $cart[$id] = $qty;
    }
    $_SESSION['cart'] = $cart;
}

function cart_clear()
{
    $_SESSION['cart'] = array();
}

function make_slug($text, $fallback = 'produit')
{
    $map = array(
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ÿ' => 'y',
        'œ' => 'oe', 'æ' => 'ae',
    );
    $text = strtr(mb_strtolower(trim((string) $text), 'UTF-8'), $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : $fallback;
}

function unique_product_slug($name, $ignoreId = 0)
{
    $base = make_slug($name);
    $slug = $base;
    $n = 2;
    $stmt = db()->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
    while (true) {
        $stmt->execute(array($slug, (int) $ignoreId));
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
    }
}

function product_image_src($image)
{
    $image = (string) $image;
    if ($image === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    $image = ltrim(str_replace('\\', '/', $image), '/');
    $parts = explode('/', $image);
    $encoded = array();
    foreach ($parts as $part) {
        $encoded[] = rawurlencode($part);
    }
    $path = implode('/', $encoded);
    if (strpos($image, 'img/') === 0) {
        return url($path);
    }
    return asset($path);
}

function get_product($id)
{
    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name,
                (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id AND r.is_published = 1) AS avg_rating,
                (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.is_published = 1) AS review_count
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?'
    );
    $stmt->execute(array((int) $id));
    return $stmt->fetch();
}

function get_products($filters = array())
{
    $sql = 'SELECT p.*, c.name AS category_name,
                   (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id AND r.is_published = 1) AS avg_rating,
                   (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.is_published = 1) AS review_count
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE 1=1';
    $params = array();

    if (!empty($filters['active_only'])) {
        $sql .= ' AND p.is_active = 1';
    }
    if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
        $sql .= ' AND p.is_active = ?';
        $params[] = (int) $filters['is_active'];
    }
    if (!empty($filters['category_id'])) {
        $sql .= ' AND p.category_id = ?';
        $params[] = (int) $filters['category_id'];
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if (!empty($filters['low_stock'])) {
        $sql .= ' AND p.stock > 0 AND p.stock <= ?';
        $params[] = (int) LOW_STOCK_THRESHOLD;
    }
    if (!empty($filters['stock_level'])) {
        if ($filters['stock_level'] === 'rupture') {
            $sql .= ' AND p.stock <= 0';
        } elseif ($filters['stock_level'] === 'faible') {
            $sql .= ' AND p.stock > 0 AND p.stock <= ?';
            $params[] = (int) LOW_STOCK_THRESHOLD;
        } elseif ($filters['stock_level'] === 'ok') {
            $sql .= ' AND p.stock > ?';
            $params[] = (int) LOW_STOCK_THRESHOLD;
        }
    }
    if (!empty($filters['item_condition'])) {
        $sql .= ' AND p.item_condition = ?';
        $params[] = $filters['item_condition'];
    }
    if (!empty($filters['featured_only'])) {
        $sql .= ' AND p.is_featured = 1';
    }

    $sql .= ' ORDER BY p.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_categories()
{
    return db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

function excerpt($text, $length = 140)
{
    $text = trim(preg_replace('/\s+/', ' ', (string) $text));
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length, 'UTF-8')) . '…';
}

function category_icon($slug)
{
    $map = array(
        'pc-portables' => 'bi-laptop',
        'pc-bureau' => 'bi-pc-display',
        'ecrans' => 'bi-display',
        'peripheriques' => 'bi-mouse',
        'stockage' => 'bi-hdd',
        'reseau' => 'bi-wifi',
    );
    return isset($map[$slug]) ? $map[$slug] : 'bi-grid';
}

function product_conditions()
{
    return array(
        'neuf' => 'Neuf',
        'reconditionne' => 'Reconditionné',
    );
}

function condition_label($code)
{
    $map = product_conditions();
    return isset($map[$code]) ? $map[$code] : 'Neuf';
}

function condition_warranty($code)
{
    return $code === 'reconditionne' ? 'Garantie 6 mois · testé en boutique' : 'Garantie 12 mois constructeur';
}

function stars_html($rating)
{
    $value = (float) $rating;
    $full = (int) round($value);
    $html = '<span class="stars" title="' . e(number_format($value, 1, ',', ' ')) . ' / 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $full ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
    }
    $html .= '</span>';
    return $html;
}

function get_product_reviews($productId, $publishedOnly = true)
{
    $sql = 'SELECT r.*, u.name AS user_name
            FROM reviews r
            JOIN users u ON u.id = r.user_id
            WHERE r.product_id = ?';
    if ($publishedOnly) {
        $sql .= ' AND r.is_published = 1';
    }
    $sql .= ' ORDER BY r.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute(array((int) $productId));
    return $stmt->fetchAll();
}

function get_user_review($productId, $userId)
{
    $stmt = db()->prepare('SELECT * FROM reviews WHERE product_id = ? AND user_id = ?');
    $stmt->execute(array((int) $productId, (int) $userId));
    return $stmt->fetch() ?: null;
}

function save_review($productId, $userId, $rating, $comment)
{
    $rating = max(1, min(5, (int) $rating));
    $existing = get_user_review($productId, $userId);
    if ($existing) {
        $stmt = db()->prepare('UPDATE reviews SET rating = ?, comment = ?, is_published = 1 WHERE id = ?');
        $stmt->execute(array($rating, $comment, $existing['id']));
        return;
    }
    $stmt = db()->prepare('INSERT INTO reviews (product_id, user_id, rating, comment, is_published) VALUES (?, ?, ?, ?, 1)');
    $stmt->execute(array((int) $productId, (int) $userId, $rating, $comment));
}

function handle_image_upload($currentImage)
{
    if (empty($_FILES['image_file']['tmp_name']) || !is_uploaded_file($_FILES['image_file']['tmp_name'])) {
        return $currentImage;
    }

    if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Échec de l’envoi de l’image.');
    }
    if ($_FILES['image_file']['size'] > UPLOAD_MAX_BYTES) {
        throw new Exception('Image trop lourde (max 2 Mo).');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['image_file']['tmp_name']);
    $allowed = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    );
    if (!isset($allowed[$mime])) {
        throw new Exception('Format d’image non accepté (jpg, png, webp, gif).');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
        throw new Exception('Impossible de créer le dossier uploads.');
    }

    $filename = 'p' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = UPLOAD_DIR . '/' . $filename;
    if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
        throw new Exception('Impossible d’enregistrer l’image.');
    }

    return 'uploads/' . $filename;
}

function cart_items()
{
    $items = array();
    foreach (cart() as $id => $qty) {
        $product = get_product($id);
        if (!$product) {
            continue;
        }
        $qty = (int) $qty;
        $items[] = array(
            'product' => $product,
            'qty' => $qty,
            'line_total' => (float) $product['price'] * $qty,
        );
    }
    return $items;
}

function cart_total()
{
    $total = 0;
    foreach (cart_items() as $item) {
        $total += $item['line_total'];
    }
    return $total;
}

function current_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    if (empty($_SESSION['user_id'])) {
        $user = null;
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute(array((int) $_SESSION['user_id']));
    $user = $stmt->fetch() ?: null;
    if (!$user) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function is_logged_in()
{
    return current_user() !== null;
}

function is_admin()
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function require_login()
{
    if (!is_logged_in()) {
        flash_set('error', 'Connectez-vous pour continuer.');
        redirect('login.php');
    }
}

function require_login_to_checkout()
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = 'checkout.php';
        flash_set('error', 'Connectez-vous pour valider la commande. Votre panier est conservé.');
        redirect('login.php');
    }
}

function redirect_after_login($user)
{
    $next = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '';
    unset($_SESSION['redirect_after_login']);
    if ($next === 'checkout.php' || $next === 'account.php' || $next === 'cart.php') {
        redirect($next);
    }
    redirect(!empty($user['role']) && $user['role'] === 'admin' ? 'admin/dashboard.php' : 'account.php');
}

function stock_level($stock)
{
    $qty = (int) $stock;
    if ($qty <= 0) {
        return 'rupture';
    }
    if ($qty <= (int) LOW_STOCK_THRESHOLD) {
        return 'faible';
    }
    return 'ok';
}

function stock_badge_html($stock)
{
    $level = stock_level($stock);
    $labels = array(
        'rupture' => 'Rupture',
        'faible' => 'Stock faible',
        'ok' => 'En stock',
    );
    return '<span class="stock-badge is-' . $level . '">' . e($labels[$level]) . ' · ' . (int) $stock . '</span>';
}

function require_admin()
{
    if (!is_logged_in()) {
        flash_set('error', 'Connectez-vous avec un compte administrateur.');
        redirect('login.php');
    }
    if (!is_admin()) {
        flash_set('error', 'Accès réservé à l’administrateur.');
        redirect('account.php');
    }
}

function login_user(array $user)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['is_admin']);
}

function logout_user()
{
    unset($_SESSION['user_id'], $_SESSION['is_admin']);
}

function find_user_by_email($email)
{
    $stmt = db()->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?)');
    $stmt->execute(array(trim((string) $email)));
    return $stmt->fetch() ?: null;
}

function admin_count()
{
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
}

function json_response($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
