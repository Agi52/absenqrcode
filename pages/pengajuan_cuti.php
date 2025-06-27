<!-- pages/pengajuan_cuti.php - Halaman Pengajuan Cuti untuk User -->
<?php
// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_cuti'])) {
    try {
        // Validasi tanggal
        $tanggal_mulai = new DateTime($_POST['tanggal_mulai']);
        $tanggal_selesai = new DateTime($_POST['tanggal_selesai']);
        
        if ($tanggal_selesai < $tanggal_mulai) {
            throw new Exception("Tanggal selesai tidak boleh lebih awal dari tanggal mulai");
        }
        
        if ($tanggal_mulai < new DateTime()) {
            throw new Exception("Tanggal mulai tidak boleh di masa lalu");
        }
        
        // Hitung durasi cuti
        $durasi = $tanggal_mulai->diff($tanggal_selesai)->days + 1;
        
        // Cek sisa cuti tahunan jika jenis cuti adalah tahunan
        if ($_POST['jenis_cuti'] == 'Cuti Tahunan') {
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(DATEDIFF(tanggal_selesai, tanggal_mulai) + 1), 0) as total_cuti_terpakai
                FROM pengajuan_cuti 
                WHERE user_id = ? AND jenis_cuti = 'Cuti Tahunan' 
                AND status = 'approved' 
                AND YEAR(tanggal_mulai) = YEAR(CURDATE())
            ");
            $stmt->execute([$user_id]);
            $cuti_terpakai = $stmt->fetchColumn();
            
            $batas_cuti_tahunan = 12; // 12 hari per tahun
            $sisa_cuti = $batas_cuti_tahunan - $cuti_terpakai;
            
            if ($durasi > $sisa_cuti) {
                throw new Exception("Durasi cuti melebihi sisa cuti tahunan Anda. Sisa cuti: $sisa_cuti hari");
            }
        }
        
        // Insert pengajuan cuti
        $stmt = $db->prepare("
            INSERT INTO pengajuan_cuti 
            (user_id, tanggal_mulai, tanggal_selesai, jenis_cuti, alasan, status, tanggal_ajuan) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $_POST['tanggal_mulai'],
            $_POST['tanggal_selesai'],
            $_POST['jenis_cuti'],
            $_POST['alasan']
        ]);
        
        $message = "Pengajuan cuti berhasil disubmit dan menunggu persetujuan";
        $alert_type = "success";
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// Get user info
$stmt = $db->prepare("SELECT u.*, j.nama_jabatan FROM users u JOIN jabatan j ON u.jabatan_id = j.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Get riwayat pengajuan cuti user
$stmt = $db->prepare("
    SELECT pc.*, admin.nama as admin_name 
    FROM pengajuan_cuti pc 
    LEFT JOIN users admin ON pc.disetujui_oleh = admin.id 
    WHERE pc.user_id = ? 
    ORDER BY pc.tanggal_ajuan DESC
");
$stmt->execute([$user_id]);
$riwayat_cuti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistik cuti user
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_pengajuan,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'approved' AND jenis_cuti = 'Cuti Tahunan' AND YEAR(tanggal_mulai) = YEAR(CURDATE()) 
                 THEN DATEDIFF(tanggal_selesai, tanggal_mulai) + 1 ELSE 0 END) as cuti_tahunan_terpakai
    FROM pengajuan_cuti 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$batas_cuti_tahunan = 12;
$sisa_cuti_tahunan = $batas_cuti_tahunan - ($stats['cuti_tahunan_terpakai'] ?? 0);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar-plus"></i> Pengajuan Cuti</h2>
                <div class="text-muted">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($user_info['nama']) ?> - <?= htmlspecialchars($user_info['nama_jabatan']) ?>
                </div>
            </div>

            <?php if (isset($message)): ?>
            <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row mb-4">
                <!-- Form Pengajuan Cuti -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Form Pengajuan Cuti Baru</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="cutiForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                                        <select class="form-select" name="jenis_cuti" id="jenisCuti" required>
                                            <option value="">Pilih Jenis Cuti</option>
                                            <option value="Cuti Tahunan">Cuti Tahunan</option>
                                            <option value="Cuti Sakit">Cuti Sakit</option>
                                            <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                                            <option value="Cuti Menikah">Cuti Menikah</option>
                                            <option value="Cuti Khusus">Cuti Khusus</option>
                                        </select>
                                        <div class="form-text" id="cutiInfo"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Durasi Cuti</label>
                                        <input type="text" class="form-control" id="durasiCuti" readonly placeholder="Akan terhitung otomatis">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal_mulai" id="tanggalMulai" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal_selesai" id="tanggalSelesai" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Alasan/Keperluan <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="alasan" rows="4" placeholder="Jelaskan alasan pengajuan cuti..." required></textarea>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="setuju" required>
                                        <label class="form-check-label" for="setuju">
                                            Saya menyatakan bahwa data yang saya masukkan adalah benar dan saya bertanggung jawab atas pengajuan cuti ini.
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-redo"></i> Reset Form
                                    </button>
                                    <button type="submit" name="submit_cuti" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Ajukan Cuti
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Info Cuti -->
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle"></i> Info Cuti Anda</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 class="text-success"><?= $sisa_cuti_tahunan ?></h4>
                                    <small>Sisa Cuti Tahunan</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-primary"><?= $stats['cuti_tahunan_terpakai'] ?? 0 ?></h4>
                                    <small>Cuti Terpakai</small>
                                </div>
                            </div>
                            <hr>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= (($stats['cuti_tahunan_terpakai'] ?? 0) / $batas_cuti_tahunan) * 100 ?>%">
                                </div>
                            </div>
                            <small class="text-muted">Batas cuti tahunan: <?= $batas_cuti_tahunan ?> hari</small>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Ketentuan Cuti</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-check text-success"></i> Pengajuan cuti harus diajukan minimal 3 hari sebelumnya</li>
                                <li><i class="fas fa-check text-success"></i> Cuti tahunan maksimal 12 hari per tahun</li>
                                <li><i class="fas fa-check text-success"></i> Cuti sakit perlu surat dokter jika >3 hari</li>
                                <li><i class="fas fa-check text-success"></i> Pengajuan dapat dibatalkan jika belum disetujui</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Pengajuan Cuti -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Riwayat Pengajuan Cuti</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($riwayat_cuti)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada riwayat pengajuan cuti</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Jenis Cuti</th>
                                    <th>Tanggal</th>
                                    <th>Durasi</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Tanggal Ajuan</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat_cuti as $index => $cuti): 
                                    $tanggal_mulai = new DateTime($cuti['tanggal_mulai']);
                                    $tanggal_selesai = new DateTime($cuti['tanggal_selesai']);
                                    $durasi = $tanggal_mulai->diff($tanggal_selesai)->days + 1;
                                    
                                    $status_class = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    
                                    $status_icon = [
                                        'pending' => 'fas fa-clock',
                                        'approved' => 'fas fa-check-circle',
                                        'rejected' => 'fas fa-times-circle'
                                    ];
                                    
                                    $status_text = [
                                        'pending' => 'Menunggu',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak'
                                    ];
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($cuti['jenis_cuti']) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($cuti['tanggal_mulai'])) ?><br>
                                        <small class="text-muted">s/d <?= date('d/m/Y', strtotime($cuti['tanggal_selesai'])) ?></small>
                                    </td>
                                    <td><?= $durasi ?> hari</td>
                                    <td>
                                        <div style="max-width: 200px;">
                                            <?= htmlspecialchars(substr($cuti['alasan'], 0, 50)) ?>
                                            <?= strlen($cuti['alasan']) > 50 ? '...' : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $status_class[$cuti['status']] ?>">
                                            <i class="<?= $status_icon[$cuti['status']] ?>"></i>
                                            <?= $status_text[$cuti['status']] ?>
                                        </span>
                                        <?php if ($cuti['admin_name']): ?>
                                        <br><small class="text-muted">oleh <?= htmlspecialchars($cuti['admin_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($cuti['tanggal_ajuan'])) ?></td>
                                    <td>
                                        <?php if ($cuti['keterangan_admin']): ?>
                                        <small><?= htmlspecialchars($cuti['keterangan_admin']) ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalMulai = document.getElementById('tanggalMulai');
    const tanggalSelesai = document.getElementById('tanggalSelesai');
    const durasiCuti = document.getElementById('durasiCuti');
    const jenisCuti = document.getElementById('jenisCuti');
    const cutiInfo = document.getElementById('cutiInfo');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    tanggalMulai.min = today;
    
    // Update minimum date for tanggal selesai when tanggal mulai changes
    tanggalMulai.addEventListener('change', function() {
        tanggalSelesai.min = this.value;
        if (tanggalSelesai.value && tanggalSelesai.value < this.value) {
            tanggalSelesai.value = '';
        }
        calculateDuration();
    });
    
    tanggalSelesai.addEventListener('change', function() {
        if (this.value < tanggalMulai.value) {
            alert('Tanggal selesai tidak boleh lebih awal dari tanggal mulai');
            this.value = '';
            return;
        }
        calculateDuration();
    });
    
    function calculateDuration() {
        if (tanggalMulai.value && tanggalSelesai.value) {
            const start = new Date(tanggalMulai.value);
            const end = new Date(tanggalSelesai.value);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            durasiCuti.value = diffDays + ' hari';
            
            // Check if cuti tahunan exceeds limit
            if (jenisCuti.value === 'Cuti Tahunan') {
                const sisaCuti = <?= $sisa_cuti_tahunan ?>;
                if (diffDays > sisaCuti) {
                    alert(`Durasi cuti melebihi sisa cuti tahunan Anda (${sisaCuti} hari)`);
                    tanggalSelesai.value = '';
                    durasiCuti.value = '';
                }
            }
        } else {
            durasiCuti.value = '';
        }
    }
    
    jenisCuti.addEventListener('change', function() {
        const infoTexts = {
            'Cuti Tahunan': `Sisa cuti tahunan Anda: <?= $sisa_cuti_tahunan ?> hari`,
            'Cuti Sakit': 'Lampirkan surat dokter jika lebih dari 3 hari',
            'Cuti Melahirkan': 'Cuti melahirkan maksimal 3 bulan',
            'Cuti Menikah': 'Cuti menikah maksimal 3 hari',
            'Cuti Khusus': 'Sesuai kebijakan perusahaan'
        };
        
        cutiInfo.textContent = infoTexts[this.value] || '';
        cutiInfo.className = this.value === 'Cuti Tahunan' && <?= $sisa_cuti_tahunan ?> <= 0 
                           ? 'form-text text-danger' 
                           : 'form-text text-muted';
        
        calculateDuration();
    });
    
    // Form validation
    document.getElementById('cutiForm').addEventListener('submit', function(e) {
        const checkboxes = this.querySelectorAll('input[type="checkbox"][required]');
        let allChecked = true;
        
        checkboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                allChecked = false;
            }
        });
        
        if (!allChecked) {
            e.preventDefault();
            alert('Anda harus menyetujui ketentuan sebelum mengajukan cuti');
            return false;
        }
        
        if (jenisCuti.value === 'Cuti Tahunan' && <?= $sisa_cuti_tahunan ?> <= 0) {
            e.preventDefault();
            alert('Sisa cuti tahunan Anda sudah habis');
            return false;
        }
        
        return confirm('Apakah Anda yakin ingin mengajukan cuti ini?');
    });
});
</script>