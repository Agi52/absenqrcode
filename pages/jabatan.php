<!-- pages/jabatan.php -->
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
                $nama_jabatan = trim($_POST['nama_jabatan']);
                $deskripsi = trim($_POST['deskripsi']);
                
                if (!empty($nama_jabatan)) {
                    // Check if jabatan already exists
                    $check_query = "SELECT COUNT(*) FROM jabatan WHERE nama_jabatan = ?";
                    $stmt = $db->prepare($check_query);
                    $stmt->execute([$nama_jabatan]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Jabatan dengan nama tersebut sudah ada!";
                    } else {
                        $insert_query = "INSERT INTO jabatan (nama_jabatan, deskripsi) VALUES (?, ?)";
                        $stmt = $db->prepare($insert_query);
                        if ($stmt->execute([$nama_jabatan, $deskripsi])) {
                            $success = "Jabatan berhasil ditambahkan!";
                        } else {
                            $error = "Gagal menambahkan jabatan!";
                        }
                    }
                } else {
                    $error = "Nama jabatan tidak boleh kosong!";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $nama_jabatan = trim($_POST['nama_jabatan']);
                $deskripsi = trim($_POST['deskripsi']);
                
                if (!empty($nama_jabatan)) {
                    // Check if jabatan name already exists for other records
                    $check_query = "SELECT COUNT(*) FROM jabatan WHERE nama_jabatan = ? AND id != ?";
                    $stmt = $db->prepare($check_query);
                    $stmt->execute([$nama_jabatan, $id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Jabatan dengan nama tersebut sudah ada!";
                    } else {
                        $update_query = "UPDATE jabatan SET nama_jabatan = ?, deskripsi = ? WHERE id = ?";
                        $stmt = $db->prepare($update_query);
                        if ($stmt->execute([$nama_jabatan, $deskripsi, $id])) {
                            $success = "Jabatan berhasil diperbarui!";
                        } else {
                            $error = "Gagal memperbarui jabatan!";
                        }
                    }
                } else {
                    $error = "Nama jabatan tidak boleh kosong!";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Check if jabatan is being used by users
                $check_users_query = "SELECT COUNT(*) FROM users WHERE jabatan_id = ?";
                $stmt = $db->prepare($check_users_query);
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "Jabatan tidak dapat dihapus karena masih digunakan oleh user!";
                } else {
                    $delete_query = "DELETE FROM jabatan WHERE id = ?";
                    $stmt = $db->prepare($delete_query);
                    if ($stmt->execute([$id])) {
                        $success = "Jabatan berhasil dihapus!";
                    } else {
                        $error = "Gagal menghapus jabatan!";
                    }
                }
                break;
        }
    }
}

// Get all jabatan
$jabatan_query = "SELECT j.*, COUNT(u.id) as jumlah_user 
                  FROM jabatan j 
                  LEFT JOIN users u ON j.id = u.jabatan_id 
                  GROUP BY j.id 
                  ORDER BY j.nama_jabatan";
$jabatan_list = $db->query($jabatan_query)->fetchAll(PDO::FETCH_ASSOC);

// Get jabatan for edit modal if requested
$edit_jabatan = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM jabatan WHERE id = ?";
    $stmt = $db->prepare($edit_query);
    $stmt->execute([$edit_id]);
    $edit_jabatan = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2>Data Jabatan</h2>
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
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJabatanModal">
            <i class="fas fa-plus me-2"></i>Tambah Jabatan
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
                        <th>Nama Jabatan</th>
                        <th>Deskripsi</th>
                        <th>Jumlah User</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jabatan_list)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada data jabatan</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($jabatan_list as $index => $jabatan): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($jabatan['nama_jabatan']) ?></strong>
                            </td>
                            <td>
                                <?= !empty($jabatan['deskripsi']) ? htmlspecialchars($jabatan['deskripsi']) : '<em class="text-muted">Tidak ada deskripsi</em>' ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $jabatan['jumlah_user'] ?> user</span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editJabatanModal"
                                            onclick="editJabatan(<?= $jabatan['id'] ?>, '<?= htmlspecialchars($jabatan['nama_jabatan'], ENT_QUOTES) ?>', '<?= htmlspecialchars($jabatan['deskripsi'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($jabatan['jumlah_user'] == 0): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus jabatan ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $jabatan['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Tidak dapat dihapus karena masih digunakan">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Jabatan Modal -->
<div class="modal fade" id="addJabatanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jabatan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_jabatan" class="form-control" required maxlength="100">
                        <div class="form-text">Contoh: Guru Matematika, Kepala Sekolah, Staff TU</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" maxlength="255" placeholder="Deskripsi singkat tentang jabatan ini (opsional)"></textarea>
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

<!-- Edit Jabatan Modal -->
<div class="modal fade" id="editJabatanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_jabatan" id="edit_nama_jabatan" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3" maxlength="255" placeholder="Deskripsi singkat tentang jabatan ini (opsional)"></textarea>
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
function editJabatan(id, nama, deskripsi) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_jabatan').value = nama;
    document.getElementById('edit_deskripsi').value = deskripsi;
}
</script>