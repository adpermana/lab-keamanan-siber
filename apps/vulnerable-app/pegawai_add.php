<?php
require_once 'config.php';

// Celah RBAC #3: Tidak ada pengecekan role - siapapun bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$conn = buatKoneksi();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // RENTAN SQL INJECTION - input langsung concatenated
    $nip = $_POST['nip'];
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $no_telp = $_POST['no_telp'];

    $query = "INSERT INTO pegawai (nip, nama, alamat, no_telp) VALUES
              ('$nip', '$nama', '$alamat', '$no_telp')";

    if (mysqli_query($conn, $query)) {
        $message = '<div class="alert alert-success">Data pegawai berhasil ditambahkan!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pegawai - SIMPEG</title>
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
    <h2>Tambah Pegawai Baru</h2>
    <?= $message ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>NIP</label>
                    <input type="text" name="nip" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
