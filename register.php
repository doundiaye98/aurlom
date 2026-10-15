<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect_after_login(current_user());
}

$errors = array();
$values = array('name' => '', 'email' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $values['name'] = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $values['email'] = strtolower(trim(isset($_POST['email']) ? $_POST['email'] : ''));
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    $pass2 = isset($_POST['password2']) ? $_POST['password2'] : '';

    if (mb_strlen($values['name']) < 2) {
        $errors[] = 'Indiquez votre nom.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    } elseif (find_user_by_email($values['email'])) {
        $errors[] = 'Un compte existe déjà avec cet email.';
    }
    if (strlen($pass) < 6) {
        $errors[] = 'Le mot de passe doit faire au moins 6 caractères.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute(array(
            $values['name'],
            $values['email'],
            password_hash($pass, PASSWORD_DEFAULT),
            'user',
        ));
        $user = find_user_by_email($values['email']);
        login_user($user);
        flash_set('success', 'Compte créé. Bienvenue ' . $values['name'] . '.');
        redirect_after_login($user);
    }
}

$pageTitle = 'Inscription';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <form method="post" class="card-panel">
                <?php echo csrf_field(); ?>
                <h1 class="h4 mb-2">Créer un compte</h1>
                <p class="text-muted">Rôle utilisateur : boutique et commandes. Pas d’accès au CRUD.</p>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?><div><?php echo e($error); ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Nom</label>
                    <input class="form-control" id="name" name="name" required value="<?php echo e($values['name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" required value="<?php echo e($values['email']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input class="form-control" type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password2">Confirmer</label>
                    <input class="form-control" type="password" id="password2" name="password2" required minlength="6">
                </div>
                <button class="btn btn-primary w-100" type="submit">S’inscrire</button>
                <p class="small text-center mt-3 mb-0">
                    Déjà inscrit ? <a href="<?php echo e(url('login.php')); ?>">Connexion</a>
                </p>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
