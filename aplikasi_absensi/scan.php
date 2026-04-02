<?php
session_start();
include 'koneksi.php';

// Proteksi: Hanya Pengurus yang boleh masuk
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'admin_mudai', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$level = $_SESSION['level'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Absensi | AbsenNgaji</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#1a535c">
<link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/3652/3652191.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #1a535c; color: white; padding: 20px; z-index: 1000; overflow-y: auto;}
        .main-content { margin-left: 250px; padding: 30px; }
        .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 10px; border-radius: 10px; transition: 0.3s; }
        .nav-link.active, .nav-link:hover { background: #4ecdc4; color: #1a535c; font-weight: bold; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        /* Desain Kotak Scanner */
        #reader { width: 100%; max-width: 500px; margin: 0 auto; border-radius: 15px; overflow: hidden; border: 4px solid #1a535c; background: #fff; }
        #reader__scan_region { background: white; }
        #reader button { background-color: #1a535c; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-bottom: 10px; font-weight: bold;}
        #reader button:hover { background-color: #4ecdc4; color: #1a535c; }
        
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold text-dark"><i class="fa fa-camera text-primary me-2"></i>Scanner Kehadiran</h2>
        <a href="dashboard_keimaman.php" class="btn btn-outline-dark fw-bold btn-sm shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card card-custom p-4 bg-white shadow-sm border-top border-primary border-4">
                <h5 class="fw-bold text-dark mb-2">Arahkan Kartu QR Code ke Kamera</h5>
                <p class="text-muted small mb-4">Pastikan kamu sudah membuat jadwal pengajian yang statusnya "Berjalan/Buka".</p>
                
                <div id="reader"></div>
                
               
                        
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>
    // Kunci untuk mencegah 1 QR ter-scan 10x dalam 1 detik
    let isProcessing = false;

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return; // Jika sedang memproses, abaikan scanan yang masuk
        isProcessing = true;

        // Munculkan Pop-up Loading
        Swal.fire({
            title: 'Memproses...',
            text: 'Mencocokkan data jamaah',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Kirim data ke proses_scan.php
        $.ajax({
            url: 'proses_scan.php',
            type: 'POST',
            data: { qr_code: decodedText },
            dataType: 'json', // Memastikan respon yang diterima adalah JSON
            success: function(response) {
                // Tampilkan pesan sesuai respons dari server
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Hadir!',
                        text: response.pesan,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else if (response.status === 'warning') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sudah Tercatat',
                        text: response.pesan,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ditolak',
                        text: response.pesan,
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
                
                // Buka kunci kamera setelah 2 detik agar siap men-scan orang berikutnya
                setTimeout(() => { isProcessing = false; }, 2000);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Sistem Error',
                    text: 'Gagal menghubungi server. Pastikan file proses_scan.php ada.',
                    timer: 3000,
                    showConfirmButton: false
                });
                setTimeout(() => { isProcessing = false; }, 3000);
            }
        });
    }

    function onScanFailure(error) {
        // Biarkan kosong agar console tidak penuh dengan log pencarian kamera
    }

    $(document).ready(function() {
        try {
            // Memulai Kamera
            let html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", 
                { fps: 10, qrbox: {width: 250, height: 250} }, 
                false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        } catch (e) {
            console.error("Gagal memuat kamera:", e);
            alert("Kamera diblokir. Pastikan kamu mengakses lewat http://localhost");
        }
    });
</script>

</body>
</html>