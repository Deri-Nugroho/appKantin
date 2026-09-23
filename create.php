<?php
require_once "config/db.php";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $status = $_POST['status'] ?? 'Tersedia';
    $description = trim($_POST['description'] ?? '');

    if ($name === '') $errors[] = "Nama menu wajib diisi.";
    if (!in_array($category, ['Makanan', 'Minuman', 'Cemilan'])) $errors[] = "Kategori tidak valid.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Harga harus berupa angka positif.";
    if (!is_numeric($stock) || $stock < 0) $errors[] = "Stok harus berupa angka positif.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO menu (name, category, price, stock, status, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $name, $category, $price, $stock, $status, $description);
        if ($stmt->execute()) {
            header("Location: index.php?msg=created");
            exit;
        } else {
            $errors[] = "Gagal menyimpan data: " . $conn->error;
        }
        $stmt->close();
    }
}

include "header.php";
?>

<h4 class="mb-3">Tambah Menu Baru</h4>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Nama Menu</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-select" required>
                        <option value="Makanan" <?php if (($_POST['category'] ?? '') === 'Makanan') echo 'selected'; ?>>Makanan</option>
                        <option value="Minuman" <?php if (($_POST['category'] ?? '') === 'Minuman') echo 'selected'; ?>>Minuman</option>
                        <option value="Cemilan" <?php if (($_POST['category'] ?? '') === 'Cemilan') echo 'selected'; ?>>Cemilan</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Tersedia" <?php if (($_POST['status'] ?? '') === 'Tersedia') echo 'selected'; ?>>Tersedia</option>
                        <option value="Habis" <?php if (($_POST['status'] ?? '') === 'Habis') echo 'selected'; ?>>Habis</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?php echo htmlspecialchars($_POST['price'] ?? '0'); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Stok</label>
                    <input type="number" min="0" name="stock" class="form-control" value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn" style="background-color:#c0392b;color:white;">Simpan</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>

<?php include "footer.php"; ?>
