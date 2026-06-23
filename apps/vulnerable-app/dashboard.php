<?php
require_once 'config.php';

// Celah RBAC #1: Tidak ada pengecekan apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$conn = buatKoneksi();

// Hapus pegawai (Celah IDOR + SQLi)
$message = '';
if (isset($_GET['hapus'])) {
    $hapus_id = $_GET['hapus'];
    $query = "DELETE FROM pegawai WHERE id = $hapus_id";
    if (mysqli_query($conn, $query)) {
        $message = '<div class="alert alert-success">Data pegawai berhasil dihapus!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Celah SQL Injection #2: Search parameter
$search = '';
$where = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $where = "WHERE pegawai.nama LIKE '%$search%' OR pegawai.nip LIKE '%$search%'";
}

$query = "SELECT * FROM pegawai $where";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - SIMPEG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">SIMPEG</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">Halo, <?= $_SESSION['nama'] ?? 'Tamu' ?></span>
            <a href="profile.php" class="nav-link">Profil Saya</a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="users.php" class="nav-link">Manajemen User</a>
            <?php endif; ?>
            <a href="upload.php" class="nav-link">Upload</a>
            <a href="logout.php" class="nav-link btn btn-danger btn-sm text-white">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Dashboard Kepegawaian</h2>
    <p>Selamat datang di Sistem Informasi Kepegawaian</p>

    <?= $message ?>

    <?php
    $count_query = "SELECT COUNT(*) as total FROM users";
    $count_result = mysqli_query($conn, $count_query);
    $total_users = mysqli_fetch_assoc($count_result)['total'];

    $pgw_query = "SELECT COUNT(*) as total FROM pegawai";
    $pgw_result = mysqli_query($conn, $pgw_query);
    $total_pegawai = mysqli_fetch_assoc($pgw_result)['total'];
    ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card bg-primary text-white p-3">
                <h5>Total User</h5>
                <h3><?= $total_users ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white p-3">
                <h5>Total Pegawai</h5>
                <h3><?= $total_pegawai ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white p-3">
                <h5>Role Anda</h5>
                <h3><?= $_SESSION['role'] ?></h3>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="row g-3">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control" placeholder="Cari pegawai..."
                           value="<?= $search ?>">
                    <small class="text-muted">Hasil pencarian untuk: <?= $search ?></small>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </form>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="pegawai_add.php" class="btn btn-success">+ Tambah Pegawai</a>
            <?php endif; ?>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>Alamat</th>
                    <th>No. Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['nip'] ?></td>
                        <td><?= $row['nama'] ?></td>
                        <td><?= $row['alamat'] ?></td>
                        <td><?= $row['no_telp'] ?></td>
                        <td>
                            <a href="pegawai_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Detail</a>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <a href="pegawai_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Tidak ada data</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
