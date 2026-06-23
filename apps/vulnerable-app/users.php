<?php
require_once 'config.php';

// Celah RBAC #4: Pengecekan role dilakukan tapi bisa dilewati
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$conn = buatKoneksi();

// Celah IDOR #3: Delete user tanpa validasi kepemilikan
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $query = "DELETE FROM users WHERE id = $delete_id";
    mysqli_query($conn, $query);
    $message = '<div class="alert alert-success">User berhasil dihapus!</div>';
}

// Celah IDOR #4: Edit user tanpa validasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $edit_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    $new_nama = $_POST['nama'];
    $query = "UPDATE users SET role = '$new_role', nama = '$new_nama' WHERE id = $edit_id";
    mysqli_query($conn, $query);
    $message = '<div class="alert alert-success">User berhasil diupdate!</div>';
}

// Ganti Password - Celah: password dikirim plaintext, disimpan plaintext
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ganti_password'])) {
    $target_id = $_POST['user_id_pass'];
    $password_baru = $_POST['password_baru'];
    $query = "UPDATE users SET password = '$password_baru' WHERE id = $target_id";
    if (mysqli_query($conn, $query)) {
        $message = '<div class="alert alert-success">Password berhasil diganti untuk user ID ' . $target_id . '!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manajemen User - SIMPEG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">SIMPEG</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><?= $_SESSION['role'] ?>: <?= $_SESSION['nama'] ?></span>
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Manajemen User</h2>
    <?= $message ?? '' ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Nama</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= $user['username'] ?></td>
                        <td><?= $user['password'] ?></td>
                        <td>
                            <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : 'secondary' ?>">
                                <?= $user['role'] ?>
                            </span>
                        </td>
                        <td><?= $user['nama'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    data-id="<?= $user['id'] ?>"
                                    data-role="<?= $user['role'] ?>"
                                    data-nama="<?= $user['nama'] ?>">Edit</button>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                    data-bs-target="#passwordModal"
                                    data-id="<?= $user['id'] ?>"
                                    data-username="<?= $user['username'] ?>">Ganti Password</button>
                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Hapus user ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Edit User</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_user" value="1">
                    <input type="hidden" name="user_id" id="edit_id">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" id="edit_role" class="form-control">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ganti Password -->
<div class="modal fade" id="passwordModal">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Ganti Password</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ganti_password" value="1">
                    <input type="hidden" name="user_id_pass" id="pass_user_id">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" id="pass_username" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label>Password Baru</label>
                        <input type="text" name="password_baru" class="form-control" required
                               placeholder="Masukkan password baru">
                        <small class="text-muted">Password disimpan dalam bentuk plaintext (tidak di-hash)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Password</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('edit_id').value = button.getAttribute('data-id');
        document.getElementById('edit_role').value = button.getAttribute('data-role');
        document.getElementById('edit_nama').value = button.getAttribute('data-nama');
    });

    var passwordModal = document.getElementById('passwordModal');
    passwordModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('pass_user_id').value = button.getAttribute('data-id');
        document.getElementById('pass_username').value = button.getAttribute('data-username');
    });
});
</script>
</body>
</html>
