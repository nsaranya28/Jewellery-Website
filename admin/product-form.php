<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
  $stmt->execute([$id]);
  $product = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();

// ── Handle uploads directory ──
$uploadDir = __DIR__ . '/../uploads/products/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name']);
  $slug    = makeSlug($name);
  $catId   = (int)$_POST['category_id'];
  $desc    = trim($_POST['description'] ?? '');
  $price   = (float)$_POST['price'];
  $dPrice  = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null;
  $mat     = trim($_POST['material'] ?? '');
  $weight  = (float)$_POST['weight'];
  $purity  = trim($_POST['purity'] ?? '');
  $stock   = (int)$_POST['stock'];
  $feat    = isset($_POST['is_featured']) ? 1 : 0;

  // ── Handle image uploads ──
  $imageFields = ['image1', 'image2', 'image3'];
  $images = [];
  foreach ($imageFields as $field) {
    // Keep existing image if no new upload and not removed
    $existing = $product[$field] ?? null;
    $removed  = !empty($_POST['remove_' . $field]);

    if ($removed) {
      // Delete old file if it exists
      if ($existing && file_exists($uploadDir . $existing)) {
        unlink($uploadDir . $existing);
      }
      $images[$field] = null;
    } elseif (!empty($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
      // Validate file type
      $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
      $finfo   = finfo_open(FILEINFO_MIME_TYPE);
      $mime    = finfo_file($finfo, $_FILES[$field]['tmp_name']);
      finfo_close($finfo);

      if (in_array($mime, $allowed)) {
        // Delete old file if replacing
        if ($existing && file_exists($uploadDir . $existing)) {
          unlink($uploadDir . $existing);
        }
        $ext      = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $filename = $slug . '-' . $field . '-' . time() . '.' . strtolower($ext);
        move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $filename);
        $images[$field] = $filename;
      } else {
        $images[$field] = $existing;
      }
    } else {
      $images[$field] = $existing;
    }
  }

  if ($product) {
    $pdo->prepare("UPDATE products SET category_id=?,name=?,slug=?,description=?,price=?,discount_price=?,material=?,weight=?,purity=?,stock=?,is_featured=?,image1=?,image2=?,image3=? WHERE id=?")
        ->execute([$catId,$name,$slug,$desc,$price,$dPrice,$mat,$weight,$purity,$stock,$feat,$images['image1'],$images['image2'],$images['image3'],$product['id']]);
    flashMessage('success', 'Product updated!');
  } else {
    // Check slug unique
    $chk = $pdo->prepare("SELECT id FROM products WHERE slug=?"); $chk->execute([$slug]);
    if ($chk->fetch()) $slug .= '-' . time();
    $pdo->prepare("INSERT INTO products (category_id,name,slug,description,price,discount_price,material,weight,purity,stock,is_featured,image1,image2,image3) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$catId,$name,$slug,$desc,$price,$dPrice,$mat,$weight,$purity,$stock,$feat,$images['image1'],$images['image2'],$images['image3']]);
    flashMessage('success', 'Product added!');
  }
  header('Location: products.php'); exit;
}

$adminTitle = ($product ? 'Edit' : 'Add') . ' Product';
include 'includes/header.php';
?>

<div style="max-width:700px;">
  <a href="products.php" style="font-size:13px;color:var(--gray);display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;"><i class="fas fa-arrow-left"></i> Back to Products</a>

  <div class="admin-form">
    <h2 style="font-size:20px;font-weight:700;color:var(--charcoal);margin-bottom:24px;"><?= $product ? '✏️ Edit' : '➕ Add New' ?> Product</h2>
    <form method="POST" enctype="multipart/form-data">

      <!-- ── Product Images ── -->
      <div class="form-group">
        <label style="margin-bottom:12px;font-size:13px;">Product Images <span style="font-weight:400;color:#999;text-transform:none;letter-spacing:0;">(up to 3 — first image is the main display)</span></label>
        <div class="image-upload-grid">
          <?php for ($i = 1; $i <= 3; $i++):
            $field = 'image' . $i;
            $existing = $product[$field] ?? null;
            $previewUrl = $existing ? productImage($existing) : '';
            $label = $i === 1 ? 'Main Image' : 'Image ' . $i;
          ?>
          <div class="image-upload-card<?= $existing ? ' has-image' : '' ?>" id="card-<?= $field ?>" data-field="<?= $field ?>">
            <input type="file" name="<?= $field ?>" id="input-<?= $field ?>" accept="image/*" style="display:none;" onchange="previewImage(this, '<?= $field ?>')"/>
            <input type="hidden" name="remove_<?= $field ?>" id="remove-<?= $field ?>" value="0"/>

            <!-- Empty state -->
            <div class="upload-placeholder" id="placeholder-<?= $field ?>" style="<?= $existing ? 'display:none;' : '' ?>">
              <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
              <div class="upload-label"><?= $label ?></div>
              <div class="upload-hint">Click or drag image here</div>
              <div class="upload-formats">JPG, PNG, WebP</div>
            </div>

            <!-- Preview state -->
            <div class="upload-preview" id="preview-<?= $field ?>" style="<?= $existing ? '' : 'display:none;' ?>">
              <img src="<?= $previewUrl ?>" alt="Preview" id="img-<?= $field ?>"/>
              <div class="preview-overlay">
                <button type="button" class="preview-btn change-btn" onclick="document.getElementById('input-<?= $field ?>').click();" title="Change image">
                  <i class="fas fa-camera"></i>
                </button>
                <button type="button" class="preview-btn remove-btn" onclick="removeImage('<?= $field ?>')" title="Remove image">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
              <span class="image-badge"><?= $label ?></span>
            </div>
          </div>
          <?php endfor; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" required value="<?= safeHtml($product['name'] ?? '') ?>" placeholder="e.g. Traditional Lakshmi Kolusu"/>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '')==$c['id']?'selected':'' ?>><?= safeHtml($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4" placeholder="Describe this jewellery piece…"><?= safeHtml($product['description'] ?? '') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Price (₹) *</label>
          <input type="number" name="price" step="0.01" required value="<?= $product['price'] ?? '' ?>" placeholder="0.00"/>
        </div>
        <div class="form-group">
          <label>Discount Price (₹)</label>
          <input type="number" name="discount_price" step="0.01" value="<?= $product['discount_price'] ?? '' ?>" placeholder="Leave blank if no discount"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Material</label>
          <input type="text" name="material" value="<?= safeHtml($product['material'] ?? '') ?>" placeholder="Gold, Silver, White Gold…"/>
        </div>
        <div class="form-group">
          <label>Purity</label>
          <input type="text" name="purity" value="<?= safeHtml($product['purity'] ?? '') ?>" placeholder="22K, 18K, 92.5%…"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Weight (grams)</label>
          <input type="number" name="weight" step="0.01" value="<?= $product['weight'] ?? '' ?>"/>
        </div>
        <div class="form-group">
          <label>Stock Quantity</label>
          <input type="number" name="stock" value="<?= $product['stock'] ?? 10 ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;">
          <input type="checkbox" name="is_featured" value="1" style="width:16px;height:16px;accent-color:var(--gold);" <?= ($product['is_featured'] ?? 0)?'checked':'' ?>/>
          Mark as Featured Product (shown on homepage)
        </label>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> <?= $product ? 'Update Product' : 'Add Product' ?></button>
        <a href="products.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<style>
