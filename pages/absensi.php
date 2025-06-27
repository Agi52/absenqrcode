<?php
// pages/absensi.php - Halaman Data Absensi
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Filter parameters
$filter_date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-01');
$filter_date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-t');
$filter_user = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query conditions and parameters
$where_conditions = ["DATE(a.tanggal) BETWEEN :date_start AND :date_end"];
$params = [
    ':date_start' => $filter_date_start,
    ':date_end' => $filter_date_end
];

// Add user filter if specified
if (!empty($filter_user)) {
    $where_conditions[] = "a.user_id = :user_id";
    $params[':user_id'] = $filter_user;
}

// Add status filter if specified
if (!empty($filter_status)) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $filter_status;
}

// For non-admin users, only show their own records
if ($_SESSION['role'] != 'admin') {
    $where_conditions[] = "a.user_id = :current_user_id";
    $params[':current_user_id'] = $_SESSION['user_id'];
}

$where_clause = implode(' AND ', $where_conditions);

// Get attendance records
$query = "SELECT a.*, u.nama, u.nip, j.nama_jabatan 
          FROM absensi a 
          JOIN users u ON a.user_id = u.id 
          LEFT JOIN jabatan j ON u.jabatan_id = j.id 
          WHERE $where_clause 
          ORDER BY a.tanggal DESC, a.jam_masuk DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$absensi_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users for filter dropdown (admin only)
$users = [];
if ($_SESSION['role'] == 'admin') {
    $stmt_users = $db->prepare("SELECT id, nama, nip FROM users WHERE role != 'admin' ORDER BY nama");
    $stmt_users->execute();
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
}

// Get statistics using the same parameters
$stats_query = "SELECT 
    COUNT(*) as total_absensi,
    COUNT(CASE WHEN status = 'hadir' THEN 1 END) as total_hadir,
    COUNT(CASE WHEN status = 'terlambat' THEN 1 END) as total_terlambat,
    COUNT(CASE WHEN status = 'izin' THEN 1 END) as total_izin,
    COUNT(CASE WHEN status = 'alpha' THEN 1 END) as total_alpha
    FROM absensi a 
    WHERE $where_clause";

$stmt_stats = $db->prepare($stats_query);
$stmt_stats->execute($params);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-check"></i> Riwayat Absensi</h2>
    <button class="btn btn-success" onclick="printReport()">
        <i class="fas fa-print"></i> Cetak Laporan
    </button>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h4><?= $stats['total_absensi'] ?></h4>
                <small>Total Absensi</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h4><?= $stats['total_hadir'] ?></h4>
                <small>Hadir</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h4><?= $stats['total_terlambat'] ?></h4>
                <small>Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-2x mb-2"></i>
                <h4><?= $stats['total_alpha'] ?></h4>
                <small>Alpha</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Filter Data</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="absensi">
            
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" name="date_start" value="<?= $filter_date_start ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" name="date_end" value="<?= $filter_date_end ?>">
            </div>
            
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <div class="col-md-3">
                <label class="form-label">Karyawan</label>
                <select class="form-select" name="user_id">
                    <option value="">Semua Karyawan</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= $filter_user == $user['id'] ? 'selected' : '' ?>>
                            <?= $user['nip'] ?> - <?= $user['nama'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="hadir" <?= $filter_status == 'hadir' ? 'selected' : '' ?>>Hadir</option>
                    <option value="terlambat" <?= $filter_status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                    <option value="izin" <?= $filter_status == 'izin' ? 'selected' : '' ?>>Izin</option>
                    <option value="alpha" <?= $filter_status == 'alpha' ? 'selected' : '' ?>>Alpha</option>
                </select>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="?page=absensi" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card" id="printable-area">
    <div class="card-header d-print-none">
        <h5><i class="fas fa-table"></i> Riwayat Absensi</h5>
    </div>
    
    <!-- Print Header (only visible when printing) -->
    <div class="d-none d-print-block text-center p-3">
        <h3>LAPORAN ABSENSI</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($filter_date_start)) ?> - <?= date('d/m/Y', strtotime($filter_date_end)) ?></p>
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <hr>
    </div>
    
    <div class="card-body">
        <?php if (count($absensi_records) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <?php endif; ?>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                        <th>Lokasi</th>
                        <th class="d-print-none">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absensi_records as $index => $record): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= date('d/m/Y', strtotime($record['tanggal'])) ?></td>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <td><?= $record['nip'] ?></td>
                        <td><?= $record['nama'] ?></td>
                        <td><?= $record['nama_jabatan'] ?: '-' ?></td>
                        <?php endif; ?>
                        <td>
                            <?= $record['jam_masuk'] ? date('H:i:s', strtotime($record['jam_masuk'])) : '-' ?>
                        </td>
                        <td>
                            <?= $record['jam_keluar'] ? date('H:i:s', strtotime($record['jam_keluar'])) : '-' ?>
                        </td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($record['status']) {
                                case 'hadir': $status_class = 'bg-success'; break;
                                case 'terlambat': $status_class = 'bg-warning'; break;
                                case 'izin': $status_class = 'bg-info'; break;
                                case 'alpha': $status_class = 'bg-danger'; break;
                            }
                            ?>
                            <span class="badge <?= $status_class ?>"><?= ucfirst($record['status']) ?></span>
                        </td>
                        <td>
                            <?php if (isset($record['lokasi']) && $record['lokasi']): ?>
                                <small><?= substr($record['lokasi'], 0, 50) ?>...</small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="d-print-none">
                            <button class="btn btn-sm btn-info" onclick="viewDetail(<?= $record['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <button class="btn btn-sm btn-warning" onclick="editRecord(<?= $record['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Tidak ada data absensi</h5>
            <p class="text-muted">Belum ada data absensi pada periode yang dipilih</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Detail content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printable-area, #printable-area * {
        visibility: visible !important;
    }
    #printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        z-index: 9999;
        background: #fff;
        box-shadow: none;
        font-size: 11px !important; /* Ukuran font lebih kecil untuk print */
    }
    #printable-area h3,
    #printable-area h5 {
        font-size: 16px !important;
    }
    #printable-area table {
        font-size: 11px !important;
    }
    #printable-area th,
    #printable-area td {
        padding: 4px 6px !important;
    }
}
</style>

<script>
function printReport() {
    window.print();
}

function viewDetail(id) {
    fetch(`ajax/get_absensi_detail.php?id=${id}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('detailContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal memuat detail absensi');
        });
}

function editRecord(id) {
    // Implement edit functionality
    alert('Fitur edit akan segera tersedia');
}

// Auto-submit form when date changes
document.querySelectorAll('input[type="date"]').forEach(input => {
    input.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>