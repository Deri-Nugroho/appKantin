<?php
require_once "config/db.php";

// Update status pesanan (dari dapur)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pesanan_id = (int)$_POST['pesanan_id'];
    $newStatus = $_POST['new_status'];
    if (in_array($newStatus, ['Diproses', 'Selesai'])) {
        $stmt = $conn->prepare("UPDATE pesanan SET status=? WHERE id=?");
        $stmt->bind_param("si", $newStatus, $pesanan_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dapur.php");
    exit;
}

include "header.php";

// Ambil pesanan yang masih Baru / Diproses beserta detail menu
$sql = "
    SELECT p.id as pesanan_id, p.status, p.catatan, p.created_at,
           m.nomor_meja
    FROM pesanan p
    JOIN meja m ON m.id = p.meja_id
    WHERE p.status IN ('Baru', 'Diproses')
    ORDER BY p.created_at ASC
";
$pesananList = $conn->query($sql);

$pesananData = [];
while ($row = $pesananList->fetch_assoc()) {
    $pesananData[$row['pesanan_id']] = $row;
}

// Ambil semua detail item untuk pesanan-pesanan tsb
$detailByPesanan = [];
if (!empty($pesananData)) {
    $ids = implode(',', array_map('intval', array_keys($pesananData)));
    $sqlDetail = "
        SELECT dp.pesanan_id, dp.qty, mn.name
        FROM detail_pesanan dp
        JOIN menu mn ON mn.id = dp.menu_id
        WHERE dp.pesanan_id IN ($ids)
    ";
    $detailResult = $conn->query($sqlDetail);
    while ($d = $detailResult->fetch_assoc()) {
        $detailByPesanan[$d['pesanan_id']][] = $d;
    }
}
?>

<meta http-equiv="refresh" content="15">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">👨‍🍳 Tampilan Dapur</h4>
    <span class="text-muted small">Halaman refresh otomatis tiap 15 detik</span>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <h6 class="text-danger">🔴 Pesanan Baru</h6>
        <div class="row g-3">
        <?php $adaBaru = false; foreach ($pesananData as $pid => $p): if ($p['status'] !== 'Baru') continue; $adaBaru = true; ?>
            <div class="col-12">
                <div class="card shadow-sm border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold fs-5">🪑 <?php echo htmlspecialchars($p['nomor_meja']); ?></div>
                                <div class="text-muted small"><?php echo date("H:i", strtotime($p['created_at'])); ?></div>
                            </div>
                            <span class="badge bg-danger">Baru</span>
                        </div>
                        <ul class="mb-2 ps-3">
                            <?php foreach ($detailByPesanan[$pid] ?? [] as $item): ?>
                                <li><?php echo (int)$item['qty']; ?>x <?php echo htmlspecialchars($item['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!empty($p['catatan'])): ?>
                            <div class="alert alert-warning py-1 px-2 small mb-2">Catatan: <?php echo htmlspecialchars($p['catatan']); ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="pesanan_id" value="<?php echo $pid; ?>">
                            <input type="hidden" name="new_status" value="Diproses">
                            <button type="submit" name="update_status" class="btn btn-sm btn-warning w-100">Mulai Siapkan</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$adaBaru): ?>
            <div class="col-12 text-muted text-center py-4">Belum ada pesanan baru.</div>
        <?php endif; ?>
        </div>
    </div>

    <div class="col-md-6">
        <h6 class="text-warning">🟡 Sedang Diproses</h6>
        <div class="row g-3">
        <?php $adaProses = false; foreach ($pesananData as $pid => $p): if ($p['status'] !== 'Diproses') continue; $adaProses = true; ?>
            <div class="col-12">
                <div class="card shadow-sm border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold fs-5">🪑 <?php echo htmlspecialchars($p['nomor_meja']); ?></div>
                                <div class="text-muted small"><?php echo date("H:i", strtotime($p['created_at'])); ?></div>
                            </div>
                            <span class="badge bg-warning text-dark">Diproses</span>
                        </div>
                        <ul class="mb-2 ps-3">
                            <?php foreach ($detailByPesanan[$pid] ?? [] as $item): ?>
                                <li><?php echo (int)$item['qty']; ?>x <?php echo htmlspecialchars($item['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!empty($p['catatan'])): ?>
                            <div class="alert alert-warning py-1 px-2 small mb-2">Catatan: <?php echo htmlspecialchars($p['catatan']); ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="pesanan_id" value="<?php echo $pid; ?>">
                            <input type="hidden" name="new_status" value="Selesai">
                            <button type="submit" name="update_status" class="btn btn-sm btn-success w-100">Tandai Selesai ✅</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$adaProses): ?>
            <div class="col-12 text-muted text-center py-4">Tidak ada pesanan yang sedang diproses.</div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
