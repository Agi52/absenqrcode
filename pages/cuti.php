<!-- pages/cuti.php - Halaman Validasi Pengajuan Cuti -->
<?php
// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $cuti_id = $_POST['cuti_id'];
        
        if ($action == 'approve') {
            $status = 'approved';
            $keterangan = 'Pengajuan cuti disetujui';
        } elseif ($action == 'reject') {
            $status = 'rejected';
            $keterangan = $_POST['keterangan_reject'] ?? 'Pengajuan cuti ditolak';
        }
        
        if (isset($status)) {
            try {
                $stmt = $db->prepare("UPDATE pengajuan_cuti SET status = ?, keterangan_admin = ?, disetujui_oleh = ?, tanggal_disetujui = NOW() WHERE id = ?");
                $stmt->execute([$status, $keterangan, $_SESSION['user_id'], $cuti_id]);
                
                $message = "Pengajuan cuti berhasil " . ($status == 'approved' ? 'disetujui' : 'ditolak');
                $alert_type = "success";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $alert_type = "danger";
            }
        }
    }
    
    // Handle pengajuan cuti baru dari user
    if (isset($_POST['submit_cuti'])) {
        try {
            $stmt = $db->prepare("INSERT INTO pengajuan_cuti (user_id, tanggal_mulai, tanggal_selesai, jenis_cuti, alasan, status, tanggal_ajuan) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['tanggal_mulai'],
                $_POST['tanggal_selesai'],
                $_POST['jenis_cuti'],
                $_POST['alasan']
            ]);
            
            $message = "Pengajuan cuti berhasil disubmit";
            $alert_type = "success";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    }
}

