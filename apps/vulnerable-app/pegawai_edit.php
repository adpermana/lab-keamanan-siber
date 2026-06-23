<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$conn = buatKoneksi();
$message = '';

// RENTAN SQL INJECTION - id dari URL tanpa sanitasi
$id = $_GET['id'] ?? 0;

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $nip = $_POST['nip'];
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $no_telp = $_POST['no_telp'];

    // RENTAN SQL INJECTION
    $query = "UPDATE pegawai SET nip = '$nip', nama = '$nama', alamat = '$alamat', no_telp = '$no_telp' WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $message = '<div class="alert alert-success">Data pegawai berhasil diupdate!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Ambil data pegawai
$query = "SELECT * FROM pegawai WHERE id = $id";
$result = mysqli_query($conn, $query);
$pegawai = mysqli_fetch_assoc($result);

if (!$pegawai) {
    die("Data pegawai tidak ditemukan!");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Pegawai - SIMPEG</title>
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
    <h2>Edit Pegawai</h2>
    <?= $message ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="edit" value="1">
                <div class="mb-3">
                    <label>NIP</label>
                    <input type="text" name="nip" class="form-control"
                           value="<?= $pegawai['nip'] ?>" required>
                </div>
                <div class="mb-3">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control"
                           value="<?= $pegawai['nama'] ?>" required>
                </div>
                <div class="mb-3">
                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3"><?= $pegawai['alamat'] ?></textarea>
                </div>
                <div class="mb-3">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" class="form-control"
                           value="<?= $pegawai['no_telp'] ?>">
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
