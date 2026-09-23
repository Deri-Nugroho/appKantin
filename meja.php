<?php
require_once "config/db.php";

$errors = [];

// Tambah meja baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meja'])) {
    $nomor = trim($_POST['nomor_meja'] ?? '');
    $kapasitas = $_POST['kapasitas'] ?? '';

    if ($nomor === '') $errors[] = "Nomor meja wajib diisi.";
    if (!is_numeric($kapasitas) || $kapasitas < 1) $errors[] = "Kapasitas harus angka lebih dari 0.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO meja (nomor_meja, kapasitas) VALUES (?, ?)");
        $stmt->bind_param("si", $nomor, $kapasitas);
        $stmt->execute();
        $stmt->close();
        header("Location: meja.php?msg=created");
        exit;
    }
}

// Kosongkan meja manual
if (isset($_GET['kosongkan'])) {
    $id = (int)$_GET['kosongkan'];
    $stmt = $conn->prepare("UPDATE meja SET status='Kosong' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: meja.php?msg=updated");
    exit;
}

// Hapus meja
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM meja WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: meja.php?msg=deleted");
    exit;
}

include "header.php";

$msg = "";
if (isset($_GET['msg'])) {
    $messages = [
        'created' => 'Meja berhasil ditambahkan.',
        'updated' => 'Status meja diperbarui.',
        'deleted' => 'Meja berhasil dihapus.',
    ];
    $msg = $messages[$_GET['msg']] ?? "";
}

$mejaList = $conn->query("SELECT * FROM meja ORDER BY id ASC");
?>

<h4 class="mb-3">Kelola Meja</h4>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo htmlspecialchars($msg); ?>
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

<div class="row">
    <div class="col-md-8">
        <div class="row g-3 mb-4">
            <?php $mejaList->data_seek(0); while ($m = $mejaList->fetch_assoc()): ?>
                <div class="col-md-4 col-6">
                    <div class="card shadow-sm text-center border-0 h-100">
                        <div class="card-body">
                            <div class="fs-4 mb-1">🪑</div>
                            <div class="fw-bold"><?php echo htmlspecialchars($m['nomor_meja']); ?></div>
                            <div class="text-muted small mb-2">Kapasitas <?php echo (int)$m['kapasitas']; ?> orang</div>
                            <?php if ($m['status'] === 'Kosong'): ?>
                                <span class="badge bg-success mb-2">Kosong</span>
                            <?php else: ?>
                                <span class="badge bg-danger mb-2">Terisi</span>
                            <?php endif; ?>
                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                <?php if ($m['status'] === 'Terisi'): ?>
                                    <a href="meja.php?kosongkan=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-secondary">Kosongkan</a>
                                <?php endif; ?>
                                <a href="meja.php?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus meja ini?');">Hapus</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="mb-3">Tambah Meja Baru</h6>
                <form method="post">
                    <div class="mb-2">
                        <label class="form-label">Nomor / Nama Meja</label>
                        <input type="text" name="nomor_meja" class="form-control" placeholder="Misal: Meja 7" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" name="kapasitas" class="form-control" value="4" min="1" required>
                    </div>
                    <button type="submit" name="add_meja" class="btn w-100" style="background-color:#c0392b;color:white;">Tambah Meja</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