// Get pengajuan cuti data
if ($_SESSION['role'] == 'admin') {
    // Admin dapat melihat semua pengajuan
    $stmt = $db->prepare("
        SELECT pc.*, u.nama, u.email, j.nama_jabatan,
               admin.nama as admin_name
        FROM pengajuan_cuti pc 
        JOIN users u ON pc.user_id = u.id 
        JOIN jabatan j ON u.jabatan_id = j.id
        LEFT JOIN users admin ON pc.disetujui_oleh = admin.id
        ORDER BY pc.tanggal_ajuan DESC
    ");
} else {
    // User hanya dapat melihat pengajuan sendiri
    $stmt = $db->prepare("
        SELECT pc.*, u.nama, u.email, j.nama_jabatan,
               admin.nama as admin_name
        FROM pengajuan_cuti pc 
        JOIN users u ON pc.user_id = u.id 
        JOIN jabatan j ON u.jabatan_id = j.id
        LEFT JOIN users admin ON pc.disetujui_oleh = admin.id
        WHERE pc.user_id = ?
        ORDER BY pc.tanggal_ajuan DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

if ($_SESSION['role'] == 'admin') {
    $stmt->execute();
}

$pengajuan_cuti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics untuk admin
if ($_SESSION['role'] == 'admin') {
    $stats = [];
    $stmt = $db->prepare("SELECT COUNT(*) FROM pengajuan_cuti WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending'] = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM pengajuan_cuti WHERE status = 'approved'");
    $stmt->execute();
    $stats['approved'] = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM pengajuan_cuti WHERE status = 'rejected'");
    $stmt->execute();
    $stats['rejected'] = $stmt->fetchColumn();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar-alt"></i> Manajemen Cuti</h2>
                <?php if ($_SESSION['role'] != 'admin'): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cutiModal">
                    <i class="fas fa-plus"></i> Ajukan Cuti
                </button>
                <?php endif; ?>
            </div>

            <?php if (isset($message)): ?>
            <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'admin'): ?>
            <!-- Statistics Cards untuk Admin -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <h3><?= $stats['pending'] ?></h3>
                            <p class="mb-0"><i class="fas fa-clock"></i> Menunggu Persetujuan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body">
                            <h3><?= $stats['approved'] ?></h3>
                            <p class="mb-0"><i class="fas fa-check"></i> Disetujui</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white text-center">
                        <div class="card-body">
                            <h3><?= $stats['rejected'] ?></h3>
                            <p class="mb-0"><i class="fas fa-times"></i> Ditolak</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabel Pengajuan Cuti -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Daftar Pengajuan Cuti</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th>Jenis Cuti</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Durasi</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Tanggal Ajuan</th>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pengajuan_cuti as $index => $cuti): 
                                    $tanggal_mulai = new DateTime($cuti['tanggal_mulai']);
                                    $tanggal_selesai = new DateTime($cuti['tanggal_selesai']);
                                    $durasi = $tanggal_mulai->diff($tanggal_selesai)->days + 1;
                                    
                                    $status_class = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    
                                    $status_text = [
                                        'pending' => 'Menunggu',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak'
                                    ];
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($cuti['nama']) ?></td>
                                    <td><?= htmlspecialchars($cuti['nama_jabatan']) ?></td>
                                    <td><?= htmlspecialchars($cuti['jenis_cuti']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($cuti['tanggal_mulai'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($cuti['tanggal_selesai'])) ?></td>
                                    <td><?= $durasi ?> hari</td>
                                    <td><?= htmlspecialchars($cuti['alasan']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $status_class[$cuti['status']] ?>">
                                            <?= $status_text[$cuti['status']] ?>
                                        </span>
                                        <?php if ($cuti['status'] != 'pending' && $cuti['admin_name']): ?>
                                        <br><small class="text-muted">oleh <?= htmlspecialchars($cuti['admin_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($cuti['tanggal_ajuan'])) ?></td>
                                    <?php if ($_SESSION['role'] == 'admin' && $cuti['status'] == 'pending'): ?>
                                    <td>
                                        <button class="btn btn-success btn-sm" onclick="approveCuti(<?= $cuti['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="rejectCuti(<?= $cuti['id'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                    <?php elseif ($_SESSION['role'] == 'admin'): ?>
                                    <td>
                                        <span class="text-muted">Selesai</span>
                                        <?php if ($cuti['keterangan_admin']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($cuti['keterangan_admin']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pengajuan Cuti untuk User -->
<?php if ($_SESSION['role'] != 'admin'): ?>
<div class="modal fade" id="cutiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajukan Cuti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis Cuti</label>
                        <select class="form-select" name="jenis_cuti" required>
                            <option value="">Pilih Jenis Cuti</option>
                            <option value="Cuti Tahunan">Cuti Tahunan</option>
                            <option value="Cuti Sakit">Cuti Sakit</option>
                            <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                            <option value="Cuti Menikah">Cuti Menikah</option>
                            <option value="Cuti Khusus">Cuti Khusus</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="tanggal_mulai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" name="tanggal_selesai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <textarea class="form-control" name="alasan" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_cuti" class="btn btn-primary">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Reject Cuti -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Pengajuan Cuti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="cuti_id" id="reject_cuti_id">
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan</label>
                        <textarea class="form-control" name="keterangan_reject" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveCuti(cutiId) {
    if (confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="cuti_id" value="${cutiId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectCuti(cutiId) {
    document.getElementById('reject_cuti_id').value = cutiId;
    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

// Validasi tanggal
document.addEventListener('DOMContentLoaded', function() {
    const tanggalMulai = document.querySelector('input[name="tanggal_mulai"]');
    const tanggalSelesai = document.querySelector('input[name="tanggal_selesai"]');
    
    if (tanggalMulai && tanggalSelesai) {
        tanggalMulai.addEventListener('change', function() {
            tanggalSelesai.min = this.value;
        });
        
        tanggalSelesai.addEventListener('change', function() {
            if (this.value < tanggalMulai.value) {
                alert('Tanggal selesai tidak boleh lebih awal dari tanggal mulai');
                this.value = '';
            }
        });
    }
});
</script>