<?php
session_start();

// Jika ada konfirmasi logout
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] == 'yes') {
    // Hapus semua session
    session_unset();
    session_destroy();
    
    // Redirect ke halaman login dengan pesan
    header('Location: login.php?logout=success');
    exit();
}

// Jika tidak ada session atau user tidak login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #008362 0%, #006b52 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .logout-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .logout-icon {
            width: 60px;
            height: 60px;
            background: #008362;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .btn-logout {
            background: #008362;
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 500;
        }
        .btn-logout:hover {
            background: #006b52;
        }
        .btn-cancel {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 500;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card logout-card">
                    <div class="card-body p-4 text-center">
                        <!-- Logout Icon -->
                        <div class="logout-icon">
                            <i class="fas fa-sign-out-alt text-white fa-lg"></i>
                        </div>
                        
                        <!-- User Info -->
                        <div class="mb-3">
                            <h5 class="mb-1">
                                <?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'User' ?>
                            </h5>
                            <?php if (isset($_SESSION['email']) && !empty($_SESSION['email'])): ?>
                                <p class="text-muted mb-0">
                                    <?= htmlspecialchars($_SESSION['email']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Confirmation Message -->
                        <h4 class="mb-3">Konfirmasi Logout</h4>
                        <p class="text-muted mb-4">
                            Apakah Anda yakin ingin keluar dari sistem?
                        </p>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="confirm_logout" value="yes">
                                <button type="submit" class="btn btn-logout text-white">
                                    <i class="fas fa-sign-out-alt me-1"></i>Ya, Logout
                                </button>
                            </form>
                            
                            <button type="button" onclick="goBack()" class="btn btn-cancel text-white">
                                <i class="fas fa-arrow-left me-1"></i>Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>