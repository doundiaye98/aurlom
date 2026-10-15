<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute(array($id));
        $target = $stmt->fetch();
    } else {
        $target = false;
    }

    if (!$target) {
        flash_set('error', 'Utilisateur introuvable.');
        redirect('admin/users.php');
    }

    if ($action === 'role') {
        $role = isset($_POST['role']) && $_POST['role'] === 'admin' ? 'admin' : 'user';
        if ((int) $target['id'] === (int) $me['id'] && $role !== 'admin') {
            flash_set('error', 'Vous ne pouvez pas retirer votre propre rôle admin.');
        } elseif ($target['role'] === 'admin' && $role !== 'admin' && admin_count() <= 1) {
            flash_set('error', 'Il doit rester au moins un administrateur.');
        } else {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute(array($role, $id));
            flash_set('success', 'Rôle mis à jour.');
        }
        redirect('admin/users.php');
    }

    if ($action === 'delete') {
        if ((int) $target['id'] === (int) $me['id']) {
            flash_set('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        } elseif ($target['role'] === 'admin' && admin_count() <= 1) {
            flash_set('error', 'Impossible de supprimer le dernier administrateur.');
        } else {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute(array($id));
            flash_set('success', 'Utilisateur supprimé.');
        }
        redirect('admin/users.php');
    }
}

$users = db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY role, name')->fetchAll();
$pageTitle = 'Utilisateurs';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="section-title mb-1">Utilisateurs &amp; rôles</h1>
            <p class="text-muted mb-0">Admin : CRUD boutique. Utilisateur : achats uniquement.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/index.php')); ?>">Produits</a>
    </div>

    <div class="table-responsive cart-table">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <?php echo e($u['name']); ?>
                        <?php if ((int) $u['id'] === (int) $me['id']): ?>
                            <span class="badge text-bg-light">vous</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo e($u['email']); ?>
                        <?php if ($u['role'] === 'user'): ?>
                            <div><a class="small" href="<?php echo e(url('admin/customers.php?id=' . (int) $u['id'])); ?>">Fiche client</a></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" class="d-flex gap-2">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="role">
                            <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                            <select class="form-select form-select-sm" name="role">
                                <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>utilisateur</option>
                                <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" type="submit">OK</button>
                        </form>
                    </td>
                    <td class="text-end">
                        <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
