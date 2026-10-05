# Docker Swarm Single Node - Deployment Summary

## Status: ✅ SUKSES

Deployment Docker Swarm Single Node dengan MariaDB telah berhasil dilakukan di EC2 AWS.

## Arsitektur yang Digunakan

Karena Docker diinstall via Snap di Ubuntu Core (dengan keterbatasan overlay network), kita menggunakan arsitektur berikut:

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
│  │ 10.0.0.159   │  │ 10.0.0.160   │    │
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

## Komponen yang Berjalan

### 1. Docker Swarm
- **Status**: Active
- **Node**: 1 (Manager + Worker)
- **Manager Address**: 54.83.79.145:2377

### 2. MariaDB Container (Standalone)
- **Image**: mariadb:11.8
- **Network**: host
- **Environment**:
  - MARIADB_ROOT_PASSWORD: rahasia
  - MARIADB_DATABASE: appdb
- **Volume**: dbdata (untuk persistensi data)
- **Akses**: Dari swarm service via 172.17.0.1 (bridge gateway)

### 3. Web Service (Swarm)
- **Image**: trafex/php-nginx
- **Replicas**: 3 (web.1, web.2, web.3)
- **Network**: ingress (default swarm network)
- **Port**: 8080
- **Mount**: /home/ubuntu/appKantin → /var/www/html
- **Load Balancing**: Round-robin via routing mesh

## Konfigurasi Database

File: `config/db.php`
```php
$DB_HOST = "172.17.0.1";  // IP bridge gateway
$DB_USER = "root";
$DB_PASS = "rahasia";
$DB_NAME = "appdb";
```

## Verifikasi Load Balancing

Test menunjukkan request dibagi ke 3 replika:
- Replika 1: 10.0.0.159, 172.19.0.3
- Replika 2: 10.0.0.160, 172.19.0.5
- Replika 3: 10.0.0.161, 172.19.0.4

## Perintah Penting

### Cek Status
```bash
sudo docker service ls
sudo docker service ps web
sudo docker ps
```

### Scale Service
```bash
sudo docker service scale web=5
```

### Hapus Service dan Container
```bash
sudo docker service rm web
sudo docker stop db
sudo docker rm db
```

### Keluar dari Swarm
```bash
sudo docker swarm leave --force
```

## Akses Aplikasi

- **Dalam Server**: http://localhost:8080
- **Dari Browser**: http://54.83.79.145:8080

## Catatan Penting

1. **Docker Snap Limitation**: Overlay network untuk Swarm service tidak berfungsi dengan baik di Docker Snap (Ubuntu Core), menyebabkan error "read-only file system"

2. **Solusi**: Gunakan host network untuk database dan ingress network default untuk web service. Web containers connect ke database via bridge gateway IP (172.17.0.1)

3. **Shared Database**: Semua 3 replika web mengakses database yang sama, sehingga data konsisten

4. **Load Balancing**: Docker Swarm routing mesh secara otomatis membagi request ke replika secara round-robin

## Langkah Deployment (Quick Reference)

```bash
# 1. Pull images
sudo docker pull trafex/php-nginx
sudo docker pull mariadb:11.8

# 2. Init swarm
sudo docker swarm init --advertise-addr 54.83.79.145

# 3. Cek bridge gateway IP
sudo docker network inspect bridge --format '{{range .IPAM.Config}}{{.Gateway}}{{end}}'

# 4. Edit config/db.php dengan IP gateway (172.17.0.1)

# 5. Jalankan MariaDB dengan host network
sudo docker run -d --name db \
  --network host \
  -e MARIADB_ROOT_PASSWORD=rahasia \
  -e MARIADB_DATABASE=appdb \
  -v dbdata:/var/lib/mysql \
  mariadb:11.8

# 6. Buat web service
sudo docker service create --name web \
  -p 8080:8080 \
  --mount type=bind,source=/home/ubuntu/appKantin,target=/var/www/html \
  trafex/php-nginx

# 7. Scale ke 3 replika
sudo docker service scale web=3

# 8. Test
curl http://localhost:8080
```

## Screenshot untuk Laporan GC

### 1. Docker Swarm Status
```bash
sudo docker service ls
sudo docker service ps web
```

### 2. Container Status
```bash
sudo docker ps
```

### 3. Aplikasi di Browser
Buka http://54.83.79.145:8080 dan screenshot halaman utama

### 4. Load Balancing Test
```bash
for i in {1..6}; do
  curl -s http://localhost:8080/index.php | grep -A1 "Hostname" | tail -1
done
```

### 5. Arsitektur
Gambar diagram arsitektur di atas

## Kesimpulan

Tugas Docker Swarm Single Node dengan MariaDB telah berhasil diselesaikan dengan:
- ✅ 3 replika web service berjalan
- ✅ MariaDB container berjalan sebagai database shared
- ✅ Load balancing round-robin berfungsi
- ✅ Aplikasi appKantin dapat diakses dan berfungsi sepenuhnya
- ✅ Data konsisten di semua replika

Solusi yang digunakan mengakomodasi keterbatasan Docker Snap di Ubuntu Core dengan menggunakan host network untuk database dan ingress network default untuk web service.
