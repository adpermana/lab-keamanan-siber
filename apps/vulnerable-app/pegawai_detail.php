<?php
require_once 'config.php';

// Celah IDOR #2 + SQL Injection #3: Parameter id dari URL tanpa validasi
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$conn = buatKoneksi();

// RENTAN SQL INJECTION - input id langsung dari URL tanpa sanitasi
$id = $_GET['id'];
$query = "SELECT * FROM pegawai WHERE id = $id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

$pegawai = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Pegawai - SIMPEG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">SIMPEG</a>
        <div class="navbar-nav ms-auto">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Detail Pegawai</h2>

    <?php if ($pegawai): ?>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr>
                    <th width="200">ID</th>
                    <td><?= $pegawai['id'] ?></td>
                </tr>
                <tr>
                    <th>NIP</th>
                    <td><?= $pegawai['nip'] ?></td>
                </tr>
                <tr>
                    <th>Nama</th>
                    <td><?= $pegawai['nama'] ?></td>
                </tr>
                <tr>
                    <th>Alamat</th>
                    <td><?= $pegawai['alamat'] ?></td>
                </tr>
                <tr>
                    <th>No. Telepon</th>
                    <td><?= $pegawai['no_telp'] ?></td>
                </tr>
            </table>
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger">Data pegawai tidak ditemukan!</div>
    <?php endif; ?>
</div>
</body>
</html>
