# Aplikasi Kantin + Sistem Pemesanan (PHP + MySQL + Bootstrap)

Aplikasi kantin dengan alur pemesanan lengkap: **pramusaji mencatat pesanan tamu di meja tertentu**, lalu **pesanan otomatis muncul di layar dapur** untuk disiapkan.

Database, tabel, dan data dummy otomatis dibuat saat file PHP pertama kali dibuka — tidak perlu import `.sql` manual.

## Alur Kerja Aplikasi

1. **Tamu duduk** di meja tertentu (meja sudah didaftarkan lewat menu **Meja**).
2. **Pramusaji** membuka halaman **Pesan (Pramusaji)**, memilih meja, memilih menu + jumlah, lalu kirim pesanan.
3. Pesanan otomatis berstatus **"Baru"** dan **langsung muncul di halaman Dapur**.
4. **Dapur** melihat pesanan masuk, klik **"Mulai Siapkan"** → status jadi **"Diproses"**.
5. Setelah selesai dimasak, klik **"Tandai Selesai"** → status jadi **"Selesai"**.
6. Di halaman **Daftar Pesanan**, pesanan yang sudah selesai bisa **ditutup** (misalnya setelah tamu bayar) — status final dan meja otomatis dikosongkan kembali agar bisa dipakai tamu berikutnya.

## Fitur

- **Manajemen Menu** — CRUD menu makanan/minuman/cemilan (kategori, harga, stok, status tersedia/habis)
- **Manajemen Meja** — tambah/hapus meja, lihat status (Kosong/Terisi), kosongkan manual
- **Pemesanan oleh Pramusaji** — pilih meja + menu + jumlah dalam satu form, bisa tambah catatan (misal "pedas semua")
- **Layar Dapur (Kitchen Display)** — menampilkan pesanan masuk secara real-time (auto-refresh 15 detik), dikelompokkan per status (Baru / Diproses), dengan tombol update status
- **Daftar Pesanan** — riwayat & status semua pesanan, total harga per pesanan, filter berdasarkan status, tombol tutup pesanan (otomatis mengosongkan meja)
- Auto-generate database `kantin_db` beserta tabel `menu`, `meja`, `pesanan`, `detail_pesanan` + dummy data saat pertama kali diakses

## Kebutuhan

- PHP 7.4+ dengan ekstensi `mysqli` aktif
- MySQL / MariaDB (bisa pakai XAMPP, Laragon, atau server sendiri)
- Koneksi internet (untuk load Bootstrap & Google Fonts dari CDN)

## Cara Menjalankan (contoh dengan XAMPP/Laragon)

1. Ekstrak folder ini ke direktori web server, misalnya:
   - XAMPP: `C:\xampp\htdocs\kantin_order_app`
   - Laragon: `C:\laragon\www\kantin_order_app`
2. Nyalakan Apache dan MySQL dari control panel XAMPP/Laragon.
3. Sesuaikan konfigurasi database di `config/db.php` jika perlu:
   ```php
   $DB_HOST = "localhost";
   $DB_USER = "root";
   $DB_PASS = "";
   $DB_NAME = "kantin_db";
   ```
4. Buka browser, akses:
   ```
   http://localhost/kantin_order_app/
   ```
5. Saat pertama kali dibuka, aplikasi otomatis membuat:
   - Database `kantin_db`
   - Tabel `menu` (12 dummy menu)
   - Tabel `meja` (6 dummy meja)
   - Tabel `pesanan` dan `detail_pesanan` (kosong, siap dipakai)

## Struktur Folder

```
kantin_order_app/
├── config/
│   └── db.php           -> koneksi + auto-generate semua tabel & dummy data
├── assets/
│   └── style.css
├── index.php             -> kelola menu (CRUD)
├── create.php / edit.php / delete.php  -> CRUD menu
├── meja.php               -> kelola meja
├── pesan.php               -> form pemesanan untuk pramusaji
├── dapur.php                -> layar dapur (kitchen display)
├── pesanan.php               -> daftar & riwayat pesanan
├── header.php / footer.php
└── README.md
```

## Tips Penggunaan di Kantin Sungguhan

- **Tablet/HP di meja pramusaji**: buka `pesan.php` di perangkat pramusaji untuk input pesanan langsung dari meja tamu.
- **Layar/monitor di dapur**: buka `dapur.php` di layar dapur — halaman ini auto-refresh setiap 15 detik sehingga koki tidak perlu klik refresh manual. Untuk update lebih instan, halaman ini bisa dikembangkan lebih lanjut memakai AJAX/WebSocket.
- **Kasir**: gunakan `pesanan.php` untuk melihat total tagihan tiap meja dan menutup pesanan setelah pembayaran.

## Pengembangan Lanjutan (opsional)
- Tambah role login (Pramusaji / Dapur / Kasir / Admin) dengan sistem autentikasi.
- Ganti auto-refresh dapur dengan **AJAX polling** atau **WebSocket** agar lebih real-time tanpa reload halaman.
- Tambah cetak struk/nota otomatis saat pesanan ditutup.
- Tambah histori laporan penjualan harian/bulanan.

## Catatan
- Untuk reset ulang seluruh data, hapus database `kantin_db` lewat phpMyAdmin, lalu buka lagi aplikasinya — seluruh tabel & dummy data akan otomatis dibuat ulang.
