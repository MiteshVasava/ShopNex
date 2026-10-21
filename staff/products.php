<?php
require_once '../includes/config.php';
require_permission('products');
$pdo = db();

/*
 * Product gallery storage is added automatically; no manual SQL is required.
 * products.image remains the primary image for compatibility with existing pages.
 */
// Some shared hosts restrict foreign-key creation or database DDL at runtime.
// Keep the main products page usable even if gallery-table creation is unavailable.
$galleryAvailable = false;
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_product_images_product_sort (product_id, sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $galleryAvailable = true;
} catch (Throwable $gallerySetupError) {
    error_log('ShopNex product gallery setup failed: ' . $gallerySetupError->getMessage());
}

function shopnex_upload_product_images(array $files): array {
    $saved = [];
    $maxFiles = 8;
    $maxBytes = 5 * 1024 * 1024;
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($files['name']) || !is_array($files['name'])) return $saved;

    $count = count($files['name']);
    if ($count > $maxFiles) {
        throw new RuntimeException('You can upload a maximum of 8 images per save.');
    }

    $uploadDir = dirname(__DIR__) . '/uploads/products';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Upload folder could not be created. Check folder permissions.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    for ($i = 0; $i < $count; $i++) {
        $error = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('One of the images failed to upload.');
        if ((int)$files['size'][$i] > $maxBytes) throw new RuntimeException('Each image must be 5 MB or smaller.');

        $tmp = $files['tmp_name'][$i] ?? '';
        if (!is_uploaded_file($tmp)) throw new RuntimeException('Invalid image upload.');
        $mime = $finfo->file($tmp);
        if (!isset($allowed[$mime]) || @getimagesize($tmp) === false) {
            throw new RuntimeException('Only JPG, PNG, WEBP, and GIF image files are allowed.');
        }

        $filename = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
        $destination = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('Could not save an uploaded image. Check uploads/products permissions.');
        }
        $saved[] = 'uploads/products/' . $filename;
    }
    return $saved;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $brand = trim($_POST['brand'] ?? '');
            $sku = trim($_POST['sku'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $price = (float)($_POST['price'] ?? 0);
            $oldPrice = (float)($_POST['old_price'] ?? 0);
            $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
            $featured = isset($_POST['featured']) ? 1 : 0;

            if ($name === '' || $brand === '' || $sku === '' || $description === '' ||
                $categoryId < 1 || $price <= 0 || $stock === false || $stock < 0) {
                throw new RuntimeException('Please complete all required fields with valid values.');
            }

            $catCheck = $pdo->prepare('SELECT id FROM categories WHERE id=? AND status=1');
            $catCheck->execute([$categoryId]);
            if (!$catCheck->fetchColumn()) throw new RuntimeException('Please select a valid category.');

            // Validate and save selected files. First upload is the primary image.
            $uploaded = shopnex_upload_product_images($_FILES['images'] ?? []);
            if (!$id && count($uploaded) < 1) {
                throw new RuntimeException('Please upload at least one product image. One image is compulsory.');
            }

            $brandQuery = $pdo->prepare('SELECT id FROM brands WHERE name=? LIMIT 1');
            $brandQuery->execute([$brand]);
            $brandRow = $brandQuery->fetch();
            if (!$brandRow) {
                $pdo->prepare('INSERT INTO brands(name) VALUES(?)')->execute([$brand]);
                $brandId = (int)$pdo->lastInsertId();
            } else {
                $brandId = (int)$brandRow['id'];
            }

            $existing = null;
            if ($id) {
                $find = $pdo->prepare('SELECT id,image FROM products WHERE id=? LIMIT 1');
                $find->execute([$id]);
                $existing = $find->fetch();
                if (!$existing) throw new RuntimeException('Product not found.');
            }

            $primaryImage = $uploaded[0] ?? ($existing['image'] ?? '');
            if ($primaryImage === '') throw new RuntimeException('Please upload at least one product image.');

            $data = [$categoryId, $brandId, $brand, $name, $sku, $description, $price,
                $oldPrice > 0 ? $oldPrice : null, $stock, $primaryImage,
                json_encode(['Brand' => $brand, 'SKU' => $sku], JSON_UNESCAPED_UNICODE), $featured];

            $pdo->beginTransaction();
            if ($id) {
                // Keep the previous primary image in the gallery when a replacement is uploaded.
                if ($galleryAvailable && !empty($uploaded)) {
                    $oldPrimary = trim((string)$existing['image']);
                    if ($oldPrimary !== '') {
                        $pdo->prepare('INSERT INTO product_images(product_id,image_path,sort_order) VALUES(?,?,?)')
                            ->execute([$id, $oldPrimary, 0]);
                    }
                }
                $q = $pdo->prepare('UPDATE products SET category_id=?,brand_id=?,brand=?,name=?,sku=?,description=?,price=?,old_price=?,stock=?,image=?,specifications=?,featured=? WHERE id=?');
                $q->execute([...$data, $id]);
                $productId = $id;
                flash('success', 'Product updated successfully.');
            } else {
                $q = $pdo->prepare('INSERT INTO products(category_id,brand_id,brand,name,sku,description,price,old_price,stock,image,specifications,featured,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,1)');
                $q->execute($data);
                $productId = (int)$pdo->lastInsertId();
                flash('success', 'Product added successfully.');
            }

            // The first upload is the main image; any further uploads become gallery images.
            $extraImages = $id ? $uploaded : array_slice($uploaded, 1);
            if ($id && !empty($uploaded)) $extraImages = array_slice($uploaded, 1);
            if ($galleryAvailable && $extraImages) {
                $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_images WHERE product_id=?');
                $sortStmt->execute([$productId]);
                $sort = (int)$sortStmt->fetchColumn();
                $imageInsert = $pdo->prepare('INSERT INTO product_images(product_id,image_path,sort_order) VALUES(?,?,?)');
                foreach ($extraImages as $path) $imageInsert->execute([$productId, $path, $sort++]);
            }

            $pdo->commit();
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
            flash('success', 'Product deleted.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (isset($uploaded) && is_array($uploaded)) {
            foreach ($uploaded as $path) {
                $absolute = dirname(__DIR__) . '/' . $path;
                if (is_file($absolute)) @unlink($absolute);
            }
        }
        flash('danger', $e instanceof PDOException && str_contains($e->getMessage(), 'Duplicate')
            ? 'That SKU already exists. Please use a unique SKU.'
            : $e->getMessage());
    }
    redirect('products.php');
}

