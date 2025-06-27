<!-- index.php - Main Dashboard -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Online</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    .sidebar {
        min-height: 100vh;
        background: linear-gradient(135deg, #008362 0%, #00a572 100%);
        position: fixed; /* Membuat sidebar tetap */
        top: 0;
        left: 0;
        width: 250px; /* Tentukan lebar tetap untuk sidebar */
        z-index: 1000; /* Pastikan sidebar di atas konten lain */
        overflow-y: auto; /* Memungkinkan scroll di dalam sidebar jika konten panjang */
    }

    /* Atur main content agar tidak tertutup sidebar */
    .main-content {
        margin-left: 250px; /* Sesuaikan dengan lebar sidebar */
        min-height: 100vh;
    }

    /* Responsive untuk tablet */
    @media (max-width: 992px) {
        .sidebar {
            width: 200px;
        }
        .main-content {
            margin-left: 200px;
        }
    }

    /* Responsive untuk mobile */
    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            left: -250px; /* Sembunyikan sidebar di mobile */
            transition: left 0.3s ease;
            width: 250px;
        }
        
        .sidebar.show {
            left: 0; /* Tampilkan sidebar saat toggle */
        }
        
        .main-content {
            margin-left: 0; /* Tidak ada margin di mobile */
        }
        
        /* Overlay untuk mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
    }

    /* Perbaiki nav-link styling */
    .nav-link {
        color: rgba(255,255,255,0.8) !important;
        margin: 5px 15px; /* Tambah margin horizontal */
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    .nav-link:hover, .nav-link.active {
        background: rgba(255,255,255,0.2);
        color: white !important;
    }

    /* Header sidebar */
    .sidebar h4 {
        padding: 20px 15px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    /* Card styling tetap sama */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .stats-card {
        background: linear-gradient(135deg, #008362 0%, #00a572 100%);
        color: white;
    }

    .btn-primary {
        background: linear-gradient(135deg, #008362 0%, #00a572 100%);
        border: none;
        border-radius: 25px;
    }

    #reader {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</head>
<body>
<?php
session_start();
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Redirect to appropriate dashboard based on role if no specific page is requested
if ($page == 'dashboard' && !isset($_GET['page'])) {
    if ($_SESSION['role'] == 'admin') {
        $page = 'dashboard';
    } else {
        $page = 'dashboard_user';
    }
}
?>

<!-- Mobile Menu Toggle Button -->
<button class="btn btn-primary d-md-none mobile-menu-toggle" id="sidebarToggle" style="position: fixed; top: 15px; left: 15px; z-index: 1001;">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay untuk Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="p-0">
        <h4 class="text-white mb-0"><i class="fas fa-qrcode"></i> Absensi QR</h4>
        <nav class="nav flex-column">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <!-- Menu untuk Admin -->
                <a class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>" href="?page=dashboard">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="nav-link <?= $page == 'user' ? 'active' : '' ?>" href="?page=user">
                    <i class="fas fa-users me-2"></i> Data Karyawan
                </a>
                <a class="nav-link <?= $page == 'jabatan' ? 'active' : '' ?>" href="?page=jabatan">
                    <i class="fas fa-briefcase me-2"></i> Data Jabatan
                </a>
                <a class="nav-link <?= $page == 'generator_qrcode' ? 'active' : '' ?>" href="?page=generator_qrcode">
                    <i class="fas fa-qrcode me-2"></i> Generate QR
                </a>
                <a class="nav-link <?= $page == 'manage_jadwal_admin' ? 'active' : '' ?>" href="?page=manage_jadwal_admin">
                    <i class="fas fa-clock me-2"></i> Jadwal Mengajar
                </a>
                <a class="nav-link <?= $page == 'validasi_cuti' ? 'active' : '' ?>" href="?page=validasi_cuti">
                    <i class="fas fa-calendar-check me-2"></i> Validasi Cuti
                </a>
            <?php else: ?>
                <!-- Menu untuk User -->
                <a class="nav-link <?= $page == 'dashboard_user' ? 'active' : '' ?>" href="?page=dashboard_user">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="nav-link <?= $page == 'jadwal_user' ? 'active' : '' ?>" href="?page=jadwal_user">
                    <i class="fas fa-calendar-alt me-2"></i> Jadwal Mengajar
                </a>
                <a class="nav-link <?= $page == 'pengajuan_cuti' ? 'active' : '' ?>" href="?page=pengajuan_cuti">
                    <i class="fas fa-calendar-plus me-2"></i> Pengajuan Cuti
                </a>
            <?php endif; ?>
            
            <!-- Menu yang sama untuk semua role -->
            <a class="nav-link <?= $page == 'scanner' ? 'active' : '' ?>" href="?page=scanner">
                <i class="fas fa-camera me-2"></i> Scan Absensi
            </a>
            <a class="nav-link <?= $page == 'absensi' ? 'active' : '' ?>" href="?page=absensi">
                <i class="fas fa-calendar-check me-2"></i> Riwayat Absensi
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="p-4">
        <?php
        switch($page) {
            case 'dashboard':
                if ($_SESSION['role'] == 'admin') {
                    include 'pages/dashboard.php';
                } else {
                    header("Location: ?page=dashboard_user");
                    exit();
                }
                break;
            case 'dashboard_user':
                if ($_SESSION['role'] != 'admin') {
                    include 'pages/dashboard_user.php';
                } else {
                    header("Location: ?page=dashboard");
                    exit();
                }
                break;
            case 'user':
                if ($_SESSION['role'] == 'admin') {
                    include 'pages/user.php';
                } else {
                    header("Location: ?page=dashboard_user");
                    exit();
                }
                break;
            case 'jabatan':
                if ($_SESSION['role'] == 'admin') {
                    include 'pages/jabatan.php';
                } else {
                    header("Location: ?page=dashboard_user");
                    exit();
                }
                break;
            case 'manage_jadwal_admin':
                if ($_SESSION['role'] == 'admin') {
                    include 'pages/manage_jadwal_admin.php';
                } else {
                    header("Location: ?page=dashboard_user");
                    exit();
                }
                break;
            case 'jadwal_user':
                if ($_SESSION['role'] != 'admin') {
                    include 'pages/jadwal_user.php';
                } else {
                    header("Location: ?page=dashboard");
                    exit();
                }
                break;
            case 'pengajuan_cuti':
                if ($_SESSION['role'] != 'admin') {
                    include 'pages/pengajuan_cuti.php';
                } else {
                    header("Location: ?page=dashboard");
                    exit();
                }
                break;
            case 'validasi_cuti':
                if ($_SESSION['role'] == 'admin') {
                    include 'pages/cuti.php';
                } else {
                    header("Location: ?page=dashboard_user");
                    exit();
                }
                break;
            case 'generator_qrcode':
                include 'pages/generator_qrcode.php';
                break;
            case 'scanner':
                include 'pages/scanner.php';
                break;
            case 'absensi':
                include 'pages/absensi.php';
                break;
            default:
                if ($_SESSION['role'] == 'admin') {
                    include 'pages/dashboard.php';
                } else {
                    include 'pages/dashboard_user.php';
                }
        }
        ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<!-- JavaScript untuk Mobile Toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar untuk mobile
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });
    
    // Tutup sidebar saat overlay diklik
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });
    
    // Tutup sidebar saat link diklik (mobile)
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    });
});
</script>

</body>