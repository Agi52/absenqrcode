<!-- pages/users.php -->
<?php
if ($_SESSION['role'] != 'admin') {
    echo '<div class="alert alert-danger">Akses ditolak!</div>';
    return;
}

$success = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add':
                $nama = trim($_POST['nama']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $jabatan_id = $_POST['jabatan_id'];
                
                if (!empty($nama) && !empty($email) && !empty($password)) {
                    // Check if email already exists
                    $check_query = "SELECT COUNT(*) FROM users WHERE email = ?";
                    $stmt = $db->prepare($check_query);
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Email sudah digunakan!";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $qr_code = 'USER' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                        
                        $insert_query = "INSERT INTO users (nama, email, password, jabatan_id, qr_code) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($insert_query);
                        if ($stmt->execute([$nama, $email, $password_hash, $jabatan_id, $qr_code])) {
                            $success = "User berhasil ditambahkan!";
                        } else {
                            $error = "Gagal menambahkan user!";
                        }
                    }
                } else {
                    $error = "Semua field wajib diisi!";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $nama = trim($_POST['nama']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $jabatan_id = $_POST['jabatan_id'];
                
                if (!empty($nama) && !empty($email)) {
                    // Check if email already exists for other records
                    $check_query = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
                    $stmt = $db->prepare($check_query);
                    $stmt->execute([$email, $id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Email sudah digunakan!";
                    } else {
                        // Update password only if provided
                        if (!empty($password)) {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $update_query = "UPDATE users SET nama = ?, email = ?, password = ?, jabatan_id = ? WHERE id = ? AND role != 'admin'";
                            $stmt = $db->prepare($update_query);
                            $result = $stmt->execute([$nama, $email, $password_hash, $jabatan_id, $id]);
                        } else {
                            $update_query = "UPDATE users SET nama = ?, email = ?, jabatan_id = ? WHERE id = ? AND role != 'admin'";
                            $stmt = $db->prepare($update_query);
                            $result = $stmt->execute([$nama, $email, $jabatan_id, $id]);
                        }
                        
                        if ($result) {
                            $success = "User berhasil diperbarui!";
                        } else {
                            $error = "Gagal memperbarui user!";
                        }
                    }
                } else {
                    $error = "Nama dan email tidak boleh kosong!";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                $delete_query = "DELETE FROM users WHERE id = ? AND role != 'admin'";
                $stmt = $db->prepare($delete_query);
                if ($stmt->execute([$id])) {
                    $success = "User berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus user!";
                }
                break;
        }
    }
}

// Get all users with jabatan
$users_query = "SELECT u.*, j.nama_jabatan FROM users u 
                LEFT JOIN jabatan j ON u.jabatan_id = j.id 
                ORDER BY u.nama";
$users = $db->query($users_query)->fetchAll(PDO::FETCH_ASSOC);

// Get all jabatan for dropdown
$jabatan_query = "SELECT * FROM jabatan ORDER BY nama_jabatan";
$jabatan_list = $db->query($jabatan_query)->fetchAll(PDO::FETCH_ASSOC);

// Get user for edit modal if requested
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM users WHERE id = ? AND role != 'admin'";
    $stmt = $db->prepare($edit_query);
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2>Data Karyawan & Guru</h2>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $success ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i>Tambah User
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Jabatan</th>
                        <th>QR Code</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data user</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($users as $index => $user): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['nama']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?= !empty($user['nama_jabatan']) ? htmlspecialchars($user['nama_jabatan']) : '<em class="text-muted">Tidak ada jabatan</em>' ?>
                            </td>
                            <td><code><?= htmlspecialchars($user['qr_code']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : 'primary' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] != 'admin'): ?>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal"
                                            onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>', '<?= $user['jabatan_id'] ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus user ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required maxlength="100">
                        <div class="form-text">Nama lengkap karyawan/guru</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required maxlength="100">
                        <div class="form-text">Email yang akan digunakan untuk login</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="form-text">Minimal 6 karakter</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                        <select name="jabatan_id" class="form-select" required>
                            <option value="">Pilih Jabatan</option>
                            <?php foreach($jabatan_list as $jabatan): ?>
                            <option value="<?= $jabatan['id'] ?>"><?= htmlspecialchars($jabatan['nama_jabatan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="edit_password" class="form-control" minlength="6">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                        <select name="jabatan_id" id="edit_jabatan_id" class="form-select" required>
                            <option value="">Pilih Jabatan</option>
                            <?php foreach($jabatan_list as $jabatan): ?>
                            <option value="<?= $jabatan['id'] ?>"><?= htmlspecialchars($jabatan['nama_jabatan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(id, nama, email, jabatan_id) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_jabatan_id').value = jabatan_id;
    document.getElementById('edit_password').value = ''; // Clear password field
}
</script>