<?php
session_start();
require_once 'config.php';

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil 7 hari terakhir untuk user yang login
$query = "SELECT tanggal, DAYNAME(tanggal) as hari, jam_masuk, jam_keluar, status 
          FROM absensi 
          WHERE user_id = :user_id 
            AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          ORDER BY tanggal DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'history' => $history
]);