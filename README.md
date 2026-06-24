# Lab Keamanan Siber
# Web Security Testing Lab

## 🚀 Cara Menjalankan

```bash
# 1. Clone repo
git clone https://github.com/adpermana/lab-keamanan-siber.git
cd lab-keamanan-siber

# 2. Jalankan semua aplikasi
docker compose up -d --build

# 3. Buka launcher di browser
#    http://localhost:8084

# 4. Dari launcher, Anda bisa Start/Stop aplikasi dan mengakses Lab soal
```

## 📋 Aplikasi

| Aplikasi | URL | Kredensial |
|----------|-----|------------|
| **Launcher** | http://localhost:8084 | - |
| **SIMPEG** (vulnerable) | http://localhost:8080 | admin / admin123 |
| **SIMPEG phpMyAdmin** | http://localhost:8081 | root / rootpassword |
| **Fotorecv3** (vulnerable) | http://localhost:8082 | admin / p@sswoRd1234 |
| **Fotorecv3 phpMyAdmin** | http://localhost:8083 | root / admin1234 |
| **SPMB** (Laravel, vulnerable) | http://localhost:8085 | admin / admin123 |
| **SPMB phpMyAdmin** | http://localhost:8086 | root / rootpassword |

## 🎯 Lab Soal Keamanan

10 soal praktik pengujian celah keamanan web:
1. SQL Injection — Bypass Login (SIMPEG)
2. SQL Injection — Ekstrak Data dengan UNION (SIMPEG)
3. IDOR — Akses Data Pegawai Lain (SIMPEG)
4. IDOR — Lihat Password User Lain (SIMPEG)
5. RBAC — Privilege Escalation (SIMPEG)
6. XSS — Reflected Cross-Site Scripting (SIMPEG)
7. File Upload — Upload Web Shell (SIMPEG)
8. SQL Injection — Ekstrak Data Foto (Fotorecv3)
9. Authentication Bypass — Admin Panel (Fotorecv3)
10. File Upload — Bypass Filter Ekstensi (Fotorecv3)

Setiap soal dilengkapi **Hint**, **Tutorial langkah demi langkah**, **Penjelasan teknis**, dan **Cara memperbaiki**.

Akses lab di http://localhost:8084/lab.php atau melalui tombol **Lab** di launcher.

## 🛑 Menghentikan Aplikasi

```bash
docker compose down
```

## ⚠️ Peringatan

Aplikasi ini sengaja dibuat **rentan** untuk tujuan pembelajaran keamanan siber.
**JANGAN** gunakan di jaringan publik atau sistem produksi.
Gunakan hanya di lingkungan lokal yang terisolasi.
