<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/schema.php';

$result = null;
$error = '';

try {
    $result = aurloum_migrate();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Base de données — Aurloum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 620px;">
    <h1 class="h3 mb-3">Base de données Aurloum</h1>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <p>Vérifiez MySQL dans WAMP et les identifiants de <code>config.php</code>.</p>
    <?php else: ?>
        <div class="alert alert-success">
            Base <strong><?php echo htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8'); ?></strong> créée / mise à jour.
        </div>
        <ul class="list-group mb-4">
            <li class="list-group-item d-flex justify-content-between">Catégories <strong><?php echo (int) $result['categories']; ?></strong></li>
            <li class="list-group-item d-flex justify-content-between">Produits <strong><?php echo (int) $result['products']; ?></strong></li>
            <li class="list-group-item d-flex justify-content-between">Commandes <strong><?php echo (int) $result['orders']; ?></strong></li>
            <li class="list-group-item d-flex justify-content-between">Utilisateurs <strong><?php echo (int) $result['users']; ?></strong></li>
        </ul>
        <?php if (!empty($result['seeded'])): ?>
            <p class="text-muted">8 produits de démo ont été insérés.</p>
        <?php endif; ?>
        <a class="btn btn-dark me-2" href="index.php">Boutique</a>
        <a class="btn btn-outline-dark" href="login.php">Connexion</a>
        <p class="small text-muted mt-3 mb-0">
            Admin : <code><?php echo htmlspecialchars(DEFAULT_ADMIN_EMAIL, ENT_QUOTES, 'UTF-8'); ?></code> / <code><?php echo htmlspecialchars(DEFAULT_ADMIN_PASS, ENT_QUOTES, 'UTF-8'); ?></code><br>
            Client : <code><?php echo htmlspecialchars(DEFAULT_USER_EMAIL, ENT_QUOTES, 'UTF-8'); ?></code> / <code><?php echo htmlspecialchars(DEFAULT_USER_PASS, ENT_QUOTES, 'UTF-8'); ?></code>
        </p>
    <?php endif; ?>
</div>
</body>
</html>
