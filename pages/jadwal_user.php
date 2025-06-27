<?php
// pages/jadwal_mengajar.php - Halaman Jadwal Mengajar untuk Guru
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is a teacher
if ($_SESSION['role'] != 'guru' && $_SESSION['role'] != 'user') {
    header('Location: index.php?page=dashboard');
    exit();
}

// Get current week date range
$current_week = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_week)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($current_week)));

// Check if required tables exist
$tables_exist = true;
$required_tables = ['kelas', 'mata_pelajaran', 'jadwal_mengajar'];

try {
    foreach ($required_tables as $table) {
        $check_tables = $db->query("SHOW TABLES LIKE '$table'");
        if ($check_tables->rowCount() == 0) {
            $tables_exist = false;
            break;
        }
    }
} catch (Exception $e) {
    $tables_exist = false;
}

// Initialize variables
$jadwal_mengajar = [];
$total_jam_perminggu = 0;
$mata_pelajaran_list = [];
$teaching_schedule = [];

// Get teaching schedule if tables exist
if ($tables_exist) {
    try {
        $query_mengajar = "SELECT jm.*, mp.nama_pelajaran, k.nama_kelas, k.tingkat,
                                  CASE 
                                      WHEN jm.hari = 1 THEN 'Senin'
                                      WHEN jm.hari = 2 THEN 'Selasa'
                                      WHEN jm.hari = 3 THEN 'Rabu'
                                      WHEN jm.hari = 4 THEN 'Kamis'
                                      WHEN jm.hari = 5 THEN 'Jumat'
                                      WHEN jm.hari = 6 THEN 'Sabtu'
                                      WHEN jm.hari = 0 THEN 'Minggu'
                                  END as nama_hari,
                                  TIMEDIFF(jm.jam_selesai, jm.jam_mulai) as durasi
                           FROM jadwal_mengajar jm
                           JOIN mata_pelajaran mp ON jm.mata_pelajaran_id = mp.id
                           JOIN kelas k ON jm.kelas_id = k.id
                           WHERE jm.user_id = :user_id
                           AND jm.status = 'aktif'
                           ORDER BY jm.hari, jm.jam_mulai";
        
        $stmt_mengajar = $db->prepare($query_mengajar);
        $stmt_mengajar->execute([':user_id' => $_SESSION['user_id']]);
        $teaching_schedule = $stmt_mengajar->fetchAll(PDO::FETCH_ASSOC);
        
        // Process schedule data
        foreach ($teaching_schedule as $schedule) {
            $jadwal_mengajar[$schedule['hari']][] = $schedule;
            
            // Calculate duration in hours
            $duration_parts = explode(':', $schedule['durasi']);
            $duration_hours = intval($duration_parts[0]) + (intval($duration_parts[1]) / 60);
            $total_jam_perminggu += $duration_hours;
            
            // Collect unique subjects
            if (!in_array($schedule['nama_pelajaran'], $mata_pelajaran_list)) {
                $mata_pelajaran_list[] = $schedule['nama_pelajaran'];
            }
        }
        
    } catch (Exception $e) {
        $tables_exist = false;
    }
}

