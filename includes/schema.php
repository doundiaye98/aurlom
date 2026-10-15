<?php

function aurloum_pdo_root()
{
    return new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
}

function aurloum_column_exists(PDO $pdo, $table, $column)
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute(array(DB_NAME, $table, $column));
    return (int) $stmt->fetchColumn() > 0;
}

function aurloum_table_exists(PDO $pdo, $table)
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $stmt->execute(array(DB_NAME, $table));
    return (int) $stmt->fetchColumn() > 0;
}

function aurloum_try_exec(PDO $pdo, $sql)
{
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
    }
}

function aurloum_column_length(PDO $pdo, $table, $column)
{
    $stmt = $pdo->prepare(
        'SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute(array(DB_NAME, $table, $column));
    $len = $stmt->fetchColumn();
    return $len === false ? 0 : (int) $len;
}

function aurloum_ensure_demo_user(PDO $pdo, $name, $email, $password, $role)
{
    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute(array($email));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($password, $row['password_hash'])) {
        return;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($row) {
        $pdo->prepare('UPDATE users SET password_hash = ?, name = ? WHERE id = ?')->execute(array($hash, $name, $row['id']));
        return;
    }
    $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')->execute(array($name, $email, $hash, $role));
}

function aurloum_slug($text, $fallback = 'item')
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

function aurloum_migrate()
{
    $pdo = aurloum_pdo_root();
    $db = str_replace('`', '', DB_NAME);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $db . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . $db . '`');
    // MySQL 5.7 + NO_ZERO_DATE refuse certains ALTER (erreur 1067 sur id).
    $pdo->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            UNIQUE KEY uq_categories_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED DEFAULT NULL,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(160) NOT NULL,
            description TEXT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image VARCHAR(500) NOT NULL,
            stock INT UNSIGNED NOT NULL DEFAULT 0,
            item_condition VARCHAR(20) NOT NULL DEFAULT 'neuf',
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_products_slug (slug),
            KEY idx_products_category (category_id),
            KEY idx_products_active (is_active),
            CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(150) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_users_email (email),
            KEY idx_users_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(120) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(80) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'nouvelle',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_orders_user (user_id),
            CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED DEFAULT NULL,
            product_name VARCHAR(150) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if (aurloum_table_exists($pdo, 'products')) {
        if (!aurloum_column_exists($pdo, 'products', 'category_id')) {
            aurloum_try_exec($pdo, 'ALTER TABLE products ADD COLUMN category_id INT UNSIGNED DEFAULT NULL AFTER id');
        }
        if (!aurloum_column_exists($pdo, 'products', 'slug')) {
            aurloum_try_exec($pdo, 'ALTER TABLE products ADD COLUMN slug VARCHAR(160) NULL AFTER name');
        }
        if (!aurloum_column_exists($pdo, 'products', 'is_active')) {
            aurloum_try_exec($pdo, 'ALTER TABLE products ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1');
        }
        if (!aurloum_column_exists($pdo, 'products', 'updated_at')) {
            aurloum_try_exec($pdo, 'ALTER TABLE products ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
        if (!aurloum_column_exists($pdo, 'products', 'item_condition')) {
            aurloum_try_exec($pdo, "ALTER TABLE products ADD COLUMN item_condition VARCHAR(20) NOT NULL DEFAULT 'neuf' AFTER stock");
        }
        if (!aurloum_column_exists($pdo, 'products', 'is_featured')) {
            aurloum_try_exec($pdo, 'ALTER TABLE products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER item_condition');
        }
        if (aurloum_column_length($pdo, 'products', 'image') < 500) {
            aurloum_try_exec($pdo, "ALTER TABLE products MODIFY image VARCHAR(500) NOT NULL DEFAULT ''");
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT NOT NULL,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_reviews_user_product (user_id, product_id),
            KEY idx_reviews_product (product_id),
            CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if (aurloum_table_exists($pdo, 'users') && !aurloum_column_exists($pdo, 'users', 'admin_note')) {
        aurloum_try_exec($pdo, 'ALTER TABLE users ADD COLUMN admin_note TEXT NULL AFTER role');
    }

    if (aurloum_table_exists($pdo, 'orders') && !aurloum_column_exists($pdo, 'orders', 'user_id')) {
        aurloum_try_exec($pdo, 'ALTER TABLE orders ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER id');
        aurloum_try_exec($pdo, 'ALTER TABLE orders ADD KEY idx_orders_user (user_id)');
        aurloum_try_exec($pdo, 'ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
    }

    aurloum_ensure_demo_user($pdo, 'Administrateur', DEFAULT_ADMIN_EMAIL, DEFAULT_ADMIN_PASS, 'admin');
    aurloum_ensure_demo_user($pdo, 'Client démo', DEFAULT_USER_EMAIL, DEFAULT_USER_PASS, 'user');

    $catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($catCount === 0) {
        $ins = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
        foreach (aurloum_it_categories() as $row) {
            $ins->execute($row);
        }
    }

    $slugToId = array();
    foreach ($pdo->query('SELECT id, slug FROM categories') as $row) {
        $slugToId[$row['slug']] = (int) $row['id'];
    }

    $usedSlugs = array();
    foreach ($pdo->query('SELECT id, name, slug FROM products') as $row) {
        $slug = trim((string) $row['slug']);
        if ($slug === '') {
            $slug = aurloum_slug($row['name'], 'produit-' . $row['id']);
        }
        $base = $slug;
        $i = 2;
        while (isset($usedSlugs[$slug])) {
            $slug = $base . '-' . $i;
            $i++;
        }
        $usedSlugs[$slug] = true;
        if ($slug !== $row['slug']) {
            $upd = $pdo->prepare('UPDATE products SET slug = ? WHERE id = ?');
            $upd->execute(array($slug, $row['id']));
        }
    }

    try {
        $pdo->exec('ALTER TABLE products ADD UNIQUE KEY uq_products_slug (slug)');
    } catch (PDOException $e) {
        // index already present
    }
    try {
        $pdo->exec('ALTER TABLE products ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL');
    } catch (PDOException $e) {
        // constraint already present
    }

    $seeded = aurloum_refresh_it_catalog($pdo);
    aurloum_seed_vitrine_and_reviews($pdo);
    aurloum_link_local_images($pdo);

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    return array(
        'seeded' => $seeded,
        'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'categories' => (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    );
}

function aurloum_it_categories()
{
    return array(
        array('PC portables', 'pc-portables'),
        array('PC de bureau', 'pc-bureau'),
        array('Écrans', 'ecrans'),
        array('Périphériques', 'peripheriques'),
        array('Stockage', 'stockage'),
        array('Réseau', 'reseau'),
    );
}

function aurloum_it_products()
{
    return array(
        array('pc-portables', 'Dell Latitude 5420', 'PC portable reconditionné, Core i5, 16 Go RAM, SSD 256 Go, écran 14". Testé en boutique, batterie en bon état.', 429.00, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=80', 6, 'reconditionne'),
        array('pc-portables', 'Lenovo ThinkPad T14', 'ThinkPad reconditionné, Ryzen 5, 16 Go RAM, SSD 512 Go. Châssis professionnel, clavier rétroéclairé.', 519.00, 'img/Lenovo ThinkPad T14.jpg', 4, 'reconditionne'),
        array('pc-portables', 'HP 15s i5 16 Go', 'PC portable neuf, Intel Core i5, 16 Go RAM, SSD 512 Go, Windows 11. Idéal bureautique et études.', 699.00, 'img/HP 15s i5 16 Go.jpg', 8, 'neuf'),
        array('pc-bureau', 'Tour Ryzen 5 / 16 Go', 'PC de bureau neuf, Ryzen 5, 16 Go RAM, SSD 500 Go. Prêt pour bureautique, photo et usage quotidien.', 749.00, 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=900&q=80', 5, 'neuf'),
        array('ecrans', 'Écran Dell 24" Full HD', 'Dalle IPS 24" reconditionnée, Full HD, HDMI + DisplayPort. Pied réglable, testé.', 119.00, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=900&q=80', 10, 'reconditionne'),
        array('ecrans', 'LG UltraGear 27" 144 Hz', 'Écran gaming neuf 27" QHD, 144 Hz, 1 ms. HDMI 2.0 et DisplayPort.', 259.00, 'img/LG UltraGear 27.jpg', 7, 'neuf'),
        array('peripheriques', 'Clavier + souris Logitech', 'Pack sans fil neuf, récepteur USB, pile longue durée. Compatible Windows et Mac.', 39.00, 'https://images.unsplash.com/photo-1511467687858-23d96c32e4ae?auto=format&fit=crop&w=900&q=80', 20, 'neuf'),
        array('stockage', 'SSD NVMe 1 To', 'SSD interne neuf, lecture jusqu’à 3500 Mo/s. Convient PC portable et bureau.', 79.00, 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=900&q=80', 15, 'neuf'),
        array('reseau', 'Routeur Wi-Fi 6 AX1800', 'Routeur neuf dual-band, Wi-Fi 6, 4 ports Gigabit. Idéal appartement ou petit bureau.', 54.00, 'https://images.unsplash.com/photo-1606904825846-647eb07f5be2?auto=format&fit=crop&w=900&q=80', 12, 'neuf'),
    );
}

function aurloum_refresh_it_catalog(PDO $pdo)
{
    $legacy = (int) $pdo->query("SELECT COUNT(*) FROM categories WHERE slug IN ('maison','cuisine','bain','textile','papeterie')")->fetchColumn();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($legacy === 0 && $count > 0) {
        return false;
    }

    if ($legacy > 0) {
        $pdo->exec('UPDATE products SET category_id = NULL');
        $pdo->exec('DELETE FROM categories');
        $pdo->exec('DELETE FROM products');
        $count = 0;
    }

    $insCat = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
    $existing = array();
    foreach ($pdo->query('SELECT slug FROM categories') as $row) {
        $existing[$row['slug']] = true;
    }
    foreach (aurloum_it_categories() as $row) {
        if (empty($existing[$row[1]])) {
            $insCat->execute($row);
        }
    }

    $slugToId = array();
    foreach ($pdo->query('SELECT id, slug FROM categories') as $row) {
        $slugToId[$row['slug']] = (int) $row['id'];
    }

    if ($count > 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO products (category_id, name, slug, description, price, image, stock, item_condition, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
    );
    foreach (aurloum_it_products() as $row) {
        $catId = isset($slugToId[$row[0]]) ? $slugToId[$row[0]] : null;
        $stmt->execute(array(
            $catId,
            $row[1],
            aurloum_slug($row[1]),
            $row[2],
            $row[3],
            $row[4],
            $row[5],
            $row[6],
        ));
    }
    return true;
}

function aurloum_seed_vitrine_and_reviews(PDO $pdo)
{
    $featured = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_featured = 1')->fetchColumn();
    if ($featured === 0) {
        $ids = $pdo->query('SELECT id FROM products ORDER BY id ASC LIMIT 4')->fetchAll(PDO::FETCH_COLUMN);
        $mark = $pdo->prepare('UPDATE products SET is_featured = 1 WHERE id = ?');
        foreach ($ids as $pid) {
            $mark->execute(array($pid));
        }
    }

    $reviewCount = (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
    if ($reviewCount > 0) {
        return;
    }

    $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $userStmt->execute(array(DEFAULT_USER_EMAIL));
    $userId = $userStmt->fetchColumn();
    if (!$userId) {
        return;
    }

    $products = $pdo->query('SELECT id, name FROM products ORDER BY id ASC')->fetchAll();
    $byName = array();
    foreach ($products as $row) {
        $byName[$row['name']] = (int) $row['id'];
    }

    $samples = array(
        array('Dell Latitude 5420', 5, 'Reçu propre, batterie correcte, nickel pour le télétravail.'),
        array('Lenovo ThinkPad T14', 4, 'Clavier au top. Léger bruit de ventilateur, mais très bon rapport qualité/prix.'),
        array('HP 15s i5 16 Go', 5, 'Neuf, déballé en boutique. Parfait pour les cours.'),
        array('Écran Dell 24" Full HD', 4, 'Dalle nette, quelques micro-rayures invisibles à l’usage.'),
        array('SSD NVMe 1 To', 5, 'Installé en 10 minutes, le PC a une autre vie.'),
    );
    $ins = $pdo->prepare(
        'INSERT INTO reviews (product_id, user_id, rating, comment, is_published) VALUES (?, ?, ?, ?, 1)'
    );
    foreach ($samples as $row) {
        if (empty($byName[$row[0]])) {
            continue;
        }
        $ins->execute(array($byName[$row[0]], $userId, $row[1], $row[2]));
    }
}

function aurloum_link_local_images(PDO $pdo)
{
    $dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'img';
    if (!is_dir($dir)) {
        return;
    }
    $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');
    $files = scandir($dir);
    if ($files === false) {
        return;
    }
    $products = $pdo->query('SELECT id, name, image FROM products')->fetchAll();
    $upd = $pdo->prepare('UPDATE products SET image = ? WHERE id = ?');
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            continue;
        }
        $base = pathinfo($file, PATHINFO_FILENAME);
        if (stripos($base, 'cabas') !== false) {
            continue;
        }
        $baseNorm = mb_strtolower($base, 'UTF-8');
        foreach ($products as $product) {
            $nameNorm = mb_strtolower($product['name'], 'UTF-8');
            if ($nameNorm === $baseNorm
                || strpos($nameNorm, $baseNorm) === 0
                || strpos($baseNorm, $nameNorm) === 0
            ) {
                $path = 'img/' . $file;
                if ($product['image'] !== $path) {
                    $upd->execute(array($path, $product['id']));
                }
                break;
            }
        }
    }
}
