<?php
require_once "config/db.php";
include "header.php";

$msg = "";
if (isset($_GET['msg'])) {
    $messages = [
        'created' => 'Menu berhasil ditambahkan.',
        'updated' => 'Menu berhasil diperbarui.',
        'deleted' => 'Menu berhasil dihapus.',
    ];
    $msg = $messages[$_GET['msg']] ?? "";
}

$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";

$sql = "SELECT * FROM menu WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if ($category !== "" && in_array($category, ['Makanan', 'Minuman', 'Cemilan'])) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Ringkasan cepat
$totalMenu = $conn->query("SELECT COUNT(*) c FROM menu")->fetch_assoc()['c'];
$totalHabis = $conn->query("SELECT COUNT(*) c FROM menu WHERE status='Habis'")->fetch_assoc()['c'];
?>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Menu</div>
                <div class="fs-3 fw-bold"><?php echo $totalMenu; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Menu Habis</div>
                <div class="fs-3 fw-bold text-danger"><?php echo $totalHabis; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Menu Tersedia</div>
                <div class="fs-3 fw-bold text-success"><?php echo $totalMenu - $totalHabis; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2 flex-wrap" method="get" role="search">
        <input type="text" name="q" class="form-control" placeholder="Cari nama menu..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="category" class="form-select">
            <option value="">Semua Kategori</option>
            <option value="Makanan" <?php if ($category === 'Makanan') echo 'selected'; ?>>Makanan</option>
            <option value="Minuman" <?php if ($category === 'Minuman') echo 'selected'; ?>>Minuman</option>
            <option value="Cemilan" <?php if ($category === 'Cemilan') echo 'selected'; ?>>Cemilan</option>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Filter</button>
    </form>
    <a href="create.php" class="btn" style="background-color:#c0392b;color:white;">+ Tambah Menu</a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle bg-white shadow-sm">
        <thead style="background-color:#c0392b;color:white;">
            <tr>
                <th>#</th>
                <th>Nama Menu</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Deskripsi</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['category']); ?></span></td>
                    <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                    <td><?php echo (int)$row['stock']; ?></td>
                    <td>
                        <?php if ($row['status'] === 'Tersedia'): ?>
                            <span class="badge bg-success">Tersedia</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Habis</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 50, "...")); ?></td>
                    <td class="text-end">
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="delete.php?id=<?php echo $row['id']; ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Yakin ingin menghapus menu ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">Tidak ada menu ditemukan.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include "footer.php"; ?>
