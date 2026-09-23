<?php
/**
 * config/db.php
 * Koneksi ke MySQL + auto-generate database, table menu, dan dummy data
 * saat pertama kali file ini dipanggil.
 */

// ==== KONFIGURASI DATABASE (SESUAIKAN JIKA PERLU) ====
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "kantin_db";

// ==== 1. Konek ke MySQL tanpa pilih database dulu ====
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);

if ($conn->connect_error) {
    die("Koneksi ke MySQL gagal: " . $conn->connect_error);
}

// ==== 2. Buat database jika belum ada ====
$sqlCreateDb = "CREATE DATABASE IF NOT EXISTS `$DB_NAME` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!$conn->query($sqlCreateDb)) {
    die("Gagal membuat database: " . $conn->error);
}

// ==== 3. Pilih database ====
$conn->select_db($DB_NAME);

// ==== 4. Cek apakah tabel 'menu' sudah ada ====
$tableCheck = $conn->query("SHOW TABLES LIKE 'menu'");
$tableExists = ($tableCheck && $tableCheck->num_rows > 0);

if (!$tableExists) {
    // ==== 5. Buat tabel menu ====
    $sqlCreateTable = "
        CREATE TABLE menu (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            category ENUM('Makanan', 'Minuman', 'Cemilan') NOT NULL DEFAULT 'Makanan',
            price DECIMAL(12,2) NOT NULL DEFAULT 0,
            stock INT NOT NULL DEFAULT 0,
            status ENUM('Tersedia', 'Habis') NOT NULL DEFAULT 'Tersedia',
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (!$conn->query($sqlCreateTable)) {
        die("Gagal membuat tabel: " . $conn->error);
    }

    // ==== 6. Isi dummy data menu kantin ====
    $dummyData = [
        ["Nasi Goreng Spesial", "Makanan", 15000, 20, "Tersedia", "Nasi goreng dengan telur, ayam suwir, dan kerupuk."],
        ["Mie Ayam Bakso", "Makanan", 13000, 15, "Tersedia", "Mie ayam dengan tambahan bakso dan pangsit goreng."],
        ["Ayam Geprek", "Makanan", 14000, 18, "Tersedia", "Ayam crispy geprek dengan sambal bawang pedas."],
        ["Soto Ayam", "Makanan", 12000, 10, "Tersedia", "Soto ayam kuah bening dengan suwiran ayam dan soun."],
        ["Es Teh Manis", "Minuman", 4000, 40, "Tersedia", "Es teh manis segar dalam gelas besar."],
        ["Es Jeruk", "Minuman", 5000, 35, "Tersedia", "Es jeruk peras asli tanpa pemanis buatan."],
        ["Kopi Hitam", "Minuman", 5000, 25, "Tersedia", "Kopi hitam robusta khas kantin."],
        ["Jus Alpukat", "Minuman", 8000, 0, "Habis", "Jus alpukat kental dengan coklat di atasnya."],
        ["Tahu Isi", "Cemilan", 2000, 30, "Tersedia", "Tahu goreng isi sayuran, disajikan dengan cabai rawit."],
        ["Pisang Goreng", "Cemilan", 3000, 22, "Tersedia", "Pisang goreng crispy dengan taburan keju."],
        ["Batagor", "Cemilan", 10000, 12, "Tersedia", "Batagor goreng dengan bumbu kacang khas."],
        ["Risoles Mayo", "Cemilan", 3500, 0, "Habis", "Risoles isi sayuran dan mayones, digoreng crispy."],
    ];

    $stmt = $conn->prepare("INSERT INTO menu (name, category, price, stock, status, description) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($dummyData as $row) {
        $stmt->bind_param("ssdiss", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
        $stmt->execute();
    }
    $stmt->close();
}

// ==== 7. Cek & buat tabel meja ====
$tableCheck = $conn->query("SHOW TABLES LIKE 'meja'");
if (!($tableCheck && $tableCheck->num_rows > 0)) {
    $conn->query("
        CREATE TABLE meja (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nomor_meja VARCHAR(20) NOT NULL,
            kapasitas INT NOT NULL DEFAULT 4,
            status ENUM('Kosong', 'Terisi') NOT NULL DEFAULT 'Kosong'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $mejaDummy = [
        ["Meja 1", 4], ["Meja 2", 4], ["Meja 3", 2],
        ["Meja 4", 6], ["Meja 5", 2], ["Meja 6", 4],
    ];
    $stmt = $conn->prepare("INSERT INTO meja (nomor_meja, kapasitas) VALUES (?, ?)");
    foreach ($mejaDummy as $m) {
        $stmt->bind_param("si", $m[0], $m[1]);
        $stmt->execute();
    }
    $stmt->close();
}

// ==== 8. Cek & buat tabel pesanan ====
$tableCheck = $conn->query("SHOW TABLES LIKE 'pesanan'");
if (!($tableCheck && $tableCheck->num_rows > 0)) {
    $conn->query("
        CREATE TABLE pesanan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meja_id INT NOT NULL,
            status ENUM('Baru', 'Diproses', 'Selesai') NOT NULL DEFAULT 'Baru',
            catatan VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (meja_id) REFERENCES meja(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

// ==== 9. Cek & buat tabel detail_pesanan ====
$tableCheck = $conn->query("SHOW TABLES LIKE 'detail_pesanan'");
if (!($tableCheck && $tableCheck->num_rows > 0)) {
    $conn->query("
        CREATE TABLE detail_pesanan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pesanan_id INT NOT NULL,
            menu_id INT NOT NULL,
            qty INT NOT NULL DEFAULT 1,
            catatan VARCHAR(255) NULL,
            FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_id) REFERENCES menu(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}
