<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$conn = buatKoneksi();

// Celah IDOR #5: Bisa lihat profil user lain dengan mengubah parameter id
$id = $_GET['id'] ?? $_SESSION['user_id'];

// RENTAN SQL INJECTION
$query = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profil - SIMPEG</title>
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
    <h2>Profil User</h2>
    <?php if ($user): ?>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr>
                    <th>ID</th>
                    <td><?= $user['id'] ?></td>
                </tr>
                <tr>
                    <th>Username</th>
                    <td><?= $user['username'] ?></td>
                </tr>
                <tr>
                    <th>Password</th>
                    <td><?= $user['password'] ?></td>
                </tr>
                <tr>
                    <th>Role</th>
                    <td><?= $user['role'] ?></td>
                </tr>
                <tr>
                    <th>Nama</th>
                    <td><?= $user['nama'] ?></td>
                </tr>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger">User tidak ditemukan!</div>
    <?php endif; ?>
</div>
</body>
</html>
