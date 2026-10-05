# LKPD 7 - Docker Swarm Single Node: Deployment Guide

## Tugas
Modifikasi agar ada container `mariadb:11.8` yang menjadi database untuk menyimpan data dari semua replika web service.

## Prasyarat
- Server dengan Docker terinstall (EC2 AWS atau server lain)
- Akses SSH ke server
- File aplikasi appKantin sudah ada di server

## Langkah-langkah Lengkap

### 1. Pull Docker Images
```bash
# Pull image PHP + Nginx untuk web service
docker pull trafex/php-nginx

# Pull image MariaDB untuk database
docker pull mariadb:11.8
```

### 2. Inisialisasi Docker Swarm
```bash
# Coba inisialisasi swarm
docker swarm init

# Jika muncul error tentang multiple IP (common di WSL/EC2):
# Error: could not choose an IP address to advertise since this system has multiple addresses
# Solusi: gunakan IP spesifik
docker swarm init --advertise-addr <IP_ADDRESS>

# Contoh:
docker swarm init --advertise-addr 10.100.100.105

# Output akan menampilkan token untuk join worker/manager
```

### 3. Persiapkan Direktori Aplikasi
```bash
# Buat direktori untuk aplikasi
mkdir -p /home/ubuntu/appKantin
cd /home/ubuntu/appKantin

# Upload/copy semua file appKantin ke direktori ini
# (via scp, git clone, atau manual upload)
```

### 4. Konfigurasi Database untuk Docker Swarm
Edit file `config/db.php`:
```bash
nano config/db.php
```

Ubah konfigurasi menjadi:
```php
// ===== KONFIGURASI UNTUK DOCKER SWARM =====
$DB_HOST = "db";  // Nama container MariaDB
$DB_USER = "root";
$DB_PASS = "rahasia";  // Sesuai MARIADB_ROOT_PASSWORD
$DB_NAME = "appdb";  // Sesuai MARIADB_DATABASE
```

### 5. Buat Network untuk Komunikasi
```bash
# Buat network bridge untuk komunikasi antara service dan container
docker network create app-network
```

### 6. Jalankan Container MariaDB
```bash
# Jalankan MariaDB dengan konfigurasi environment
docker run -d --name db \
  -p 3306:3306 \
  -e MARIADB_ROOT_PASSWORD=rahasia \
  -e MARIADB_DATABASE=appdb \
  -v dbdata:/var/lib/mysql \
  --network app-network \
  mariadb:11.8

# Verifikasi container berjalan
docker ps
docker logs db
```

### 7. Buat Docker Service untuk Aplikasi Web
```bash
# Buat service dengan 1 replika dulu
docker service create --name web \
  -p 8080:8080 \
  --mount type=bind,source=/home/ubuntu/appKantin,target=/var/www/html \
  --network app-network \
  trafex/php-nginx

# Cek service
docker service ls
docker service ps web
```

### 8. Scale Service ke 3 Replika
```bash
# Scale service web ke 3 replika
docker service scale web=3

# Cek status semua replika
docker service ps web
```

### 9. Verifikasi Deployment
```bash
# Cek semua container yang berjalan
docker ps

# Cek service swarm
docker service ls
docker service ps web

# Test akses dari server
curl http://localhost:8080
```

### 10. Test Load Balancing
Buka browser dan akses:
```
http://<PUBLIC_IP_EC2>:8080
```

Buka 3 tab browser dan refresh berkali-kali untuk melihat request dibagi ke replika berbeda.

Atau gunakan curl loop dari server:
```bash
for i in {1..6}; do
  curl -s http://localhost:8080/index.php | grep -o '<b>[^<]*</b>' | head -1
done
```

### 11. Cek Container ID Setiap Replika
```bash
# Lihat task service
docker service ps web

# Lihat semua container dengan format table
docker ps --format 'table {{.ID}}\t{{.Names}}'
```

## Perintah-perintah Penting