// Day names in Indonesian
$day_names = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Calculate unique classes
$unique_classes = [];
foreach ($teaching_schedule as $schedule) {
    if (!in_array($schedule['nama_kelas'], $unique_classes)) {
        $unique_classes[] = $schedule['nama_kelas'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chalkboard-teacher"></i> Jadwal Mengajar</h2>
    <button class="btn btn-success" onclick="printJadwal()">
        <i class="fas fa-print"></i> Cetak Jadwal
    </button>
</div>

<?php if (!$tables_exist): ?>
<div class="alert alert-warning" role="alert">
    <h5><i class="fas fa-exclamation-triangle"></i> Peringatan</h5>
    <p>Tabel database untuk jadwal mengajar belum dibuat. Silakan hubungi administrator.</p>
    <p>Tabel yang diperlukan: <?= implode(', ', $required_tables) ?></p>
</div>
<?php else: ?>

<!-- Layout dengan 2 kolom seimbang -->
<div class="row mb-4">
    <!-- Teacher Info Card - Kolom Kiri -->
    <div class="col-md-6">
        <div class="card shadow-sm border-primary h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Informasi Guru</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="fw-bold"><i class="fas fa-user-circle me-2"></i>Nama</span>
                    <div class="text-secondary ms-4"><?= htmlspecialchars($_SESSION['nama']) ?></div>
                </div>
                <div class="mb-3">
                    <span class="fw-bold"><i class="fas fa-id-card me-2"></i>NIP</span>
                    <div class="text-secondary ms-4"><?= isset($_SESSION['nip']) && $_SESSION['nip'] ? htmlspecialchars($_SESSION['nip']) : '-' ?></div>
                </div>
                <div class="mb-3">
                    <span class="fw-bold"><i class="fas fa-clock me-2"></i>Total Jam/Minggu</span>
                    <div class="text-secondary ms-4"><?= number_format($total_jam_perminggu, 1) ?> Jam</div>
                </div>
                <div class="mb-0">
                    <span class="fw-bold"><i class="fas fa-book me-2"></i>Mata Pelajaran</span>
                    <div class="text-secondary ms-4"><?= implode(', ', $mata_pelajaran_list) ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Card - Kolom Kanan -->
    <div class="col-md-6">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistik Mengajar</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-item">
                            <h4 class="text-primary"><?= count($teaching_schedule) ?></h4>
                            <p class="text-muted mb-0">Total Sesi/Minggu</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <h4 class="text-success"><?= number_format($total_jam_perminggu, 1) ?></h4>
                            <p class="text-muted mb-0">Jam/Minggu</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <h4 class="text-info"><?= count($mata_pelajaran_list) ?></h4>
                            <p class="text-muted mb-0">Mata Pelajaran</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <h4 class="text-warning"><?= count($unique_classes) ?></h4>
                            <p class="text-muted mb-0">Kelas Diampu</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Weekly Schedule Table -->
<div class="card" id="jadwal-table">
    <div class="card-header d-print-none">
        <h5><i class="fas fa-table"></i> Jadwal Mengajar Mingguan</h5>
    </div>
    
    <!-- Print Header -->
    <div class="d-none d-print-block text-center p-3">
        <h3>JADWAL MENGAJAR</h3>
        <h4>
            <?= htmlspecialchars($_SESSION['nama']) ?> - 
            <?= isset($_SESSION['nip']) && $_SESSION['nip'] ? htmlspecialchars($_SESSION['nip']) : '-' ?>
        </h4>
        <p>Periode: <?= date('d M', strtotime($week_start)) ?> - <?= date('d M Y', strtotime($week_end)) ?></p>
        <hr>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-primary">
                    <tr>
                        <th width="12%">Hari</th>
                        <th width="15%">Waktu</th>
                        <th width="25%">Mata Pelajaran</th>
                        <th width="18%">Kelas</th>
                        <th width="8%">Ruang</th>
                        <th width="22%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jadwal_mengajar)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                            <br>Tidak ada jadwal mengajar
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach (range(1, 6) as $day_index): ?>
                            <?php if (isset($jadwal_mengajar[$day_index])): ?>
                                <?php foreach ($jadwal_mengajar[$day_index] as $key => $schedule): ?>
                                <tr>
                                    <?php if ($key == 0): ?>
                                    <td rowspan="<?= count($jadwal_mengajar[$day_index]) ?>" class="align-middle">
                                        <strong class="text-primary"><?= $day_names[$day_index] ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?= date('d M', strtotime($week_start . ' +' . ($day_index-1) . ' days')) ?>
                                        </small>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <strong><?= date('H:i', strtotime($schedule['jam_mulai'])) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= date('H:i', strtotime($schedule['jam_selesai'])) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?= $schedule['nama_pelajaran'] ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info fs-6"><?= $schedule['nama_kelas'] ?></span>
                                        <?php if ($schedule['tingkat']): ?>
                                        <br><small class="text-muted">Tingkat <?= $schedule['tingkat'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $schedule['ruangan'] ?: '-' ?>
                                    </td>
                                    <td>
                                        <?= $schedule['keterangan'] ?: '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
.stat-item {
    text-align: center;
    padding: 0.75rem;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.stat-item h4 {
    margin-bottom: 0.25rem;
    font-weight: bold;
    font-size: 1.5rem;
}

.stat-item p {
    font-size: 0.85rem;
    margin-bottom: 0;
}

/* Memastikan card memiliki tinggi yang sama */
.h-100 {
    height: 100% !important;
}

@media print {
    body {
        margin: 0;
        padding: 15px;
        background: white !important;
        font-family: Arial, sans-serif;
    }
    
    .d-print-none {
        display: none !important;
    }
    
    .d-print-block {
        display: block !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
    }
    
    .card-header {
        background: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6 !important;
        color: #000 !important;
        padding: 10px 15px !important;
    }
    
    .table {
        font-size: 11px;
        margin-bottom: 0 !important;
    }
    
    .table th,
    .table td {
        padding: 6px 8px !important;
        border: 1px solid #000 !important;
    }
    
    .badge {
        font-size: 0.7em !important;
        background-color: #6c757d !important;
        color: white !important;
    }
    
    .text-primary,
    .text-success,
    .text-info,
    .text-warning {
        color: #000 !important;
    }
    
    /* Hide semua elemen kecuali jadwal table */
    body > *:not(#print-content) {
        display: none !important;
    }
}
</style>

<script>
function printJadwal() {
    // Ambil hanya tabel jadwal untuk dicetak
    const jadwalTable = document.getElementById('jadwal-table');
    const printContent = jadwalTable.outerHTML;
    
    const win = window.open('', '', 'width=900,height=700');
    win.document.write(`
        <html>
        <head>
            <title>Cetak Jadwal Mengajar</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <style>
                body { 
                    background: #fff !important; 
                    margin: 20px; 
                    font-family: Arial, sans-serif; 
                    color: #000 !important;
                }
                .card { 
                    border: none !important; 
                    box-shadow: none !important; 
                    margin: 0 !important;
                }
                .card-header { 
                    background: #f8f9fa !important; 
                    border-bottom: 2px solid #dee2e6 !important; 
                    color: #000 !important; 
                    padding: 10px 15px !important;
                }
                .d-print-none { 
                    display: none !important; 
                }
                .d-print-block { 
                    display: block !important; 
                }
                .table { 
                    font-size: 11px; 
                    margin-bottom: 0 !important;
                }
                .table th, .table td { 
                    padding: 6px 8px !important; 
                    border: 1px solid #000 !important;
                }
                .table-primary th {
                    background-color: #e7f1ff !important;
                    color: #000 !important;
                }
                .badge { 
                    font-size: 0.7em !important; 
                    background-color: #6c757d !important;
                    color: white !important;
                    border: 1px solid #000 !important;
                }
                .text-primary, .text-success, .text-info, .text-warning, .text-muted {
                    color: #000 !important;
                }
                hr {
                    border-color: #000 !important;
                }
            </style>
        </head>
        <body>
            <div id="print-content">
                ${printContent}
            </div>
            <script>
                window.onload = function() {
                    setTimeout(function() { 
                        window.print(); 
                    }, 500);
                };
                window.onafterprint = function() {
                    setTimeout(function() { 
                        window.close(); 
                    }, 100);
                };
            <\/script>
        </body>
        </html>
    `);
    win.document.close();
    win.focus();
}

// Keyboard shortcut for print
document.addEventListener('keydown', function(e) {
    if (e.key === 'p' && e.ctrlKey) {
        e.preventDefault();
        printJadwal();
    }
});
</script>