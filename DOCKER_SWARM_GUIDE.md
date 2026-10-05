# LKPD 7 - Docker Swarm Single Node: Deployment Guide (Untuk Docker Snap)

## Tugas
Modifikasi agar ada container `mariadb:11.8` yang menjadi database untuk menyimpan data dari semua replika web service.

## ⚠️ PENTING: Cek Jenis Instalasi Docker

Sebelum memulai, cek jenis instalasi Docker Anda:
```bash
sudo docker info | grep "Docker Root Dir"
```

### Jika Output: `/var/snap/docker/common/var-lib-docker`
Docker diinstall via **Snap** (Ubuntu Core/EC2 AWS). Ada keterbatasan:
- ❌ Overlay network untuk Swarm service akan menyebabkan error "read-only file system"
- ✅ Solusi: Gunakan host network untuk database dan ingress network default untuk web service
- **Ikuti panduan ini**

### Jika Output: `/var/lib/docker`
Docker diinstall via apt/manual (bukan Snap). Anda bisa menggunakan overlay network standar.

**Panduan ini dibuat khusus untuk Docker Snap (Ubuntu Core/EC2 AWS).**

## Prasyarat
- Server dengan Docker terinstall (EC2 AWS atau server lain)
- Akses SSH ke server
- File aplikasi appKantin sudah ada di server

## Penting: Setup Permission Docker

Sebelum memulai, Anda perlu memberikan akses Docker ke user Anda. Jika tidak, akan muncul error:
```
permission denied while trying to connect to the docker API at unix:///var/run/docker.sock
```

### Solusi 1: Gunakan sudo (Sementara - Disarankan untuk Docker Snap)
Jalankan semua perintah Docker dengan sudo:
```bash
sudo docker pull trafex/php-nginx
sudo docker swarm init
# dst...
```

### Solusi 2: Tambah User ke Docker Group (Permanen)
```bash
# Tambah user ubuntu ke docker group
sudo usermod -aG docker ubuntu

# Logout dan login kembali ATAU jalankan perintah ini:
newgrp docker

# Verifikasi - sekarang bisa tanpa sudo
docker ps
```

**Catatan**: Di Docker Snap, group docker mungkin belum ada. Jika error, gunakan solusi 1 (sudo).

## Langkah-langkah Lengkap (Untuk Docker Snap)

### 1. Pull Docker Images
```bash
sudo docker pull trafex/php-nginx
sudo docker pull mariadb:11.8
```

### 2. Inisialisasi Docker Swarm
```bash
# Coba inisialisasi swarm
sudo docker swarm init

# Jika muncul error tentang multiple IP (common di EC2):
# Error: could not choose an IP address to advertise since this system has multiple addresses
# Solusi: gunakan IP spesifik (gunakan Public IP atau Private IP)
sudo docker swarm init --advertise-addr 54.83.79.145
# atau gunakan private IP:
# sudo docker swarm init --advertise-addr 172.31.28.207

# Output akan menampilkan token untuk join worker/manager
```

### 3. Persiapkan Direktori Aplikasi
```bash
# Buat direktori untuk aplikasi
mkdir -p /home/ubuntu/appKantin
cd /home/ubuntu/appKantin

# Upload/copy semua file appKantin ke direktori ini
# Via git clone:
git clone https://github.com/Deri-Nugroho/appKantin.git temp
mv temp/* .
mv temp/.gitignore . 2>/dev/null || true
rm -rf temp

# ATAU via scp dari local:
# scp -r /path/to/appKantin/* ubuntu@<IP>:/home/ubuntu/appKantin/
```

### 4. Cek Docker Bridge Gateway IP
```bash
# Cari IP gateway bridge network (IP ini akan digunakan untuk koneksi ke database)
sudo docker network inspect bridge --format '{{range .IPAM.Config}}{{.Gateway}}{{end}}'
# Output biasanya: 172.17.0.1
# CATAT IP INI!
```

