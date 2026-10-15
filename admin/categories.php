<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$errors = array();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'save') {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        if ($name === '') {
            $errors[] = 'Le nom de la catégorie est obligatoire.';
        } else {
            $slug = make_slug($name, 'categorie');
            $check = db()->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
            $check->execute(array($slug, $id));
            if ($check->fetch()) {
                $slug .= '-' . ($id ? $id : time());
            }
            if ($id) {
                db()->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?')->execute(array($name, $slug, $id));
                flash_set('success', 'Catégorie mise à jour.');
            } else {
                db()->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')->execute(array($name, $slug));
                flash_set('success', 'Catégorie créée.');
            }
            redirect('admin/categories.php');
        }
    }

    if ($action === 'delete') {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        db()->prepare('UPDATE products SET category_id = NULL WHERE category_id = ?')->execute(array($id));
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute(array($id));
        flash_set('success', 'Catégorie supprimée.');
        redirect('admin/categories.php');
    }
}

$categories = get_categories();
$counts = array();
foreach (db()->query('SELECT category_id, COUNT(*) AS n FROM products WHERE category_id IS NOT NULL GROUP BY category_id') as $row) {
    $counts[(int) $row['category_id']] = (int) $row['n'];
}
$editing = null;
if ($editId) {
    foreach ($categories as $cat) {
        if ((int) $cat['id'] === $editId) {
            $editing = $cat;
            break;
        }
    }
}

$pageTitle = 'Catégories';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <?php include dirname(__DIR__) . '/includes/admin-bar.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="section-title mb-0">Catégories</h1>
        <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/index.php')); ?>">Produits</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $error): ?><div><?php echo e($error); ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <form method="post" class="card-panel">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo $editing ? (int) $editing['id'] : 0; ?>">
                <h2 class="h5 mb-3"><?php echo $editing ? 'Modifier' : 'Nouvelle catégorie'; ?></h2>
                <div class="mb-3">
                    <label class="form-label" for="name">Nom</label>
                    <input class="form-control" id="name" name="name" required value="<?php echo e($editing ? $editing['name'] : ''); ?>">
                </div>
                <button class="btn btn-primary" type="submit">Enregistrer</button>
                <?php if ($editing): ?>
                    <a class="btn btn-outline-secondary" href="<?php echo e(url('admin/categories.php')); ?>">Annuler</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="table-responsive cart-table">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Produits</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($cat['name']); ?></strong>
                                <div class="small text-muted"><?php echo e($cat['slug']); ?></div>
                            </td>
                            <td><?php echo isset($counts[(int) $cat['id']]) ? $counts[(int) $cat['id']] : 0; ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(url('admin/categories.php?edit=' . (int) $cat['id'])); ?>">Modifier</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $cat['id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$categories): ?>
                        <tr><td colspan="3" class="text-muted">Aucune catégorie.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
