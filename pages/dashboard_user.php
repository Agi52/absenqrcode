<!-- pages/dashboard.php -->
<?php
// Get current month statistics
$current_month = date('Y-m');
$total_working_days = date('t'); // Total days in current month
$current_date = date('Y-m-d');

$stats_query = "SELECT 
    (SELECT COUNT(DISTINCT user_id) FROM absensi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$current_month') as kehadiran_bulan_ini,
    (SELECT COUNT(*) FROM users WHERE role = 'user') - (SELECT COUNT(DISTINCT user_id) FROM absensi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$current_month') as tidak_hadir_bulan_ini,
    (SELECT COUNT(*) FROM absensi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$current_month') as total_absensi_bulan,
    (SELECT COUNT(*) FROM users WHERE role = 'user') as total_karyawan";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Calculate attendance rate
$tingkat_kehadiran = $stats['total_karyawan'] > 0 ? 
    round(($stats['kehadiran_bulan_ini'] / $stats['total_karyawan']) * 100, 1) : 0;

// Get today's status for current user
$today_status_query = "SELECT jam_masuk, jam_keluar FROM absensi 
                      WHERE user_id = ? AND tanggal = CURDATE()";
$today_stmt = $db->prepare($today_status_query);
$today_stmt->execute([$_SESSION['user_id']]);
$today_status = $today_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2>Dashboard <small class="text-muted">Selamat datang, <?= $_SESSION['nama'] ?></small></h2>
        <p class="text-muted">Periode: <?= date('F Y') ?></p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-user-check fa-2x mb-3 text-success"></i>
                <h3><?= $stats['kehadiran_bulan_ini'] ?></h3>
                <p>Kehadiran Bulan Ini</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-user-times fa-2x mb-3 text-danger"></i>
                <h3><?= $stats['tidak_hadir_bulan_ini'] ?></h3>
                <p>Tidak Hadir Bulan Ini</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x mb-3 text-info"></i>
                <h3><?= $tingkat_kehadiran ?>%</h3>
                <p>Tingkat Kehadiran</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <?php if($today_status): ?>
                    <?php if($today_status['jam_masuk'] && $today_status['jam_keluar']): ?>
                        <i class="fas fa-check-circle fa-2x mb-3 text-success"></i>
                        <h5>Selesai</h5>
                        <p>Status Hari Ini</p>
                    <?php elseif($today_status['jam_masuk']): ?>
                        <i class="fas fa-clock fa-2x mb-3 text-warning"></i>
                        <h5>Masuk</h5>
                        <p>Status Hari Ini</p>
                    <?php endif; ?>
                <?php else: ?>
                    <i class="fas fa-times-circle fa-2x mb-3 text-danger"></i>
                    <h5>Belum Absen</h5>
                    <p>Status Hari Ini</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Riwayat Absensi Terbaru</h5>
                <small class="text-muted">10 Data Terakhir</small>
            </div>
            <div class="card-body">
                <?php
                $recent_query = "SELECT u.nama, a.tanggal, a.jam_masuk, a.jam_keluar, j.nama_jabatan,
                    CASE 
                        WHEN a.jam_masuk > '08:00:00' THEN 'Terlambat'
                        WHEN a.jam_masuk IS NULL THEN 'Tidak Masuk'
                        ELSE 'Tepat Waktu'
                    END as status_masuk
                    FROM absensi a 
                    JOIN users u ON a.user_id = u.id 
                    JOIN jabatan j ON u.jabatan_id = j.id 
                    WHERE a.user_id = ?
                    ORDER BY a.created_at DESC LIMIT 10";
                $recent_stmt = $db->prepare($recent_query);
                $recent_stmt->execute([$_SESSION['user_id']]);
                $recent_absensi = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_absensi as $absen): ?>
                            <tr>
                                <td><?= htmlspecialchars($absen['nama']) ?></td>
                                <td><small><?= htmlspecialchars($absen['nama_jabatan']) ?></small></td>
                                <td><?= date('d/m/Y', strtotime($absen['tanggal'])) ?></td>
                                <td>
                                    <?php if($absen['jam_masuk']): ?>
                                        <span class="<?= $absen['jam_masuk'] > '08:00:00' ? 'text-warning' : 'text-success' ?>">
                                            <?= $absen['jam_masuk'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($absen['jam_keluar']): ?>
                                        <span class="text-success"><?= $absen['jam_keluar'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $absen['status_masuk'] == 'Tepat Waktu' ? 'success' : 
                                        ($absen['status_masuk'] == 'Terlambat' ? 'warning' : 'danger') 
                                    ?>">
                                        <?= $absen['status_masuk'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h5>Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if(!$today_status): ?>
                        <a href="?page=scanner" class="btn btn-primary">
                            <i class="fas fa-camera me-2"></i>Absen Masuk
                        </a>
                    <?php elseif($today_status['jam_masuk'] && !$today_status['jam_keluar']): ?>
                        <a href="?page=scanner" class="btn btn-warning">
                            <i class="fas fa-camera me-2"></i>Absen Keluar
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success" disabled>
                            <i class="fas fa-check me-2"></i>Absensi Selesai
                        </button>
                    <?php endif; ?>
                    
                    <a href="?page=jadwal_user" class="btn btn-outline-primary">
                        <i class="fas fa-qrcode me-2"></i>Jadwal Kerja
                    </a>
                    <a href="?page=absensi" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>Lihat Data Absensi
                    </a>
                    <a href="?page=pengajuan_cuti" class="btn btn-outline-secondary">
                        <i class="fas fa-chart-bar me-2"></i>Pengajuan Cuti
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Status Hari Ini</h5>
            </div>
            <div class="card-body">
                <?php if($today_status): ?>
                    <div class="text-center">
                        <?php if($today_status['jam_masuk']): ?>
                            <div class="mb-2">
                                <i class="fas fa-sign-in-alt text-success me-2"></i>
                                <strong>Masuk:</strong> <?= $today_status['jam_masuk'] ?>
                                <?php if($today_status['jam_masuk'] > '08:00:00'): ?>
                                    <span class="badge bg-warning ms-2">Terlambat</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($today_status['jam_keluar']): ?>
                            <div class="mb-2">
                                <i class="fas fa-sign-out-alt text-primary me-2"></i>
                                <strong>Keluar:</strong> <?= $today_status['jam_keluar'] ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">
                                <i class="fas fa-clock me-2"></i>Belum absen keluar
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                        <p>Belum melakukan absensi hari ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>