$products = $pdo->query('SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC')->fetchAll();
$cats = $pdo->query('SELECT * FROM categories WHERE status=1 ORDER BY name')->fetchAll();
$title = 'Admin Products';
require '../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
  <div><h1 class="section-title">Products</h1><p class="text-secondary mb-0">Create, edit, feature and manage stock and product images.</p></div>
  <button class="btn btn-purple" type="button" onclick="openNewProduct()">Add Product</button>
</div>

<?php if ($message = get_flash()): ?>
  <div class="alert alert-<?=e($message[0])?> alert-dismissible fade show mt-3" role="alert">
    <?=e($message[1])?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="bg-white border rounded-4 overflow-hidden mt-3">
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Featured</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><div class="d-flex align-items-center gap-2"><img src="<?=e(preg_match('#^https?://#i', (string)$p['image']) ? $p['image'] : url($p['image']))?>" style="width:55px;height:55px;object-fit:cover;border-radius:10px" alt="">
          <div><strong><?=e($p['name'])?></strong><div class="text-secondary small"><?=e($p['brand'])?> · <?=e($p['sku'])?></div></div></div></td>
        <td><?=e($p['category_name'])?></td><td><?=money($p['price'])?></td><td><?= (int)$p['stock'] ?></td><td><?=$p['featured']?'Yes':'No'?></td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-outline-purple" type="button" onclick='editProduct(<?=json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>)'>Edit</button>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?')">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=(int)$p['id']?>">
            <button name="action" value="delete" class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="6" class="text-center text-secondary py-5">No products yet. Select Add Product to create one.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <form method="post" enctype="multipart/form-data" id="productForm">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="pid">
      <div class="modal-header"><h5 class="modal-title" id="modalTitle">Add Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="pname">Product name *</label><input id="pname" name="name" class="form-control" required maxlength="180"></div>
        <div class="col-md-6"><label class="form-label" for="pbrand">Brand *</label><input id="pbrand" name="brand" class="form-control" required maxlength="100"></div>
        <div class="col-md-6"><label class="form-label" for="psku">SKU *</label><input id="psku" name="sku" class="form-control" required maxlength="80"></div>
        <div class="col-md-6"><label class="form-label" for="pcat">Category *</label><select id="pcat" name="category_id" class="form-select" required><?php foreach ($cats as $c): ?><option value="<?=(int)$c['id']?>"><?=e($c['name'])?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label" for="pprice">Price (₹) *</label><input id="pprice" name="price" type="number" min="0.01" step="0.01" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label" for="pold">Old price (₹)</label><input id="pold" name="old_price" type="number" min="0" step="0.01" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="pstock">Stock *</label><input id="pstock" name="stock" type="number" min="0" step="1" class="form-control" required></div>
        <div class="col-12">
          <label class="form-label" for="pimages">Product images <span class="text-danger">*</span></label>
          <input id="pimages" name="images[]" type="file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
          <div class="form-text" id="imageHelp">Upload at least one image to create a product. You can select up to 8 images (JPG, PNG, WEBP, GIF; max 5 MB each). The first image is the main product image.</div>
          <div id="imagePreview" class="d-flex flex-wrap gap-2 mt-3"></div>
          <div id="currentImageNote" class="small text-secondary mt-2"></div>
        </div>
        <div class="col-12"><label class="form-label" for="pdesc">Description *</label><textarea id="pdesc" name="description" class="form-control" placeholder="Describe this product" rows="3" required></textarea></div>
        <div class="col-12 form-check ms-1"><input id="pfeatured" type="checkbox" name="featured" value="1" class="form-check-input"><label class="form-check-label" for="pfeatured">Featured product</label></div>
      </div></div>
      <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-purple" id="saveProductBtn">Save Product</button></div>
    </form>
  </div></div>
