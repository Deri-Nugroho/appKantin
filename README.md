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

---

# Docker Swarm Single Node Deployment

## Overview
Dokumentasi ini menjelaskan cara men-deploy aplikasi appKantin menggunakan Docker Swarm dengan konfigurasi single node (1 manager yang juga berfungsi sebagai worker). Database MariaDB akan dijalankan sebagai container terpisah yang dapat diakses oleh semua replika web service.

## Prasyarat
- Docker dan Docker Swarm sudah terinstall
- Akses ke server (EC2 AWS atau server lain)
- Akses internet untuk pull image

## Langkah-langkah Deployment

### 1. Pull Docker Images
```bash
# Pull image web server (PHP + Nginx)
docker pull trafex/php-nginx

# Pull image MariaDB
docker pull mariadb:11.8
```

### 2. Inisialisasi Docker Swarm
```bash
# Cek jika ada multiple IP (sering terjadi di WSL)
docker swarm init

# Jika muncul error multiple IP, gunakan IP spesifik:
# Error: could not choose an IP address to advertise since this system has multiple addresses
# Solusi:
docker swarm init --advertise-addr <IP_ADDRESS>
# Contoh:
docker swarm init --advertise-addr 10.100.100.105
```

### 3. Persiapkan Direktori Aplikasi
```bash
# Buat direktori untuk aplikasi
mkdir -p /home/ubuntu/appKantin
cd /home/ubuntu/appKantin

# Copy semua file appKantin ke direktori ini
# (upload via scp, git clone, atau copy dari local)
```

### 4. Jalankan Container MariaDB
```bash
# Jalankan MariaDB dengan konfigurasi environment
docker run -d --name db \
  -p 3306:3306 \
  -e MARIADB_ROOT_PASSWORD=rahasia \
  -e MARIADB_DATABASE=appdb \
  -v dbdata:/var/lib/mysql \
  mariadb:11.8

# Verifikasi container berjalan
docker ps
docker logs db
```

### 5. Konfigurasi Database di aplikasi
Edit file `config/db.php` untuk menghubungkan ke MariaDB container:

```php
// config/db.php
$DB_HOST = "db";  // Nama container MariaDB
$DB_USER = "root";
$DB_PASS = "rahasia";
$DB_NAME = "appdb";  // Sesuai dengan MARIADB_DATABASE
```

### 6. Buat Docker Service untuk Aplikasi Web
```bash
# Buat service dengan 3 replika
docker service create --name web \
  -p 8080:8080 \
  --mount type=bind,source=/home/ubuntu/appKantin,target=/var/www/html \
  --network overlay  # Otomatis terhubung ke swarm network
  trafex/php-nginx

# Pastikan service web dapat mengakses container db
# Jika perlu, connect ke network yang sama:
docker network connect $(docker network ls --filter name=ingress --format '{{.Name}}') db
```

**Catatan Penting:** Untuk menghubungkan service swarm dengan standalone container (db), kita perlu:
```bash
# Buat network bridge untuk komunikasi
docker network create app-network

# Connect container db ke network
docker network connect app-network db

# Update service web untuk menggunakan network ini
docker service update --network-add app-network web
```

### 7. Scale Service ke 3 Replika
```bash
# Scale service web ke 3 replika
docker service scale web=3

# Cek status service
docker service ls
docker service ps web
```

### 8. Verifikasi Deployment
```bash
# Cek semua container yang berjalan
docker ps

# Cek service swarm
docker service ls
docker service ps web

# Test akses aplikasi dari dalam container
docker exec -it <CONTAINER_ID> curl http://localhost:8080
```

### 9. Akses Aplikasi
Buka browser dan akses:
```
http://<PUBLIC_IP_EC2>:8080
```

Atau dari server sendiri:
```bash
curl http://localhost:8080
```

### 10. Test Load Balancing (Round-Robin)
Buka 3 tab browser dan refresh berkali-kali untuk melihat bahwa request dibagi ke replika berbeda.

Atau gunakan curl loop:
```bash
for i in {1..6}; do
  curl -s http://localhost:8080/index.php | grep -o '<b>[^<]*</b>' | head -1
done
```

