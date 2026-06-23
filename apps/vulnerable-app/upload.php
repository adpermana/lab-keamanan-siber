<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$message = '';
$upload_dir = 'uploads/';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // Celah File Upload #1: Tidak ada validasi tipe file
    // Celah File Upload #2: Tidak ada validasi ekstensi
    // Celah File Upload #3: Nama file tidak diganti (path traversal)
    // Celah File Upload #4: Tidak ada batasan ukuran file

    $target_file = $upload_dir . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $message = '<div class="alert alert-success">
                        File berhasil diupload: <a href="' . $target_file . '">' . $file['name'] . '</a>
                    </div>';
    } else {
        $message = '<div class="alert alert-danger">Gagal upload file!</div>';
    }
}

// Celah File Upload #5: Menampilkan daftar file yang sudah diupload
$uploaded_files = glob($upload_dir . '*');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload File - SIMPEG</title>
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
    <h2>Upload File</h2>
    <?= $message ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label>Pilih File</label>
                    <input type="file" name="file" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>

    <h4>File Terupload</h4>
    <div class="list-group">
        <?php if ($uploaded_files): ?>
            <?php foreach ($uploaded_files as $file): ?>
                <?php $filename = basename($file); ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="<?= $file ?>"><?= $filename ?></a>
                    <span class="badge bg-secondary rounded-pill">
                        <?= round(filesize($file) / 1024, 2) ?> KB
                    </span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">Belum ada file terupload.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