### 5. Konfigurasi Database untuk Docker Swarm
Edit file `config/db.php`:
```bash
nano config/db.php
```

Ubah konfigurasi menjadi (GANTI IP sesuai output langkah 4):
```php
// ===== KONFIGURASI UNTUK DOCKER SWARM =====
$DB_HOST = "172.17.0.1";  // IP bridge gateway (dari langkah 4)
$DB_USER = "root";
$DB_PASS = "rahasia";  // Sesuai MARIADB_ROOT_PASSWORD
$DB_NAME = "appdb";  // Sesuai MARIADB_DATABASE
```

Simpan dengan: `Ctrl+X`, lalu `Y`, lalu `Enter`

### 6. Jalankan Container MariaDB dengan Host Network
```bash
# Jalankan MariaDB dengan host network (PENTING: gunakan --network host)
sudo docker run -d --name db \
  --network host \
  -e MARIADB_ROOT_PASSWORD=rahasia \
  -e MARIADB_DATABASE=appdb \
  -v dbdata:/var/lib/mysql \
  mariadb:11.8

# Verifikasi container berjalan
sudo docker ps
sudo docker logs db
```

### 7. Buat Docker Service untuk Aplikasi Web
```bash
# Buat service dengan 1 replika dulu (TANPA network custom - gunakan default ingress)
# JANGAN gunakan --network app-network atau --network overlay!
sudo docker service create --name web \
  -p 8080:8080 \
  --mount type=bind,source=/home/ubuntu/appKantin,target=/var/www/html \
  trafex/php-nginx

# Cek service
sudo docker service ls
sudo docker service ps web
```

### 8. Scale Service ke 3 Replika
```bash
# Scale service web ke 3 replika
sudo docker service scale web=3

# Cek status semua replika
sudo docker service ps web
```

### 9. Verifikasi Deployment
```bash
# Cek semua container yang berjalan
sudo docker ps

# Cek service swarm
sudo docker service ls
sudo docker service ps web

# Test akses dari server
curl http://localhost:8080
```

Jika curl menampilkan HTML aplikasi, deployment berhasil!

### 10. Test Load Balancing
```bash
# Test load balancing dengan melihat IP server yang berbeda
for i in {1..6}; do
  curl -s http://localhost:8080/index.php | grep -A1 "Hostname" | tail -1
done
```

Output akan menunjukkan IP server yang berbeda (round-robin ke 3 replika).

### 11. Akses dari Browser
Buka browser dan akses:
```
http://<PUBLIC_IP_EC2>:8080
```

Contoh: http://54.83.79.145:8080

Buka 3 tab browser dan refresh berkali-kali untuk melihat request dibagi ke replika berbeda.

## ⚠️ Hal yang TIDAK BOLEH Dilakukan (Untuk Docker Snap)

### ❌ JANGAN Gunakan Overlay Network
```bash
# JANGAN jalankan ini - akan menyebabkan error "read-only file system"
sudo docker network create --driver overlay app-network
```

### ❌ JANGAN Connect Service ke Overlay Network
```bash
# JANGAN jalankan ini - akan menyebabkan error
sudo docker service update --network-add app-network web
```

### ❌ JANGAN Jalankan MariaDB sebagai Swarm Service
```bash
# JANGAN jalankan ini - akan menyebabkan error
sudo docker service create --name db mariadb:11.8
```

### ❌ JANGAN Gunakan Network Custom untuk Web Service
```bash
# JANGAN gunakan --network flag saat membuat web service
sudo docker service create --name web --network app-network ...
```

## ✅ Solusi yang Benar

Gunakan:
- ✅ Host network untuk MariaDB container (standalone)
- ✅ Ingress network default untuk web service (swarm)
- ✅ Bridge gateway IP (172.17.0.1) untuk koneksi web ke db

## Perintah-perintah Penting

### Cek Status
```bash
sudo docker service ls
sudo docker service ps web
sudo docker ps
```

