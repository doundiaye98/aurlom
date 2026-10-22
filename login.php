<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect_after_login(current_user());
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim(isset($_POST['email']) ? $_POST['email'] : ''));
    $pass = trim(isset($_POST['password']) ? $_POST['password'] : '');
    $user = $email !== '' ? find_user_by_email($email) : null;
    if (!$user || !password_verify($pass, $user['password_hash'])) {
        $error = 'Email ou mot de passe incorrect.';
    } else {
        login_user($user);
        flash_set('success', 'Bonjour ' . $user['name'] . '.');
        redirect_after_login($user);
    }
}

$pageTitle = 'Connexion';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <form method="post" class="card-panel">
                <?php echo csrf_field(); ?>
                <h1 class="h4 mb-2">Connexion</h1>
                <p class="text-muted">Compte client ou administrateur. La commande se valide uniquement une fois connecté.</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" required autocomplete="username" value="<?php echo e($email); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary w-100" type="submit">Se connecter</button>
                <div class="login-divider"><span>Pas encore de compte ?</span></div>
                <a class="btn btn-outline-primary btn-create-account w-100" href="<?php echo e(url('register.php')); ?>">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                    Créer un compte
                </a>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
