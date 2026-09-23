<?php
require_once "config/db.php";

// Selesaikan pesanan & kosongkan meja (misalnya setelah bayar)
if (isset($_GET['tutup'])) {
    $pesanan_id = (int)$_GET['tutup'];
    $stmt = $conn->prepare("SELECT meja_id FROM pesanan WHERE id=?");
    $stmt->bind_param("i", $pesanan_id);
    $stmt->execute();
    $meja_id = $stmt->get_result()->fetch_assoc()['meja_id'] ?? null;
    $stmt->close();

    if ($meja_id) {
        $conn->query("UPDATE pesanan SET status='Selesai' WHERE id=" . (int)$pesanan_id);
        $conn->query("UPDATE meja SET status='Kosong' WHERE id=" . (int)$meja_id);
    }
    header("Location: pesanan.php?msg=closed");
    exit;
}

include "header.php";

$msg = "";
if (isset($_GET['msg']) && $_GET['msg'] === 'closed') {
    $msg = "Pesanan ditutup dan meja dikosongkan.";
}

$filterStatus = $_GET['status'] ?? '';

$sql = "
    SELECT p.id as pesanan_id, p.status, p.catatan, p.created_at, m.nomor_meja, m.id as meja_id
    FROM pesanan p
    JOIN meja m ON m.id = p.meja_id
";
if (in_array($filterStatus, ['Baru', 'Diproses', 'Selesai'])) {
    $sql .= " WHERE p.status = '" . $conn->real_escape_string($filterStatus) . "'";
}
$sql .= " ORDER BY p.created_at DESC LIMIT 50";

$pesananList = $conn->query($sql);
$pesananData = [];
while ($row = $pesananList->fetch_assoc()) {
    $pesananData[$row['pesanan_id']] = $row;
}

$detailByPesanan = [];
$totalByPesanan = [];
if (!empty($pesananData)) {
    $ids = implode(',', array_map('intval', array_keys($pesananData)));
    $sqlDetail = "
        SELECT dp.pesanan_id, dp.qty, mn.name, mn.price
        FROM detail_pesanan dp
        JOIN menu mn ON mn.id = dp.menu_id
        WHERE dp.pesanan_id IN ($ids)
    ";
    $detailResult = $conn->query($sqlDetail);
    while ($d = $detailResult->fetch_assoc()) {
        $detailByPesanan[$d['pesanan_id']][] = $d;
        $totalByPesanan[$d['pesanan_id']] = ($totalByPesanan[$d['pesanan_id']] ?? 0) + ($d['qty'] * $d['price']);
    }
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h4 class="mb-0">📋 Daftar Pesanan</h4>
    <form class="d-flex gap-2" method="get">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="Baru" <?php if ($filterStatus === 'Baru') echo 'selected'; ?>>Baru</option>
            <option value="Diproses" <?php if ($filterStatus === 'Diproses') echo 'selected'; ?>>Diproses</option>
            <option value="Selesai" <?php if ($filterStatus === 'Selesai') echo 'selected'; ?>>Selesai</option>
        </select>
    </form>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php if (empty($pesananData)): ?>
        <div class="col-12 text-muted text-center py-5">Belum ada pesanan.</div>
    <?php endif; ?>

    <?php foreach ($pesananData as $pid => $p): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold">🪑 <?php echo htmlspecialchars($p['nomor_meja']); ?></div>
                            <div class="text-muted small"><?php echo date("d/m/Y H:i", strtotime($p['created_at'])); ?></div>
                        </div>
                        <?php
                        $badgeClass = ['Baru' => 'bg-danger', 'Diproses' => 'bg-warning text-dark', 'Selesai' => 'bg-success'];
                        ?>
                        <span class="badge <?php echo $badgeClass[$p['status']]; ?>"><?php echo $p['status']; ?></span>
                    </div>
                    <ul class="ps-3 mb-2 small">
                        <?php foreach ($detailByPesanan[$pid] ?? [] as $item): ?>
                            <li><?php echo (int)$item['qty']; ?>x <?php echo htmlspecialchars($item['name']); ?>
                                <span class="text-muted">(Rp <?php echo number_format($item['price'] * $item['qty'], 0, ',', '.'); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (!empty($p['catatan'])): ?>
                        <div class="small text-muted mb-2">📝 <?php echo htmlspecialchars($p['catatan']); ?></div>
                    <?php endif; ?>
                    <div class="fw-bold mb-2">Total: Rp <?php echo number_format($totalByPesanan[$pid] ?? 0, 0, ',', '.'); ?></div>

                    <?php if ($p['status'] !== 'Selesai'): ?>
                        <a href="pesanan.php?tutup=<?php echo $pid; ?>"
                           class="btn btn-sm btn-outline-success w-100"
                           onclick="return confirm('Tutup pesanan ini dan kosongkan meja?');">
                           Tutup / Sudah Dibayar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include "footer.php"; ?>