</div>

<style>
#imagePreview .preview-wrap{position:relative;width:88px;height:88px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#f8f7fc}
#imagePreview img{width:100%;height:100%;object-fit:cover}
#imagePreview .preview-label{position:absolute;bottom:0;left:0;right:0;background:#24113fdb;color:#fff;text-align:center;font-size:10px;padding:3px}
@media(max-width:576px){#productModal .modal-dialog{margin:.5rem}#productModal .modal-body{padding:1rem}}
</style>
<script>
const productModalEl = document.getElementById('productModal');
const productModal = () => bootstrap.Modal.getOrCreateInstance(productModalEl);

function resetProductForm() {
  document.getElementById('productForm').reset();
  document.getElementById('pid').value = '';
  document.getElementById('modalTitle').textContent = 'Add Product';
  document.getElementById('pimages').required = true;
  document.getElementById('imageHelp').textContent = 'Upload at least one image to create a product. You can select up to 8 images (JPG, PNG, WEBP, GIF; max 5 MB each). The first image is the main product image.';
  document.getElementById('currentImageNote').textContent = '';
  document.getElementById('imagePreview').replaceChildren();
}
function openNewProduct() { resetProductForm(); productModal().show(); }
if (new URLSearchParams(window.location.search).get('add') === '1') {
  window.addEventListener('load', openNewProduct, { once: true });
}

function normalizeProductImage(src) {
  if (!src) return '';
  if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:')) return src;
  return '<?= BASE_URL ?>/' + src.replace(/^\/+/, '');
}
function editProduct(p) {
  resetProductForm();
  document.getElementById('modalTitle').textContent = 'Edit Product';
  document.getElementById('pid').value = p.id;
  document.getElementById('pname').value = p.name || '';
  document.getElementById('pbrand').value = p.brand || '';
  document.getElementById('psku').value = p.sku || '';
  document.getElementById('pcat').value = p.category_id;
  document.getElementById('pprice').value = p.price;
  document.getElementById('pold').value = p.old_price || '';
  document.getElementById('pstock').value = p.stock;
  document.getElementById('pdesc').value = p.description || '';
  document.getElementById('pfeatured').checked = !!Number(p.featured);
  document.getElementById('pimages').required = false;
  document.getElementById('imageHelp').textContent = 'Choose images only if you want to update the main image or add more gallery images. Existing images are kept.';
  document.getElementById('currentImageNote').textContent = 'Current main image: ' + (p.image || 'none');
  if (p.image) {
    const wrap = document.createElement('div'); wrap.className = 'preview-wrap';
    const img = document.createElement('img'); img.src = normalizeProductImage(p.image); img.alt = 'Current main product image';
    const label = document.createElement('span'); label.className = 'preview-label'; label.textContent = 'Current main';
    wrap.append(img,label); document.getElementById('imagePreview').append(wrap);
  }
  productModal().show();
}

document.getElementById('pimages').addEventListener('change', function () {
  const preview = document.getElementById('imagePreview');
  preview.replaceChildren();
  const files = Array.from(this.files || []);
  if (files.length > 8) { alert('Choose up to 8 images per save.'); this.value = ''; return; }
  files.forEach((file, index) => {
    if (!file.type.startsWith('image/')) return;
    const wrap = document.createElement('div'); wrap.className = 'preview-wrap';
    const img = document.createElement('img'); img.alt = 'Selected product image ' + (index + 1);
    img.src = URL.createObjectURL(file); img.onload = () => URL.revokeObjectURL(img.src);
    const label = document.createElement('span'); label.className = 'preview-label'; label.textContent = index === 0 ? 'Main image' : 'Gallery ' + index;
    wrap.append(img,label); preview.append(wrap);
  });
});
document.getElementById('productForm').addEventListener('submit', function (event) {
  const isNew = !document.getElementById('pid').value;
  const files = document.getElementById('pimages').files;
  if (isNew && (!files || files.length < 1)) {
    event.preventDefault(); alert('Please upload at least one product image.'); return;
  }
  if (files.length > 8) { event.preventDefault(); alert('You can upload up to 8 images per save.'); }
});
if (new URLSearchParams(window.location.search).get('add') === '1') {
  document.addEventListener('DOMContentLoaded', openNewProduct);
}
</script>
<?php require '../includes/footer.php'; ?>
