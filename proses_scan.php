<?php
// process_scan.php
session_start();
require_once 'config.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Koneksi DB
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['qr_code'])) {
        throw new Exception('QR Code tidak ditemukan dalam request');
    }

    $qr_code = trim($input['qr_code']);
    if (empty($qr_code)) {
        throw new Exception('QR Code tidak boleh kosong');
    }

    // Cek user berdasarkan QR
    $query = "SELECT u.*, j.nama_jabatan 
              FROM users u 
              LEFT JOIN jabatan j ON u.jabatan_id = j.id 
              WHERE u.qr_code = ? AND u.status = 'aktif'";
    $stmt = $db->prepare($query);
    $stmt->execute([$qr_code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'QR Code tidak valid atau user tidak aktif'
        ]);
        exit;
    }

    // Waktu sekarang
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $current_datetime = date('Y-m-d H:i:s');

    // Cek apakah sudah absen hari ini
    $check_query = "SELECT * FROM absensi WHERE user_id = ? AND tanggal = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$user['id'], $today]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // Mulai transaksi
    $db->beginTransaction();

    if (!$existing) {
        // Scan pertama (masuk)
        $status = ($current_time > '08:00:00') ? 'terlambat' : 'hadir';

        $insert_query = "INSERT INTO absensi (user_id, tanggal, jam_masuk, status, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([
            $user['id'],
            $today,
            $current_time,
            $status,
            $current_datetime,
            $current_datetime
        ]);

        $message = "Absensi masuk berhasil dicatat!";
        $type = "masuk";

        logActivity($db, $user['id'], 'check_in', "Absensi masuk pada {$current_time}");
    } else if (is_null($existing['jam_keluar']) || $existing['jam_keluar'] == '') {
        // Scan kedua (keluar)
        $jam_masuk = new DateTime($existing['jam_masuk']);
        $jam_keluar = new DateTime($current_time);
        $diff = $jam_masuk->diff($jam_keluar);
        $jam_kerja = $diff->h + ($diff->i / 60);

        // Status baru
        $new_status = $existing['status'];
        if ($existing['status'] === 'hadir' && $jam_kerja >= 8) {
            $new_status = 'hadir_lengkap';
        }

        $update_query = "UPDATE absensi SET jam_keluar = ?, status = ?, jam_kerja = ?, updated_at = ?
                         WHERE user_id = ? AND tanggal = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([
            $current_time,
            $new_status,
            round($jam_kerja, 2),
            $current_datetime,
            $user['id'],
            $today
        ]);

        $message = "Absensi keluar berhasil dicatat!";
        $type = "keluar";

        logActivity($db, $user['id'], 'check_out', "Absensi keluar pada {$current_time}");
    } else {
        throw new Exception("Anda sudah melakukan absensi masuk dan keluar hari ini.");
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'nama' => $user['nama'],
        'jabatan' => $user['nama_jabatan'] ?? 'Tidak ada jabatan',
        'waktu' => $current_time,
        'tanggal' => date('d/m/Y'),
        'type' => $type,
        'status' => $new_status ?? $status
    ]);
} catch (PDOException $e) {
    if ($db && $db->inTransaction()) $db->rollback();
    error_log("DB ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan database: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($db && $db->inTransaction()) $db->rollback();
    error_log("ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) $db->rollback();
    error_log("FATAL: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem. Silakan hubungi admin.'
    ]);
}

// Fungsi log aktivitas
function logActivity($db, $user_id, $action, $description) {
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, created_at)
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $action,
            $description,
            date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("LOG ERROR: " . $e->getMessage());
    }
}
?>
