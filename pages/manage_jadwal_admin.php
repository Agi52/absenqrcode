<?php
// pages/manage_jadwal_mengajar.php - Halaman Manage Jadwal Mengajar untuk Admin
require_once 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if ($_SESSION['role'] != 'admin') {
    header('Location: index.php?page=dashboard');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle CRUD operations
$message = '';
$message_type = '';

// Check if required tables exist
$tables_exist = true;
try {
    $check_tables = $db->query("SHOW TABLES LIKE 'kelas'");
    if ($check_tables->rowCount() == 0) $tables_exist = false;
    
    $check_tables = $db->query("SHOW TABLES LIKE 'mata_pelajaran'");
    if ($check_tables->rowCount() == 0) $tables_exist = false;
    
    $check_tables = $db->query("SHOW TABLES LIKE 'jadwal_mengajar'");
    if ($check_tables->rowCount() == 0) $tables_exist = false;
    
    $check_tables = $db->query("SHOW TABLES LIKE 'users'");
    if ($check_tables->rowCount() == 0) $tables_exist = false;
} catch (Exception $e) {
    $tables_exist = false;
}

if ($tables_exist) {
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    try {
                        $query = "INSERT INTO jadwal_mengajar (user_id, mata_pelajaran_id, kelas_id, hari, jam_mulai, jam_selesai, ruangan, keterangan, status, created_at) 
                                 VALUES (:user_id, :mata_pelajaran_id, :kelas_id, :hari, :jam_mulai, :jam_selesai, :ruangan, :keterangan, 'aktif', NOW())";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ':user_id' => $_POST['guru_id'],
                            ':mata_pelajaran_id' => $_POST['mata_pelajaran_id'],
                            ':kelas_id' => $_POST['kelas_id'],
                            ':hari' => $_POST['hari'],
                            ':jam_mulai' => $_POST['jam_mulai'],
                            ':jam_selesai' => $_POST['jam_selesai'],
                            ':ruangan' => $_POST['ruangan'],
                            ':keterangan' => $_POST['keterangan']
                        ]);
                        $message = 'Jadwal mengajar berhasil ditambahkan!';
                        $message_type = 'success';
                    } catch (Exception $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;
                    
                case 'edit':
                    try {
                        $query = "UPDATE jadwal_mengajar SET user_id = :user_id, mata_pelajaran_id = :mata_pelajaran_id, 
                                 kelas_id = :kelas_id, hari = :hari, jam_mulai = :jam_mulai, jam_selesai = :jam_selesai, 
                                 ruangan = :ruangan, keterangan = :keterangan, updated_at = NOW() 
                                 WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ':id' => $_POST['id'],
                            ':user_id' => $_POST['guru_id'],
                            ':mata_pelajaran_id' => $_POST['mata_pelajaran_id'],
                            ':kelas_id' => $_POST['kelas_id'],
                            ':hari' => $_POST['hari'],
                            ':jam_mulai' => $_POST['jam_mulai'],
                            ':jam_selesai' => $_POST['jam_selesai'],
                            ':ruangan' => $_POST['ruangan'],
                            ':keterangan' => $_POST['keterangan']
                        ]);
                        $message = 'Jadwal mengajar berhasil diperbarui!';
                        $message_type = 'success';
                    } catch (Exception $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;
                    
                case 'delete':
                    try {
                        $query = "DELETE FROM jadwal_mengajar WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([':id' => $_POST['id']]);
                        $message = 'Jadwal mengajar berhasil dihapus!';
                        $message_type = 'success';
                    } catch (Exception $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;
                    
                case 'toggle_status':
                    try {
                        $new_status = $_POST['current_status'] == 'aktif' ? 'nonaktif' : 'aktif';
                        $query = "UPDATE jadwal_mengajar SET status = :status, updated_at = NOW() WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ':id' => $_POST['id'],
                            ':status' => $new_status
                        ]);
                        $message = 'Status jadwal berhasil diperbarui!';
                        $message_type = 'success';
                    } catch (Exception $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;
            }
        }
    }

    // Get filter parameters
    $filter_guru = isset($_GET['guru']) ? $_GET['guru'] : '';
    $filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
    $filter_mapel = isset($_GET['mapel']) ? $_GET['mapel'] : '';
    $filter_hari = isset($_GET['hari']) ? $_GET['hari'] : '';

    // Get jadwal mengajar with filters
    $where_conditions = [];
    $params = [];

    if ($filter_guru) {
        $where_conditions[] = "jm.user_id = :user_id";
        $params[':user_id'] = $filter_guru;
    }
    if ($filter_kelas) {
        $where_conditions[] = "jm.kelas_id = :kelas_id";
        $params[':kelas_id'] = $filter_kelas;
    }
    if ($filter_mapel) {
        $where_conditions[] = "jm.mata_pelajaran_id = :mapel_id";
        $params[':mapel_id'] = $filter_mapel;
    }
    if ($filter_hari) {
        $where_conditions[] = "jm.hari = :hari";
        $params[':hari'] = $filter_hari;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $query_jadwal = "SELECT jm.*, u.nama as nama_guru, u.nip, mp.nama_pelajaran, k.nama_kelas, k.tingkat,
                            CASE 
                                WHEN jm.hari = 1 THEN 'Senin'
                                WHEN jm.hari = 2 THEN 'Selasa'
                                WHEN jm.hari = 3 THEN 'Rabu'
                                WHEN jm.hari = 4 THEN 'Kamis'
                                WHEN jm.hari = 5 THEN 'Jumat'
                                WHEN jm.hari = 6 THEN 'Sabtu'
                            END as nama_hari,
                            TIMEDIFF(jm.jam_selesai, jm.jam_mulai) as durasi
                     FROM jadwal_mengajar jm
                     JOIN users u ON jm.user_id = u.id
                     JOIN mata_pelajaran mp ON jm.mata_pelajaran_id = mp.id
                     JOIN kelas k ON jm.kelas_id = k.id
                     $where_clause
                     ORDER BY jm.hari, jm.jam_mulai, u.nama";
    
    $stmt_jadwal = $db->prepare($query_jadwal);
    $stmt_jadwal->execute($params);
    $jadwal_list = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

    // Get data for dropdowns
    $guru_list = $db->query("
        SELECT id, nama
        FROM users
        WHERE role = 'user'
        ORDER BY nama
    ")->fetchAll(PDO::FETCH_ASSOC);
    $mapel_list = $db->query("SELECT id, nama_pelajaran FROM mata_pelajaran ORDER BY nama_pelajaran")->fetchAll(PDO::FETCH_ASSOC);
    $kelas_list = $db->query("SELECT id, nama_kelas, tingkat FROM kelas ORDER BY tingkat, nama_kelas")->fetchAll(PDO::FETCH_ASSOC);
}

// Day names
$day_names = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-alt"></i> Jadwal Mengajar</h2>
    <div class="btn-group">
        <?php if ($tables_exist): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addJadwalModal">
            <i class="fas fa-plus"></i> Tambah Jadwal
        </button>
        <button class="btn btn-info" onclick="exportJadwal()">
            <i class="fas fa-download"></i> Export
        </button>
        <button class="btn btn-warning" onclick="printJadwal()">
            <i class="fas fa-print"></i> Print
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!$tables_exist): ?>
<!-- Warning for missing tables -->
<div class="alert alert-warning" role="alert">
    <h5><i class="fas fa-exclamation-triangle"></i> Peringatan</h5>
    <p>Tabel database untuk jadwal mengajar belum dibuat. Silakan buat tabel berikut:</p>
    <ul>
        <li>Tabel <code>users</code> (untuk data guru)</li>
        <li>Tabel <code>kelas</code></li>
        <li>Tabel <code>mata_pelajaran</code></li>
        <li>Tabel <code>jadwal_mengajar</code></li>
    </ul>
