# Quick Start - Docker Swarm Deployment

## ⚠️ Cek Instalasi Docker Anda

```bash
sudo docker info | grep "Docker Root Dir"
```

### Jika: `/var/snap/docker/common/var-lib-docker`
**Ikuti panduan ini (Docker Snap)**

### Jika: `/var/lib/docker`
Gunakan panduan standar Docker (bukan Snap)

---

## Langkah Deployment (Docker Snap - Ubuntu Core/EC2 AWS)

### 1. Cek dan Setup Permission
```bash
# Jika error permission denied, gunakan sudo di semua perintah
sudo docker ps
```

### 2. Pull Images
```bash
sudo docker pull trafex/php-nginx
sudo docker pull mariadb:11.8
```

### 3. Init Swarm
```bash
sudo docker swarm init --advertise-addr <YOUR_PUBLIC_IP>
# Contoh: sudo docker swarm init --advertise-addr 54.83.79.145
```

### 4. Cek Bridge Gateway IP
```bash
sudo docker network inspect bridge --format '{{range .IPAM.Config}}{{.Gateway}}{{end}}'
# Output: 172.17.0.1 (CATAT IP INI!)
```

### 5. Persiapkan Aplikasi
```bash
mkdir -p /home/ubuntu/appKantin
cd /home/ubuntu/appKantin
git clone https://github.com/Deri-Nugroho/appKantin.git temp
mv temp/* .
rm -rf temp
```

### 6. Edit config/db.php
```bash
nano config/db.php
```

Ubah bagian KONFIGURASI AKTIF:
```php
$DB_HOST = "172.17.0.1";  // IP dari langkah 4
$DB_USER = "root";
$DB_PASS = "rahasia";
$DB_NAME = "appdb";
```

Simpan: `Ctrl+X`, `Y`, `Enter`

### 7. Jalankan MariaDB (Host Network)
```bash
sudo docker run -d --name db \
  --network host \
  -e MARIADB_ROOT_PASSWORD=rahasia \
  -e MARIADB_DATABASE=appdb \
  -v dbdata:/var/lib/mysql \
  mariadb:11.8
```

### 8. Buat Web Service
```bash
sudo docker service create --name web \
  -p 8080:8080 \
  --mount type=bind,source=/home/ubuntu/appKantin,target=/var/www/html \
  trafex/php-nginx
```

### 9. Scale ke 3 Replika
```bash
sudo docker service scale web=3
```

### 10. Test
```bash
curl http://localhost:8080
```

Jika menampilkan HTML aplikasi, deployment berhasil!

---

## ❌ JANGAN Lakukan Ini (Docker Snap)

```bash
# JANGAN buat overlay network
sudo docker network create --driver overlay app-network  # ❌ ERROR

# JANGAN connect service ke overlay network
sudo docker service update --network-add app-network web  # ❌ ERROR

# JANGAN gunakan --network saat membuat service
sudo docker service create --name web --network app-network ...  # ❌ ERROR
```

---

## ✅ Solusi yang Benar

- ✅ Gunakan `--network host` untuk MariaDB container
- ✅ TANPA `--network` flag untuk web service (gunakan default ingress)
- ✅ Connect via bridge gateway IP (172.17.0.1)

---

## Verifikasi

```bash
# Cek service
sudo docker service ls
sudo docker service ps web

# Cek container
sudo docker ps

# Test load balancing
for i in {1..6}; do
  curl -s http://localhost:8080/index.php | grep -A1 "Hostname" | tail -1
done
```

---

## Akses dari Browser

```
http://<YOUR_PUBLIC_IP>:8080
```

---

## Masalah Umum

### Error: "permission denied"
**Solusi**: Gunakan `sudo` di depan semua perintah Docker

### Error: "read-only file system"
**Solusi**: JANGAN gunakan overlay network. Ikuti panduan ini.

### Error: "Name does not resolve"
**Solusi**: Pastikan config/db.php menggunakan IP 172.17.0.1, bukan "db"

---

## Dokumentasi Lengkap

Untuk detail lengkap, troubleshooting, dan laporan GC, lihat:
- **DOCKER_SWARM_GUIDE.md** - Panduan lengkap Docker Swarm
- **DEPLOYMENT_SUMMARY.md** - Ringkasan deployment
- **README.md** - Dokumentasi aplikasi