### 11. Cek Container ID Setiap Replika
```bash
# Lihat task service
docker service ps web

# Lihat semua container dengan format
docker ps --format 'table {{.ID}}\t{{.Names}}'
```

## Perintah Penting Docker Swarm

### Manajemen Service
```bash
# Melihat semua service
docker service ls

# Melihat detail service
docker service inspect web

# Melihat task/container dalam service
docker service ps web

# Scale service (ubah jumlah replika)
docker service scale web=5

# Update image service
docker service update --image trafex/php-nginx web

# Hapus service
docker service rm web
```

### Manajemen Container Database
```bash
# Masuk ke container MariaDB
docker exec -it db bash

# Masuk ke MySQL prompt
docker exec -it db mysql -uroot -prahasia

# Backup database
docker exec db mysqldump -uroot -prahasia appdb > backup.sql

# Restore database
docker exec -i db mysql -uroot -prahasia appdb < backup.sql

# Stop container
docker stop db

# Start container
docker start db

# Hapus container (hati-hati!)
docker rm -f db
```

### Manajemen Swarm
```bash
# Lihat node dalam swarm
docker node ls

# Lihat token untuk join worker
docker swarm join-token worker

# Lihat token untuk join manager
docker swarm join-token manager

# Keluar dari swarm (force)
docker swarm leave --force
```

## Troubleshooting

### Service web tidak bisa connect ke database
```bash
# Cek network
docker network ls
docker network inspect app-network

# Pastikan container db dan service web di network yang sama
docker network connect app-network db
docker service update --network-add app-network web
```

### Container tidak bisa start
```bash
# Cek logs
docker logs <CONTAINER_ID>
docker service logs web

# Cek resource
docker stats
```

### Port sudah digunakan
```bash
# Cek port yang sedang digunakan
netstat -tulpn | grep 8080

# Gunakan port lain jika perlu
docker service create --name web -p 9090:8080 ...
```

### Database connection failed
```bash
# Cek config/db.php
nano config/db.php

# Pastikan:
# - $DB_HOST = "db" (nama container)
# - $DB_USER = "root"
# - $DB_PASS = "rahasia" (sesuai MARIADB_ROOT_PASSWORD)
# - $DB_NAME = "appdb" (sesuai MARIADB_DATABASE)
```

## Arsitektur Docker Swarm Single Node

```
┌─────────────────────────────────────────┐
│           Docker Swarm Node             │
│  (Manager + Worker dalam 1 mesin)       │
├─────────────────────────────────────────┤
│                                          │
│  ┌──────────────┐  ┌──────────────┐    │
│  │   Web.1      │  │   Web.2      │    │
│  │ (Replica 1)  │  │ (Replica 2)  │    │
│  │ PHP+Nginx    │  │ PHP+Nginx    │    │
│  └──────┬───────┘  └──────┬───────┘    │
│         │                  │            │
│         └────────┬─────────┘            │
│                  │                      │
│         ┌────────▼────────┐             │
│         │ Routing Mesh    │             │
│         │ (Port 8080)     │             │
│         └────────┬────────┘             │
│                  │                      │
│         ┌────────▼────────┐             │
│         │   Container db  │             │
│         │   MariaDB 11.8  │             │
│         │   Port 3306     │             │
│         └─────────────────┘             │
│                                          │
└─────────────────────────────────────────┘
```

## Keuntungan Docker Swarm
- **Load Balancing Otomatis**: Routing mesh membagi request ke replika secara round-robin
- **Scalability**: Mudah menambah/mengurangi replika dengan satu perintah
- **High Availability**: Jika satu replika gagal, Swarm otomatis restart
- **Simpel Konfigurasi**: Single node cukup untuk belajar dan development
- **Mudah Upgrade**: Rolling update dengan `docker service update`

## Pengembangan Lanjutan
- Tambah monitoring dengan Prometheus + Grafana
- Gunakan Docker Compose untuk konfigurasi lebih kompleks
- Implementasikan multi-node swarm (1 manager + 2+ worker)
- Tambah reverse proxy (Traefik/Nginx) untuk SSL termination
- Gunakan secrets management untuk password database