### Scale Service
```bash
# Tambah replika
sudo docker service scale web=5

# Kurangi replika
sudo docker service scale web=2
```

### Update Service
```bash
# Update image
sudo docker service update --image trafex/php-nginx web

# Force update (restart service)
sudo docker service update --force web
```

### Hapus Service dan Container
```bash
# Hapus service web
sudo docker service rm web

# Hapus container database
sudo docker stop db
sudo docker rm db

# Hapus volume database (hati-hati - data akan hilang!)
sudo docker volume rm dbdata
```

### Keluar dari Swarm
```bash
# Keluar dari swarm (force)
sudo docker swarm leave --force
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
│  │ Ingress Net  │  │ Ingress Net  │    │
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
│         │   Host Network  │             │
│         │   172.17.0.1    │             │
│         └─────────────────┘             │
│                                          │
└─────────────────────────────────────────┘
```

## Troubleshooting

### Error: "permission denied while trying to connect to the docker API"
**Solusi**: Gunakan `sudo` di depan semua perintah Docker

### Error: "read-only file system" saat membuat service dengan overlay network
**Solusi**: JANGAN gunakan overlay network. Gunakan ingress network default (tanpa --network flag)

### Error: "Name does not resolve" saat connect ke database
**Solusi**: Pastikan config/db.php menggunakan IP bridge gateway (172.17.0.1), bukan hostname "db"

### Service tidak bisa connect ke database
**Solusi**:
1. Pastikan MariaDB berjalan dengan `--network host`
2. Pastikan config/db.php menggunakan IP 172.17.0.1
3. Cek dengan: `sudo docker ps` untuk memastikan container db berjalan

### Container tidak bisa start
**Solusi**:
```bash
# Cek logs service
sudo docker service logs web

# Cek logs container
sudo docker logs <CONTAINER_ID>

# Cek resource
sudo docker stats
```

### Port sudah digunakan
**Solusi**:
```bash
# Cek port yang sedang digunakan
sudo netstat -tulpn | grep 8080

# Gunakan port lain jika perlu
sudo docker service create --name web -p 9090:8080 ...
```

## Catatan Penting

1. **Shared Database**: Semua replika web (web.1, web.2, web.3) mengakses database yang sama (container db), sehingga data konsisten di semua replika.

2. **Bind Mount**: File aplikasi di `/home/ubuntu/appKantin` di-mount ke semua container web. Perubahan di direktori ini akan langsung terlihat di semua replika.

3. **Volume**: Data database disimpan di Docker volume `dbdata`, sehingga data tetap ada meskipun container dihapus/restart.

4. **Routing Mesh**: Docker Swarm routing mesh secara otomatis membagi request ke replika secara round-robin.

5. **Network Communication**: Karena keterbatasan Docker Snap, kita menggunakan host network untuk db dan ingress network untuk web, dengan koneksi via bridge gateway IP.

## Laporan untuk GC

Setelah selesai, buat laporan yang berisi:

1. **Screenshot Docker Swarm Status**
   - `sudo docker service ls`
   - `sudo docker service ps web`
   - `sudo docker ps`

2. **Screenshot Aplikasi**
   - Halaman utama aplikasi dari browser
   - Halaman Menu (CRUD)
   - Halaman Dapur dengan pesanan

3. **Screenshot Load Balancing**
   - Hasil curl loop menunjukkan IP server berbeda:
   ```bash
   for i in {1..6}; do
     curl -s http://localhost:8080/index.php | grep -A1 "Hostname" | tail -1
   done
   ```

4. **Penjelasan Arsitektur**
   - Gambar arsitektur Docker Swarm Single Node
   - Penjelasan bagaimana 3 replika mengakses 1 database via bridge gateway IP

5. **Kesimpulan**
   - Apakah tugas berhasil diselesaikan?
   - Apa yang dipelajari tentang Docker Swarm?
   - Tantangan yang dihadapi (Docker Snap limitation) dan solusinya
