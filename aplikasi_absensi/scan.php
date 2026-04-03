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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner Absensi | AbsenNgaji</title>
    
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
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        /* Desain Kotak Scanner Anti-Lag */
        #reader { 
            width: 100%; 
            max-width: 400px; /* Diperkecil sedikit agar tidak berat render di HP */
            margin: 0 auto; 
            border-radius: 15px; 
            overflow: hidden; 
            border: 4px solid #1a535c; 
            background: #000; /* Warna dasar hitam agar tidak silau kalau telat loading */
        }
        /* Memaksa video menyesuaikan kotak agar iPhone tidak bingung */
        #reader video {
            object-fit: cover !important;
        }
        
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
                
                <div id="status-kamera" class="mt-3 text-muted small fw-bold">
                    <i class="fa fa-spinner fa-spin me-1"></i> Sedang menyiapkan kamera...
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    let isProcessing = false;
    const html5QrCode = new Html5Qrcode("reader");

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return; 
        isProcessing = true;
        html5QrCode.pause();

        Swal.fire({
            title: 'Memproses...',
            text: 'Mencocokkan data jamaah',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'proses_scan.php',
            type: 'POST',
            data: { qr_code: decodedText },
            dataType: 'json',
            success: function(response) {
                let iconType = 'info';
                if (response.status === 'success') iconType = 'success';
                else if (response.status === 'warning') iconType = 'warning';
                else iconType = 'error';

                Swal.fire({
                    icon: iconType,
                    title: response.status.toUpperCase(),
                    text: response.pesan,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                setTimeout(() => { 
                    isProcessing = false; 
                    html5QrCode.resume(); 
                }, 2000);
            },
            error: function() {
                Swal.fire({
                    icon: 'error', title: 'Sistem Error', text: 'Gagal menghubungi server.',
                    timer: 3000, showConfirmButton: false
                });
                setTimeout(() => { isProcessing = false; html5QrCode.resume(); }, 3000);
            }
        });
    }

    $(document).ready(function() {
        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0 
        };

        // KODINGAN BARU: Deteksi semua lensa dulu sebelum menyalakan
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                // Coba nyalakan kamera belakang (environment)
                html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
                .catch((err) => {
                    // Jika kamera belakang gagal/nge-bug di iPhone, paksa pakai kamera pertama di daftar
                    console.warn("Kamera belakang gagal, mencoba kamera alternatif...");
                    html5QrCode.start(devices[0].id, config, onScanSuccess);
                });
                document.getElementById('status-kamera').innerHTML = '<i class="fa fa-check-circle text-success me-1"></i> Kamera Aktif';
            } else {
                document.getElementById('status-kamera').innerHTML = '<span class="text-danger"><i class="fa fa-times-circle me-1"></i> Tidak ada kamera terdeteksi di HP ini.</span>';
            }
        }).catch(err => {
            console.error("Gagal meminta izin kamera:", err);
            document.getElementById('status-kamera').innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle me-1"></i> Izin kamera ditolak.</span>';
        });
    });
</script>

</body>
</html>