/* ── Image Upload Grid ── */
.image-upload-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}
@media (max-width: 600px) {
  .image-upload-grid { grid-template-columns: 1fr 1fr; }
}

.image-upload-card {
  position: relative;
  aspect-ratio: 1 / 1;
  border: 2px dashed #d4d0c8;
  border-radius: 14px;
  background: #FAFAF7;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.3s ease;
}
.image-upload-card:hover {
  border-color: var(--gold);
  background: rgba(201,162,39,0.04);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(201,162,39,0.12);
}
.image-upload-card.has-image {
  border-style: solid;
  border-color: var(--gold-light);
}
.image-upload-card.drag-over {
  border-color: var(--gold);
  background: rgba(201,162,39,0.08);
  box-shadow: 0 0 0 4px rgba(201,162,39,0.15);
  transform: scale(1.02);
}

/* Empty state */
.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 6px;
  padding: 16px;
  text-align: center;
}
.upload-icon {
  width: 48px; height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(201,162,39,0.12), rgba(201,162,39,0.05));
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 4px;
  transition: transform 0.3s;
}
.image-upload-card:hover .upload-icon { transform: scale(1.1); }
.upload-icon i { font-size: 20px; color: var(--gold); }
.upload-label { font-size: 12px; font-weight: 700; color: var(--charcoal); }
.upload-hint { font-size: 11px; color: var(--gray); }
.upload-formats { font-size: 10px; color: #bbb; margin-top: 2px; }

/* Preview state */
.upload-preview {
  position: relative;
  width: 100%; height: 100%;
}
.upload-preview img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.preview-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  opacity: 0;
  transition: opacity 0.3s;
}
.image-upload-card:hover .preview-overlay { opacity: 1; }
.preview-btn {
  width: 40px; height: 40px;
  border-radius: 50%;
  border: none;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  backdrop-filter: blur(6px);
}
.change-btn {
  background: rgba(255,255,255,0.9);
  color: var(--charcoal);
}
.change-btn:hover {
  background: var(--gold);
  color: #fff;
  transform: scale(1.1);
}
.remove-btn {
  background: rgba(231,76,60,0.85);
  color: #fff;
}
.remove-btn:hover {
  background: var(--red);
  transform: scale(1.1);
}
.image-badge {
  position: absolute;
  bottom: 8px; left: 8px;
  background: rgba(0,0,0,0.6);
  color: #fff;
  font-size: 10px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  backdrop-filter: blur(4px);
  letter-spacing: 0.5px;
}
</style>

<script>
// ── Click-to-upload ──
document.querySelectorAll('.image-upload-card').forEach(card => {
  card.addEventListener('click', function(e) {
    // Don't trigger if clicking buttons
    if (e.target.closest('.preview-btn')) return;
    const field = this.dataset.field;
    document.getElementById('input-' + field).click();
  });

  // ── Drag & Drop ──
  card.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('drag-over');
  });
  card.addEventListener('dragleave', function() {
    this.classList.remove('drag-over');
  });
  card.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    const field = this.dataset.field;
    const files = e.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
      const input = document.getElementById('input-' + field);
      // Create a new DataTransfer to set files on input
      const dt = new DataTransfer();
      dt.items.add(files[0]);
      input.files = dt.files;
      previewImage(input, field);
    }
  });
});

// ── Preview uploaded image ──
function previewImage(input, field) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('img-' + field).src = e.target.result;
      document.getElementById('placeholder-' + field).style.display = 'none';
      document.getElementById('preview-' + field).style.display = 'block';
      document.getElementById('card-' + field).classList.add('has-image');
      document.getElementById('remove-' + field).value = '0';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// ── Remove image ──
function removeImage(field) {
  document.getElementById('img-' + field).src = '';
  document.getElementById('placeholder-' + field).style.display = '';
  document.getElementById('preview-' + field).style.display = 'none';
  document.getElementById('card-' + field).classList.remove('has-image');
  document.getElementById('input-' + field).value = '';
  document.getElementById('remove-' + field).value = '1';
}
</script>

<?php include 'includes/footer.php'; ?>