</div>
<?php else: ?>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Filter Jadwal</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="page" value="manage_jadwal_admin">
            
            <div class="col-md-3">
                <label class="form-label">Guru</label>
                <select name="guru" class="form-select">
                    <option value="">Semua Guru</option>
                    <?php foreach ($guru_list as $guru): ?>
                    <option value="<?= $guru['id'] ?>" <?= $filter_guru == $guru['id'] ? 'selected' : '' ?>>
                        <?= $guru['nama'] ?> (<?= $guru['id'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas_list as $kelas): ?>
                    <option value="<?= $kelas['id'] ?>" <?= $filter_kelas == $kelas['id'] ? 'selected' : '' ?>>
                        <?= $kelas['nama_kelas'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Mata Pelajaran</label>
                <select name="mapel" class="form-select">
                    <option value="">Semua Mapel</option>
                    <?php foreach ($mapel_list as $mapel): ?>
                    <option value="<?= $mapel['id'] ?>" <?= $filter_mapel == $mapel['id'] ? 'selected' : '' ?>>
                        <?= $mapel['nama_pelajaran'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Hari</label>
                <select name="hari" class="form-select">
                    <option value="">Semua Hari</option>
                    <?php foreach ($day_names as $key => $day): ?>
                    <option value="<?= $key ?>" <?= $filter_hari == $key ? 'selected' : '' ?>>
                        <?= $day ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Jadwal List -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list"></i> Daftar Jadwal Mengajar 
            <span class="badge bg-primary"><?= count($jadwal_list) ?> jadwal</span>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="jadwalTable">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Guru</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Hari</th>
                        <th>Waktu</th>
                        <th>Ruang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jadwal_list)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                            <br>Tidak ada jadwal mengajar
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($jadwal_list as $index => $jadwal): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= $jadwal['nama_guru'] ?></strong>
                                <br><small class="text-muted"><?= $jadwal['nip'] ? $jadwal['nip'] : '-' ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $jadwal['nama_pelajaran'] ?></span>
                            </td>
                            <td>
                                <strong><?= $jadwal['nama_kelas'] ?></strong>
                                <?php if ($jadwal['tingkat']): ?>
                                <br><small class="text-muted">Tingkat <?= $jadwal['tingkat'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-primary"><?= $jadwal['nama_hari'] ?></strong>
                            </td>
                            <td>
                                <strong><?= date('H:i', strtotime($jadwal['jam_mulai'])) ?></strong>
                                <br>
                                <small class="text-muted"><?= date('H:i', strtotime($jadwal['jam_selesai'])) ?></small>
                            </td>
                            <td><?= $jadwal['ruangan'] ?: '-' ?></td>
                            <td>
                                <span class="badge bg-<?= $jadwal['status'] == 'aktif' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($jadwal['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-warning" onclick="editJadwal(<?= htmlspecialchars(json_encode($jadwal)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-<?= $jadwal['status'] == 'aktif' ? 'secondary' : 'success' ?>" 
                                            onclick="toggleStatus(<?= $jadwal['id'] ?>, '<?= $jadwal['status'] ?>')">
                                        <i class="fas fa-<?= $jadwal['status'] == 'aktif' ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteJadwal(<?= $jadwal['id'] ?>, '<?= $jadwal['nama_guru'] ?>', '<?= $jadwal['nama_pelajaran'] ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<!-- Add Jadwal Modal -->
<div class="modal fade" id="addJadwalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Tambah Jadwal Mengajar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Guru <span class="text-danger">*</span></label>
                                <select name="guru_id" class="form-select" required>
                                    <option value="">Pilih Guru</option>
                                    <?php foreach ($guru_list as $guru): ?>
                                    <option value="<?= $guru['id'] ?>"><?= $guru['nama'] ?> (<?= $guru['id'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="mata_pelajaran_id" class="form-select" required>
                                    <option value="">Pilih Mata Pelajaran</option>
                                    <?php foreach ($mapel_list as $mapel): ?>
                                    <option value="<?= $mapel['id'] ?>"><?= $mapel['nama_pelajaran'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas_id" class="form-select" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id'] ?>"><?= $kelas['nama_kelas'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hari <span class="text-danger">*</span></label>
                                <select name="hari" class="form-select" required>
                                    <option value="">Pilih Hari</option>
                                    <?php foreach ($day_names as $key => $day): ?>
                                    <option value="<?= $key ?>"><?= $day ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_mulai" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ruangan</label>
                                <input type="text" name="ruangan" class="form-control" placeholder="Contoh: R.101">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control" placeholder="Keterangan tambahan">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Jadwal Modal -->
<div class="modal fade" id="editJadwalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Jadwal Mengajar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Guru <span class="text-danger">*</span></label>
                                <select name="guru_id" id="edit_guru_id" class="form-select" required>
                                    <option value="">Pilih Guru</option>
                                    <?php foreach ($guru_list as $guru): ?>
                                    <option value="<?= $guru['id'] ?>"><?= $guru['nama'] ?> (<?= $guru['id'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="mata_pelajaran_id" id="edit_mata_pelajaran_id" class="form-select" required>
                                    <option value="">Pilih Mata Pelajaran</option>
                                    <?php foreach ($mapel_list as $mapel): ?>
                                    <option value="<?= $mapel['id'] ?>"><?= $mapel['nama_pelajaran'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas_id" id="edit_kelas_id" class="form-select" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id'] ?>"><?= $kelas['nama_kelas'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hari <span class="text-danger">*</span></label>
                                <select name="hari" id="edit_hari" class="form-select" required>
                                    <option value="">Pilih Hari</option>
                                    <?php foreach ($day_names as $key => $day): ?>
                                    <option value="<?= $key ?>"><?= $day ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_mulai" id="edit_jam_mulai" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_selesai" id="edit_jam_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ruangan</label>
                                <input type="text" name="ruangan" id="edit_ruangan" class="form-control" placeholder="Contoh: R.101">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="keterangan" id="edit_keterangan" class="form-control" placeholder="Keterangan tambahan">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
.table th {
    background-color: #343a40;
    color: white;
    border-color: #454d55;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

@media print {
    .d-print-none {
        display: none !important;
    }
    
    .table {
        font-size: 11px;
    }
    
    .badge {
        font-size: 0.7em !important;
        color: #000 !important;
        background-color: #f0f0f0 !important;
        border: 1px solid #000;
    }
    
    .btn {
        display: none;
    }
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8em;
    }
    
    .btn-group-sm .btn {
        padding: 0.125rem 0.25rem;
        font-size: 0.7rem;
    }
}
</style>

<script>
function editJadwal(jadwal) {
    document.getElementById('edit_id').value = jadwal.id;
    document.getElementById('edit_guru_id').value = jadwal.user_id;
    document.getElementById('edit_mata_pelajaran_id').value = jadwal.mata_pelajaran_id;
    document.getElementById('edit_kelas_id').value = jadwal.kelas_id;
    document.getElementById('edit_hari').value = jadwal.hari;
    document.getElementById('edit_jam_mulai').value = jadwal.jam_mulai;
    document.getElementById('edit_jam_selesai').value = jadwal.jam_selesai;
    document.getElementById('edit_ruangan').value = jadwal.ruangan || '';
    document.getElementById('edit_keterangan').value = jadwal.keterangan || '';
    
    new bootstrap.Modal(document.getElementById('editJadwalModal')).show();
}

function deleteJadwal(id, namaGuru, namaPelajaran) {
    if (confirm(`Apakah Anda yakin ingin menghapus jadwal mengajar?\n\nGuru: ${namaGuru}\nMata Pelajaran: ${namaPelajaran}`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'aktif' ? 'nonaktif' : 'aktif';
    const message = `Apakah Anda yakin ingin mengubah status jadwal menjadi ${newStatus}?`;
    
    if (confirm(message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="current_status" value="${currentStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportJadwal() {
    const table = document.getElementById('jadwalTable');
    let csvContent = 'No,Guru,NIP,Mata Pelajaran,Kelas,Hari,Jam Mulai,Jam Selesai,Ruang,Status,Keterangan\n';
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        if (row.cells.length > 1) { // Skip empty state row
            const cells = row.cells;
            const rowData = [
                index + 1,
                cells[1].textContent.trim().split('\n')[0], // Nama guru
                cells[1].textContent.trim().split('\n')[1] || '', // NIP
                cells[2].textContent.trim(),
                cells[3].textContent.trim().split('\n')[0], // Nama kelas
                cells[4].textContent.trim(),
                cells[5].textContent.trim().split('\n')[0], // Jam mulai
                cells[5].textContent.trim().split('\n')[1] || '', // Jam selesai
                cells[6].textContent.trim(),
                cells[7].textContent.trim(),
                '' // Keterangan
            ];
            csvContent += rowData.map(cell => `"${cell}"`).join(',') + '\n';
        }
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `jadwal_mengajar_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printJadwal() {
    const printContent = `
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .badge { background-color: #f0f0f0; border: 1px solid #000; padding: 2px 5px; }
        </style>
        <div class="header">
            <h2>DAFTAR JADWAL MENGAJAR</h2>
            <p>Tanggal: ${new Date().toLocaleDateString('id-ID')}</p>
            <hr>
        </div>
        ${document.querySelector('#jadwalTable').outerHTML}
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Jadwal Mengajar</title>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const jamMulai = form.querySelector('input[name="jam_mulai"]');
            const jamSelesai = form.querySelector('input[name="jam_selesai"]');
            
            if (jamMulai && jamSelesai && jamMulai.value && jamSelesai.value) {
                if (jamMulai.value >= jamSelesai.value) {
                    e.preventDefault();
                    alert('Jam selesai harus lebih besar dari jam mulai!');
                    return false;
                }
            }
        });
    });
});

// Auto-refresh every 5 minutes to keep data current
setInterval(function() {
    if (document.visibilityState === 'visible') {
        // Only refresh if no modal is open
        if (!document.querySelector('.modal.show')) {
            location.reload();
        }
    }
}, 300000);

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey) {
        switch(e.key) {
            case 'n':
                e.preventDefault();
                new bootstrap.Modal(document.getElementById('addJadwalModal')).show();
                break;
            case 'p':
                e.preventDefault();
                printJadwal();
                break;
            case 'e':
                e.preventDefault();
                exportJadwal();
                break;
        }
    }
});

// Search functionality
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('jadwalTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length - 1; j++) { // Skip action column
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
}

// Add search input if needed
if (document.querySelector('#jadwalTable tbody tr').textContent.indexOf('Tidak ada jadwal') === -1) {
    const searchHtml = `
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" class="form-control" placeholder="Cari jadwal..." onkeyup="searchTable()">
            </div>
        </div>
    `;
    
    const cardBody = document.querySelector('.card:last-of-type .card-body');
    if (cardBody) {
        cardBody.insertAdjacentHTML('afterbegin', searchHtml);
    }
}
</script>