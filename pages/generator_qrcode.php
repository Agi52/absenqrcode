<!-- pages/admin_qr_generator.php -->
<?php
// Pastikan hanya admin yang bisa akses
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission untuk generate QR codes
$selected_users = [];
$show_qr = false;

if ($_POST && isset($_POST['generate_qr'])) {
    if (isset($_POST['user_ids']) && !empty($_POST['user_ids'])) {
        $user_ids = $_POST['user_ids'];
        $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
        
        $query = "SELECT id, nama, qr_code, email FROM users WHERE id IN ($placeholders) ORDER BY nama";
        $stmt = $db->prepare($query);
        $stmt->execute($user_ids);
        $selected_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $show_qr = true;
    }
}

// Get all users untuk dropdown/checkbox
$all_users_query = "SELECT id, nama, email, role FROM users ORDER BY nama";
$all_users_stmt = $db->prepare($all_users_query);
$all_users_stmt->execute();
$all_users = $all_users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2>Generate QR Code Users</h2>
        <p class="text-muted">Pilih user yang ingin di-generate QR code-nya</p>
    </div>
</div>

<!-- Form Seleksi User -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Pilih User</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="qrForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAll()">
                                <i class="fas fa-check-square me-1"></i>Pilih Semua
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="deselectAll()">
                                <i class="fas fa-square me-1"></i>Hapus Semua
                            </button>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-info" id="selectedCount">0 user dipilih</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($all_users as $user): ?>
                        <div class="col-md-4 col-sm-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" 
                                       name="user_ids[]" value="<?= $user['id'] ?>" 
                                       id="user<?= $user['id'] ?>"
                                       <?= (isset($_POST['user_ids']) && in_array($user['id'], $_POST['user_ids'])) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="user<?= $user['id'] ?>">
                                    <strong><?= htmlspecialchars($user['nama']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                    <br><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?> badge-sm">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" name="generate_qr" class="btn btn-success me-2">
                                <i class="fas fa-qrcode me-2"></i>Generate QR Code
                            </button>
                            <?php if ($show_qr && !empty($selected_users)): ?>
                            <button type="button" class="btn btn-primary me-2" onclick="printQRCodes()">
                                <i class="fas fa-print me-2"></i>Print Semua QR Code
                            </button>
                            <button type="button" class="btn btn-info me-2" onclick="downloadAllQR()">
                                <i class="fas fa-download me-2"></i>Download Semua PNG
                            </button>
                            <button type="button" class="btn btn-warning" onclick="downloadAllQRAsZip()">
                                <i class="fas fa-file-archive me-2"></i>Download ZIP
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Results -->
<?php if ($show_qr && !empty($selected_users)): ?>
<div class="row" id="qrResults">
    <div class="col-12 mb-3">
        <h4>QR Code Results (<?= count($selected_users) ?> users)</h4>
    </div>
    
    <?php foreach ($selected_users as $user): ?>
    <div class="col-md-4 col-sm-6 mb-4 qr-card">
        <div class="card h-100">
            <div class="card-body text-center">
                <?php
                $qr_data = $user['qr_code'];
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
                ?>
                
                <h6 class="card-title"><?= htmlspecialchars($user['nama']) ?></h6>
                <p class="text-muted small"><?= htmlspecialchars($user['email']) ?></p>
                
                <img src="<?= $qr_url ?>" alt="QR Code" class="img-fluid mb-2 qr-image" 
                     style="max-width: 200px;" data-qr-url="<?= $qr_url ?>" 
                     data-user-name="<?= htmlspecialchars($user['nama']) ?>"
                     data-qr-data="<?= $qr_data ?>">
                
                <p class="text-muted small">ID: <?= $qr_data ?></p>
                
                <div class="btn-group-vertical w-100">
                    <button type="button" class="btn btn-outline-primary btn-sm mb-1" 
                            onclick="downloadSingleQR('<?= $qr_url ?>', '<?= htmlspecialchars($user['nama']) ?>', '<?= $qr_data ?>')">
                        <i class="fas fa-download me-1"></i>Download PNG
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" 
                            onclick="printSingleQR('<?= $qr_url ?>', '<?= htmlspecialchars($user['nama']) ?>', '<?= $qr_data ?>')">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Hidden Print Area -->
<div id="printArea" style="display: none;">
    <!-- Content akan di-generate oleh JavaScript -->
</div>

<?php elseif ($show_qr && empty($selected_users)): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Tidak ada user yang dipilih. Pilih minimal satu user untuk generate QR code.
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
// Function untuk select/deselect all
function selectAll() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
    updateSelectedCount();
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    updateSelectedCount();
}

// Update counter
function updateSelectedCount() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const countElement = document.getElementById('selectedCount');
    const count = checkedBoxes.length;
    countElement.textContent = count + ' user dipilih';
    countElement.className = count > 0 ? 'badge bg-success' : 'badge bg-info';
}

// Event listener untuk checkbox changes
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    updateSelectedCount(); // Initial count
});

