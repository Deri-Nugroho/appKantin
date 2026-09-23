<?php
require_once "config/db.php";

$errors = [];
$success = false;

// ==== Proses submit pesanan ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meja_id = (int)($_POST['meja_id'] ?? 0);
    $catatan_umum = trim($_POST['catatan_umum'] ?? '');
    $items = $_POST['qty'] ?? []; // array [menu_id => qty]

    if ($meja_id <= 0) {
        $errors[] = "Silakan pilih meja terlebih dahulu.";
    }

    // Kumpulkan item yang qty > 0
    $orderedItems = [];
    foreach ($items as $menu_id => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $orderedItems[(int)$menu_id] = $qty;
        }
    }

    if (empty($orderedItems)) {
        $errors[] = "Pilih minimal 1 menu dengan jumlah lebih dari 0.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO pesanan (meja_id, status, catatan) VALUES (?, 'Baru', ?)");
            $stmt->bind_param("is", $meja_id, $catatan_umum);
            $stmt->execute();
            $pesanan_id = $conn->insert_id;
            $stmt->close();

            $stmtDetail = $conn->prepare("INSERT INTO detail_pesanan (pesanan_id, menu_id, qty) VALUES (?, ?, ?)");
            foreach ($orderedItems as $menu_id => $qty) {
                $stmtDetail->bind_param("iii", $pesanan_id, $menu_id, $qty);
                $stmtDetail->execute();
            }
            $stmtDetail->close();

            $stmtMeja = $conn->prepare("UPDATE meja SET status='Terisi' WHERE id=?");
            $stmtMeja->bind_param("i", $meja_id);
            $stmtMeja->execute();
            $stmtMeja->close();

            $conn->commit();
            header("Location: pesan.php?success=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Gagal membuat pesanan: " . $e->getMessage();
        }
    }
}

include "header.php";

if (isset($_GET['success'])) {
    $success = true;
}

$mejaList = $conn->query("SELECT * FROM meja ORDER BY id ASC");
$menuList = $conn->query("SELECT * FROM menu WHERE status='Tersedia' ORDER BY category, name ASC");

// Kelompokkan menu per kategori untuk tampilan
$menuByCategory = [];
while ($m = $menuList->fetch_assoc()) {
    $menuByCategory[$m['category']][] = $m;
}
?>

<h4 class="mb-3">📝 Buat Pesanan Baru</h4>
<p class="text-muted">Pilih meja tamu, lalu tentukan menu dan jumlahnya.</p>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Pesanan berhasil dibuat dan dikirim ke dapur!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Pilih Meja</label>
                    <select name="meja_id" class="form-select" required>
                        <option value="">-- Pilih Meja --</option>
                        <?php $mejaList->data_seek(0); while ($m = $mejaList->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>">
                                <?php echo htmlspecialchars($m['nomor_meja']); ?>
                                (<?php echo $m['status']; ?>, kapasitas <?php echo $m['kapasitas']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Catatan Pesanan (opsional)</label>
                    <input type="text" name="catatan_umum" class="form-control" placeholder="Misal: pedas semua, tanpa es, dll.">
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($menuByCategory as $category => $items): ?>
        <h6 class="mt-4 mb-2"><?php echo htmlspecialchars($category); ?></h6>
        <div class="row g-3 mb-3">
            <?php foreach ($items as $menu): ?>
                <div class="col-md-4 col-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="fw-semibold"><?php echo htmlspecialchars($menu['name']); ?></div>
                            <div class="text-muted small mb-2">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></div>
                            <label class="form-label small mb-1">Jumlah</label>
                            <input type="number" name="qty[<?php echo $menu['id']; ?>]" class="form-control form-control-sm" min="0" value="0">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <?php if (empty($menuByCategory)): ?>
        <div class="alert alert-warning">Tidak ada menu yang berstatus "Tersedia" saat ini.</div>
    <?php endif; ?>

    <div class="mt-4 mb-5">
        <button type="submit" class="btn btn-lg" style="background-color:#c0392b;color:white;">Kirim Pesanan ke Dapur 🍳</button>
    </div>
</form>

<?php include "footer.php"; ?>
