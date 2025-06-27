<!-- pages/dashboard.php -->
<?php
// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
    (SELECT COUNT(*) FROM absensi WHERE tanggal = CURDATE()) as absensi_hari_ini,
    (SELECT COUNT(*) FROM absensi WHERE tanggal = CURDATE() AND jam_masuk > '08:00:00') as terlambat_hari_ini,
    (SELECT COUNT(*) FROM jabatan) as total_jabatan";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2>Dashboard <small class="text-muted">Selamat datang, <?= $_SESSION['nama'] ?></small></h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x mb-3"></i>
                <h3><?= $stats['total_users'] ?></h3>
                <p>Total Karyawan</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-3"></i>
                <h3><?= $stats['absensi_hari_ini'] ?></h3>
                <p>Absensi Hari Ini</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-3"></i>
                <h3><?= $stats['terlambat_hari_ini'] ?></h3>
                <p>Terlambat Hari Ini</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-briefcase fa-2x mb-3"></i>
                <h3><?= $stats['total_jabatan'] ?></h3>
                <p>Total Jabatan</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Absensi Terbaru</h5>
            </div>
            <div class="card-body">
                <?php
                $recent_query = "SELECT u.nama, a.tanggal, a.jam_masuk, a.jam_keluar, j.nama_jabatan 
                                FROM absensi a 
                                JOIN users u ON a.user_id = u.id 
                                JOIN jabatan j ON u.jabatan_id = j.id 
                                ORDER BY a.created_at DESC LIMIT 5";
                $recent_absensi = $db->query($recent_query)->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_absensi as $absen): ?>
                            <tr>
                                <td><?= $absen['nama'] ?></td>
                                <td><?= date('d/m/Y', strtotime($absen['tanggal'])) ?></td>
                                <td><?= $absen['jam_masuk'] ?: '-' ?></td>
                                <td><?= $absen['jam_keluar'] ?: '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?page=scanner" class="btn btn-primary">
                        <i class="fas fa-camera me-2"></i>Scan Absensi
                    </a>
                    <a href="?page=qr_generator" class="btn btn-outline-primary">
                        <i class="fas fa-qrcode me-2"></i>Generate QR Code
                    </a>
                    <a href="?page=absensi" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>Lihat Data Absensi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>