### Cek Status
```bash
# Cek service
docker service ls
docker service ps web

# Cek container
docker ps

# Cek logs
docker service logs web
docker logs db
```

### Scale Service
```bash
# Tambah replika
docker service scale web=5

# Kurangi replika
docker service scale web=2
```

### Update Service
```bash
# Update image
docker service update --image trafex/php-nginx web

# Force update (restart service)
docker service update --force web
```

### Hapus Service dan Container
```bash
# Hapus service web
docker service rm web

# Hapus container database
docker stop db
docker rm db

# Hapus volume database (hati-hati - data akan hilang!)
docker volume rm dbdata
```

### Keluar dari Swarm
```bash
# Keluar dari swarm (force)
docker swarm leave --force
```

## Arsitektur

```
┌─────────────────────────────────────────┐
│     Docker Swarm Single Node            │
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
│         │ Round-Robin LB  │             │
│         └────────┬────────┘             │
│                  │                      │
│         ┌────────▼────────┐             │
│         │   Container db  │             │
│         │   MariaDB 11.8  │             │
│         │   (Shared DB)   │             │
│         └─────────────────┘             │
│                                          │
└─────────────────────────────────────────┘
```

## Troubleshooting

### Service web tidak bisa connect ke database
```bash
# Cek apakah container db berjalan
docker ps | grep db

# Cek network
docker network inspect app-network

# Pastikan keduanya di network yang sama
docker network connect app-network db
docker service update --network-add app-network web
```

### Container tidak bisa start
```bash
# Cek logs service
docker service logs web

# Cek logs container
docker logs <CONTAINER_ID>

# Cek resource
docker stats
```

### Database connection failed di aplikasi
```bash
# Cek config/db.php
cat config/db.php

# Pastikan konfigurasi sesuai:
# - $DB_HOST = "db"
# - $DB_USER = "root"
# - $DB_PASS = "rahasia"
# - $DB_NAME = "appdb"

# Test koneksi dari dalam container web
docker exec -it <WEB_CONTAINER_ID> bash
# Di dalam container:
apt-get update && apt-get install -y mysql-client
mysql -h db -u root -prahasia appdb
```

### Port sudah digunakan
```bash
# Cek port yang sedang digunakan
sudo netstat -tulpn | grep 8080

# Atau
sudo lsof -i :8080

# Gunakan port lain jika perlu
docker service create --name web -p 9090:8080 ...
```

## Catatan Penting

1. **Shared Database**: Semua replika web (web.1, web.2, web.3) mengakses database yang sama (container db), sehingga data konsisten di semua replika.

2. **Bind Mount**: File aplikasi di `/home/ubuntu/appKantin` di-mount ke semua container web. Perubahan di direktori ini akan langsung terlihat di semua replika.

3. **Volume**: Data database disimpan di Docker volume `dbdata`, sehingga data tetap ada meskipun container dihapus/restart.

4. **Routing Mesh**: Docker Swarm routing mesh secara otomatis membagi request ke replika secara round-robin.

5. **Network Communication**: Service web dan container db harus berada di network yang sama untuk bisa berkomunikasi.

## Laporan untuk GC

Setelah selesai, buat laporan yang berisi:

1. **Screenshot Docker Swarm Status**
   - `docker service ls`
   - `docker service ps web`
   - `docker ps`

2. **Screenshot Aplikasi**
   - Halaman utama aplikasi dari browser
   - Halaman Menu (CRUD)
   - Halaman Dapur dengan pesanan

3. **Screenshot Load Balancing**
   - Hasil curl loop menunjukkan hostname/IP berbeda
   - Container ID dari masing-masing replika

4. **Penjelasan Arsitektur**
   - Gambar arsitektur Docker Swarm Single Node
   - Penjelasan bagaimana 3 replika mengakses 1 database

5. **Kesimpulan**
   - Apakah tugas berhasil diselesaikan?
   - Apa yang dipelajari tentang Docker Swarm?
   - Tantangan yang dihadapi dan solusinya
