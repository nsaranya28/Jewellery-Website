<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if category has products
    $chk = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
        flashMessage('error', 'Cannot delete category with active products.');
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        flashMessage('success', 'Category deleted successfully.');
    }
    header('Location: categories.php');
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $slug  = trim($_POST['slug'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $active= isset($_POST['is_active']) ? 1 : 0;

    if (!$slug) $slug = makeSlug($name);

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $slug, $desc, $active, $id]);
        flashMessage('success', 'Category updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $desc, $active]);
        flashMessage('success', 'Category added.');
    }
    header('Location: categories.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$adminTitle = 'Categories';
include 'includes/header.php';
?>

<div class="admin-table-head" style="margin-bottom:20px;">
  <h2 style="font-size:18px;font-weight:600;">Manage Categories</h2>
  <button class="btn btn-gold" onclick="openCategoryModal()"><i class="fas fa-plus"></i> Add Category</button>
</div>

<div class="admin-table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Slug</th>
        <th>Description</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($categories)): ?>
      <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray);">No categories found</td></tr>
      <?php endif; ?>
      <?php foreach ($categories as $c): ?>
      <tr>
        <td>#<?= $c['id'] ?></td>
        <td style="font-weight:600;"><?= safeHtml($c['name']) ?></td>
        <td><code><?= safeHtml($c['slug']) ?></code></td>
        <td style="max-width:250px;font-size:12px;color:var(--gray);"><?= safeHtml(truncate($c['description'], 60)) ?></td>
        <td><span class="badge <?= $c['is_active']?'badge-green':'badge-red' ?>"><?= $c['is_active']?'Active':'Inactive' ?></span></td>
        <td>
          <div style="display:flex;gap:6px;">
            <button class="btn btn-sm btn-outline" onclick='editCategory(<?= json_encode($c) ?>)'><i class="fas fa-edit"></i></button>
            <a href="categories.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete this category?')"><i class="fas fa-trash"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="modal-overlay" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="modalTitle">Add Category</h3>
      <button onclick="closeCategoryModal()" style="font-size:20px;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="catId" value="0"/>
      <div class="form-group">
        <label>Category Name</label>
        <input type="text" name="name" id="catName" class="form-control" required placeholder="e.g. Diamond Rings"/>
      </div>
      <div class="form-group">
        <label>Slug (optional)</label>
        <input type="text" name="slug" id="catSlug" class="form-control" placeholder="e.g. diamond-rings"/>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="catDesc" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:10px;">
        <input type="checkbox" name="is_active" id="catActive" checked/>
        <label for="catActive" style="margin-bottom:0;">Active</label>
      </div>
      <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-outline" onclick="closeCategoryModal()">Cancel</button>
        <button type="submit" name="save_category" class="btn btn-gold">Save Category</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCategoryModal() {
    document.getElementById('modalTitle').innerText = 'Add Category';
    document.getElementById('catId').value = '0';
    document.getElementById('catName').value = '';
    document.getElementById('catSlug').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catActive').checked = true;
    document.getElementById('categoryModal').style.display = 'flex';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function editCategory(cat) {
    document.getElementById('modalTitle').innerText = 'Edit Category';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catSlug').value = cat.slug;
    document.getElementById('catDesc').value = cat.description;
    document.getElementById('catActive').checked = cat.is_active == 1;
    document.getElementById('categoryModal').style.display = 'flex';
}

// Close on outside click
window.onclick = function(event) {
    let modal = document.getElementById('categoryModal');
    if (event.target == modal) closeCategoryModal();
}
</script>

<style>
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 2000;
}
.modal-content {
    background: var(--white);
    padding: 30px;
    border-radius: var(--radius-md);
    width: 100%;
    max-width: 500px;
    box-shadow: var(--shadow-lg);
}
.modal-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px; border-bottom: 1px solid var(--gray-light);
    padding-bottom: 10px;
}
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--dark); }
.form-control {
    width: 100%; padding: 10px; border: 1.5px solid var(--gray-light);
    border-radius: var(--radius-sm); outline: none; font-size: 14px;
}
.form-control:focus { border-color: var(--gold); }
</style>

<?php include 'includes/footer.php'; ?>
