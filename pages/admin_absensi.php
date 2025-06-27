<?php
// pages/admin_absensi.php - Halaman Management Absensi Admin
require_once 'config.php';

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle CRUD operations
$message = '';
$message_type = '';

// Delete record
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM absensi WHERE id = :id");
        $stmt->bindParam(':id', $_GET['delete']);
        if ($stmt->execute()) {
            $message = "Data absensi berhasil dihapus!";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Update record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_absensi'])) {
    try {
        $stmt = $db->prepare("UPDATE absensi SET 
            tanggal = :tanggal, 
            jam_masuk = :jam_masuk, 
            jam_keluar = :jam_keluar, 
            status = :status, 
            keterangan = :keterangan 
            WHERE id = :id");
        
        $stmt->execute([
            ':tanggal' => $_POST['tanggal'],
            ':jam_masuk' => $_POST['jam_masuk'] ?: null,
            ':jam_keluar' => $_POST['jam_keluar'] ?: null,
            ':status' => $_POST['status'],
            ':keterangan' => $_POST['keterangan'] ?: null,
            ':id' => $_POST['absensi_id']
        ]);
        
        $message = "Data absensi berhasil diupdate!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Add new record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_absensi'])) {
    try {
        $stmt = $db->prepare("INSERT INTO absensi (user_id, tanggal, jam_masuk, jam_keluar, status, keterangan) 
            VALUES (:user_id, :tanggal, :jam_masuk, :jam_keluar, :status, :keterangan)");
        
        $stmt->execute([
            ':user_id' => $_POST['user_id'],
            ':tanggal' => $_POST['tanggal'],
            ':jam_masuk' => $_POST['jam_masuk'] ?: null,
            ':jam_keluar' => $_POST['jam_keluar'] ?: null,
            ':status' => $_POST['status'],
            ':keterangan' => $_POST['keterangan'] ?: null
        ]);
        
        $message = "Data absensi berhasil ditambahkan!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Filter parameters
$filter_date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-01');
$filter_date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-t');
$filter_user = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query conditions
$where_conditions = ["DATE(a.tanggal) BETWEEN :date_start AND :date_end"];
$params = [
    ':date_start' => $filter_date_start,
    ':date_end' => $filter_date_end
];

if (!empty($filter_user)) {
    $where_conditions[] = "a.user_id = :user_id";
    $params[':user_id'] = $filter_user;
}

if (!empty($filter_status)) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $filter_status;
}

$where_clause = implode(' AND ', $where_conditions);

// Get attendance records
$query = "SELECT a.*, u.nama, u.nip, j.nama_jabatan,
          CASE 
            WHEN a.jam_masuk > '08:30:00' THEN 'Terlambat'
            WHEN a.status = 'hadir' THEN 'Tepat Waktu'
            ELSE UPPER(LEFT(a.status, 1)) || LOWER(SUBSTRING(a.status, 2))
          END as status_display
          FROM absensi a 
          JOIN users u ON a.user_id = u.id 
          LEFT JOIN jabatan j ON u.jabatan_id = j.id 
          WHERE $where_clause 
          ORDER BY a.tanggal DESC, u.nama ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$absensi_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users for dropdown
$stmt_users = $db->prepare("SELECT id, nama, nip FROM users WHERE role != 'admin' ORDER BY nama");
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_absensi,
    COUNT(CASE WHEN status = 'hadir' THEN 1 END) as total_hadir,
    COUNT(CASE WHEN status = 'terlambat' THEN 1 END) as total_terlambat,
    COUNT(CASE WHEN status = 'izin' THEN 1 END) as total_izin,
    COUNT(CASE WHEN status = 'alpha' THEN 1 END) as total_alpha,
    COUNT(DISTINCT user_id) as total_karyawan
    FROM absensi a 
    WHERE $where_clause";

$stmt_stats = $db->prepare($stats_query);
$stmt_stats->execute($params);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Get monthly statistics for each employee
$monthly_stats_query = "SELECT 
    u.id, u.nama, u.nip,
    COUNT(*) as total_absensi,
    COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
    COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) as terlambat,
    COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
    COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha
    FROM users u 
    LEFT JOIN absensi a ON u.id = a.user_id AND DATE(a.tanggal) BETWEEN :date_start AND :date_end
    WHERE u.role != 'admin'
    GROUP BY u.id, u.nama, u.nip
    ORDER BY u.nama";

$stmt_monthly = $db->prepare($monthly_stats_query);
$stmt_monthly->execute([':date_start' => $filter_date_start, ':date_end' => $filter_date_end]);
$monthly_stats = $stmt_monthly->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog"></i> Management Absensi</h2>
    <div class="btn-group">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Tambah Data
        </button>
        <div class="btn-group">
            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="printAllReport()">
                    <i class="fas fa-users"></i> Semua Karyawan
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="showPrintEmployeeModal()">
                    <i class="fas fa-user"></i> Per Karyawan
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="printSummaryReport()">
                    <i class="fas fa-chart-bar"></i> Laporan Ringkasan
                </a></li>
            </ul>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="card text-center stats-card">
            <div class="card-body">
                <i class="fas fa-users fa-2x mb-2 text-primary"></i>
                <h4><?= $stats['total_karyawan'] ?></h4>
                <small>Karyawan</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card text-center stats-card">
            <div class="card-body">
                <i class="fas fa-calendar-check fa-2x mb-2 text-info"></i>
                <h4><?= $stats['total_absensi'] ?></h4>
                <small>Total Absensi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-success text-white text-center stats-card">
            <div class="card-body">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h4><?= $stats['total_hadir'] ?></h4>
                <small>Hadir</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-warning text-white text-center stats-card">
            <div class="card-body">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h4><?= $stats['total_terlambat'] ?></h4>
                <small>Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-info text-white text-center stats-card">
            <div class="card-body">
                <i class="fas fa-info-circle fa-2x mb-2"></i>
                <h4><?= $stats['total_izin'] ?></h4>
                <small>Izin</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card bg-danger text-white text-center stats-card">
            <div class="card-body">
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
            <input type="hidden" name="page" value="admin_absensi">
            
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" name="date_start" value="<?= $filter_date_start ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" name="date_end" value="<?= $filter_date_end ?>">
            </div>
            
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
                <a href="?page=admin_absensi" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card" id="printable-area">
    <div class="card-header d-print-none">
        <h5><i class="fas fa-table"></i> Data Absensi</h5>
    </div>
    
    <!-- Print Header -->
    <div class="d-none d-print-block text-center p-3" id="print-header">
        <h3>LAPORAN ABSENSI KARYAWAN</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($filter_date_start)) ?> - <?= date('d/m/Y', strtotime($filter_date_end)) ?></p>
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <hr>
    </div>
    
    <div class="card-body">
        <?php if (count($absensi_records) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="absensi-table">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th class="d-print-none">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absensi_records as $index => $record): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= date('d/m/Y', strtotime($record['tanggal'])) ?></td>
                        <td><?= $record['nip'] ?></td>
                        <td><?= $record['nama'] ?></td>
                        <td><?= $record['nama_jabatan'] ?: '-' ?></td>
                        <td><?= $record['jam_masuk'] ? date('H:i', strtotime($record['jam_masuk'])) : '-' ?></td>
                        <td><?= $record['jam_keluar'] ? date('H:i', strtotime($record['jam_keluar'])) : '-' ?></td>
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
                        <td><?= $record['keterangan'] ?: '-' ?></td>
                        <td class="d-print-none">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-info" onclick="viewDetail(<?= $record['id'] ?>)" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning" onclick="editRecord(<?= htmlspecialchars(json_encode($record)) ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" onclick="deleteRecord(<?= $record['id'] ?>)" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
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

<!-- Summary Report (Hidden, for printing) -->
<div id="summary-report" class="d-none">
    <div class="text-center p-3">
        <h3>LAPORAN RINGKASAN ABSENSI</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($filter_date_start)) ?> - <?= date('d/m/Y', strtotime($filter_date_end)) ?></p>
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <hr>
    </div>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Total</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Izin</th>
                <th>Alpha</th>
                <th>Persentase Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthly_stats as $index => $emp): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= $emp['nip'] ?></td>
                <td><?= $emp['nama'] ?></td>
                <td><?= $emp['total_absensi'] ?></td>
                <td><?= $emp['hadir'] ?></td>
                <td><?= $emp['terlambat'] ?></td>
                <td><?= $emp['izin'] ?></td>
                <td><?= $emp['alpha'] ?></td>
                <td>
                    <?php 
                    $percentage = $emp['total_absensi'] > 0 ? 
                        round((($emp['hadir'] + $emp['terlambat']) / $emp['total_absensi'] * 100), 1) : 0;
                    echo $percentage . '%';
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Karyawan *</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">Pilih Karyawan</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= $user['nip'] ?> - <?= $user['nama'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" class="form-control" name="tanggal" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jam Masuk</label>
                            <input type="time" class="form-control" name="jam_masuk">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jam Keluar</label>
                            <input type="time" class="form-control" name="jam_keluar">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="hadir">Hadir</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_absensi" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="absensi_id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Karyawan</label>
                        <input type="text" class="form-control" id="edit_employee" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" class="form-control" name="tanggal" id="edit_tanggal" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jam Masuk</label>
                            <input type="time" class="form-control" name="jam_masuk" id="edit_jam_masuk">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jam Keluar</label>
                            <input type="time" class="form-control" name="jam_keluar" id="edit_jam_keluar">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="hadir">Hadir</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_absensi" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Print Employee Modal -->
<div class="modal fade" id="printEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cetak Laporan Per Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Karyawan</label>
                    <select class="form-select" id="print_employee_select">
                        <option value="">Pilih Karyawan</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= $user['nip'] ?> - <?= $user['nama'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="print_date_start" value="<?= $filter_date_start ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="print_date_end" value="<?= $filter_date_end ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="printEmployeeReport()">Cetak</button>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-content">
                <!-- Content will be loaded by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none { display: none !important; }
    .d-print-block { display: block !important; }
    body { font-size: 12px; }
    .table { font-size: 11px; }
    .card { border: none; box-shadow: none; }
    .badge { 
        background-color: #6c757d !important; 
        color: white !important;
        -webkit-print-color-adjust: exact;
    }
    .bg-success { background-color: #28a745 !important; }
    .bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
    .bg-info { background-color: #17a2b8 !important; }
    .bg-danger { background-color: #dc3545 !important; }
}

.stats-card {