// Print single QR code
function printSingleQR(qrUrl, userName, qrData) {
    const printContent = `
        <div style="text-align: center; padding: 20px; font-family: Arial, sans-serif;">
            <h2 style="margin-bottom: 10px;">${userName}</h2>
            <img src="${qrUrl}" alt="QR Code" style="max-width: 300px; margin: 20px 0;">
            <p style="margin-top: 10px; font-size: 12px; color: #666;">QR Code ID: ${qrData}</p>
            <p style="margin-top: 5px; font-size: 10px; color: #999;">Generated: ${new Date().toLocaleString()}</p>
        </div>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>QR Code - ${userName}</title>
            <style>
                body { margin: 0; padding: 0; }
                @media print {
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            ${printContent}
            <script>
                window.onafterprint = function() {
                    window.close();
                    window.opener.location.reload();
                };
                window.print();
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
}

// Print all QR codes
function printQRCodes() {
    const qrImages = document.querySelectorAll('.qr-image');
    let printContent = `
        <div style="font-family: Arial, sans-serif;">
            <h1 style="text-align: center; margin-bottom: 30px;">QR Codes - All Users</h1>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; page-break-inside: avoid;">
    `;
    
    qrImages.forEach((img, index) => {
        const userName = img.getAttribute('data-user-name');
        const qrData = img.getAttribute('data-qr-data');
        const qrUrl = img.getAttribute('data-qr-url');
        
        printContent += `
            <div style="text-align: center; padding: 15px; border: 1px solid #ddd; page-break-inside: avoid;">
                <h3 style="margin-bottom: 10px; font-size: 16px;">${userName}</h3>
                <img src="${qrUrl}" alt="QR Code" style="max-width: 200px; margin: 10px 0;">
                <p style="margin-top: 8px; font-size: 10px; color: #666;">ID: ${qrData}</p>
            </div>
        `;
        
        if ((index + 1) % 4 === 0) {
            printContent += `</div><div style="page-break-before: always; display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">`;
        }
    });
    
    printContent += `
            </div>
            <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #999;">
                Generated: ${new Date().toLocaleString()} | Total: ${qrImages.length} QR Codes
            </div>
        </div>
        <script>
            window.onafterprint = function() {
                window.close();
                window.opener.location.reload();
            };
            window.print();
        <\/script>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>All QR Codes</title>
            <style>
                body { margin: 20px; padding: 0; }
                @media print {
                    body { margin: 10px; }
                    .page-break { page-break-before: always; }
                }
            </style>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
}

// Download all QR codes as ZIP (requires additional library)
function downloadAllQR() {
    const qrImages = document.querySelectorAll('.qr-image');
    qrImages.forEach((img, index) => {
        const userName = img.getAttribute('data-user-name');
        const qrData = img.getAttribute('data-qr-data');
        const qrUrl = img.getAttribute('data-qr-url');
        
        setTimeout(() => {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = `qr-${userName}-${qrData}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }, index * 500);
    });
    alert(`Downloading ${qrImages.length} QR codes. Please check your downloads folder.`);
    setTimeout(() => {
        window.location.reload();
    }, qrImages.length * 500 + 1000); // reload setelah semua download selesai
}

// Download all QR codes as ZIP (JSZip)
function downloadAllQRAsZip() {
    const qrImages = document.querySelectorAll('.qr-image');
    if (qrImages.length === 0) {
        alert('Tidak ada QR code untuk diunduh.');
        return;
    }
    const zip = new JSZip();
    let count = 0;
    qrImages.forEach((img, idx) => {
        const userName = img.getAttribute('data-user-name');
        const qrData = img.getAttribute('data-qr-data');
        const qrUrl = img.getAttribute('data-qr-url');
        fetch(qrUrl)
            .then(res => res.blob())
            .then(blob => {
                zip.file(`qr-${userName}-${qrData}.png`, blob);
                count++;
                if (count === qrImages.length) {
                    zip.generateAsync({type:"blob"}).then(function(content) {
                        saveAs(content, "all-qr-codes.zip");
                    });
                }
            });
    });
}

// Single QR code download function
function downloadSingleQR(qrUrl, userName, qrData) {
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = `qr-${userName}-${qrData}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Form validation
document.getElementById('qrForm').addEventListener('submit', function(e) {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Pilih minimal satu user untuk generate QR code.');
        return false;
    }
});
</script>

<style>
.qr-card {
    transition: transform 0.2s;
}

.qr-card:hover {
    transform: translateY(-2px);
}

.form-check-label {
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.form-check-label:hover {
    background-color: #f8f9fa;
}

.form-check-input:checked + .form-check-label {
    background-color: #e3f2fd;
}

.badge-sm {
    font-size: 0.7em;
}

@media print {
    .card, .btn, .form-check, .alert, .card-header {
        display: none !important;
    }
    
    #qrResults {
        display: block !important;
    }
    
    .qr-card {
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
}

/* Print styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    #printArea, #printArea * {
        visibility: visible;
    }
    
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>