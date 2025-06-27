<!-- pages/scanner.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code Absensi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Html5-QRCode Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .scanner-container {
            position: relative;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .scan-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 250px;
            border: 2px dashed #007bff;
            border-radius: 10px;
            pointer-events: none;
            z-index: 10;
        }
        
        .history-card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
            border-radius: 10px;
        }
        
        .status-badge {
            font-size: 0.8em;
            padding: 0.4em 0.8em;
        }
        
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12 mb-4">
                <h2 class="text-center">
                    <i class="fas fa-qrcode me-2"></i>
                    Scan QR Code Absensi
                </h2>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card scanner-container">
                    <div class="card-body text-center">
                        <div id="reader"></div>
                        <div class="mt-3">
                            <!-- <button id="start-scan" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-camera me-2"></i>Mulai Scan
                            </button> -->
                            <button id="stop-scan" class="btn btn-secondary btn-lg" style="display:none;">
                                <i class="fas fa-stop me-2"></i>Stop Scan
                            </button>
                        </div>
                        <div id="scan-result" class="mt-3"></div>
                        
                        <!-- Instructions -->
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Arahkan kamera ke QR Code untuk melakukan absensi
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Absensi 1 Minggu -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-10">
                <div class="card history-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Riwayat Absensi (7 Hari Terakhir)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="attendance-history">
                            <div class="loading-spinner">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Memuat...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
    const html5QrCode = new Html5Qrcode("reader");

    function onScanSuccess(decodedText, decodedResult) {
        alert("QR Code terbaca: " + decodedText);

        fetch('proses_scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ qr_code: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(
                    `✅ Absensi ${data.type === 'masuk' ? 'Masuk' : 'Keluar'} Berhasil!\n` +
                    `Nama: ${data.nama}\n` +
                    `Jabatan: ${data.jabatan}\n` +
                    `Tanggal: ${data.tanggal}\n` +
                    `Waktu: ${data.waktu}\n` +
                    `Status: ${data.status}`
                );
            } else {
                alert("❌ Gagal: " + data.message);
            }
            html5QrCode.stop(); // Stop scanner setelah scan
        })
        .catch(err => {
            alert("❌ Gagal mengirim data ke server.");
            console.error(err);
            html5QrCode.stop();
        });
    }

    function startScanner() {
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                html5QrCode.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: 250
                    },
                    onScanSuccess
                ).then(() => {
                    document.getElementById('stop-scan').style.display = 'inline-block';
                });
            } else {
                alert("Tidak ada kamera ditemukan");
            }
        }).catch(err => {
            alert("Gagal mengakses kamera: " + err);
        });
    }

    function stopScanner() {
        html5QrCode.stop().then(() => {
            document.getElementById('stop-scan').style.display = 'none';
        }).catch(err => {
            console.error("Gagal menghentikan scanner:", err);
        });
    }

    // Otomatis mulai saat halaman dimuat
    document.addEventListener("DOMContentLoaded", function () {
        startScanner();
    });

    document.getElementById('stop-scan').addEventListener('click', stopScanner);
</script>


    
</body>
</html>