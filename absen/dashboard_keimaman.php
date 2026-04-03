<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Proteksi Hak Akses
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$id_user   = $_SESSION['id_user'];
$level     = $_SESSION['level']; 
$kelompok  = $_SESSION['kelompok'] ?? ''; 

// Cek Biodata untuk Modal QR
$cek_bio = mysqli_query($conn, "SELECT jenjang, nama_lengkap FROM biodata_jamaah WHERE id_user = '$id_user'");
$sudah_isi_bio = mysqli_num_rows($cek_bio) > 0;

if ($sudah_isi_bio) {
    $d_bio = mysqli_fetch_assoc($cek_bio);
    $user_jenjang = $d_bio['jenjang'];
    $nama = !empty($d_bio['nama_lengkap']) ? $d_bio['nama_lengkap'] : $_SESSION['username'];
} else {
    $user_jenjang = '';
    $nama = $_SESSION['username']; 
}
$kode_qr_unik = "ABSENGAJI-" . $id_user . "-" . strtoupper(substr(md5($nama), 0, 5));

// AMBIL INFO PENGAJIAN TERAKHIR (Hanya untuk info di layar, bukan untuk absen)
$q_aktif = mysqli_query($conn, "SELECT * FROM kegiatan WHERE (target_kelompok = 'Semua' OR target_kelompok = '$kelompok') AND (status_buka = 1 OR status_izin = 1 OR DATE(tgl_buat) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)) ORDER BY id_kegiatan DESC LIMIT 1");
$kegiatan = mysqli_fetch_assoc($q_aktif);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Kontrol Pengurus | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark">Ruang Kontrol, <?= strtoupper($nama); ?>!</h2>
            <p class="text-muted mb-0">Role: <span class="badge bg-danger px-3 rounded-pill"><?= strtoupper(str_replace('_', ' ', $level)); ?></span> Wilayah: <span class="badge bg-secondary"><?= $kelompok; ?></span></p>
        </div>
        <div class="text-end">
            <?php if($sudah_isi_bio): ?>
                <button class="btn btn-dark fw-bold shadow-sm rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalQR"><i class="fa fa-qrcode me-2"></i>KARTU QR SAYA</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-info border-info shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fa fa-lightbulb fa-2x me-3 text-info"></i>
            <div>
                <h6 class="fw-bold mb-1">Informasi Absensi Pribadi Pengurus</h6>
                <small>Halaman ini dikhususkan untuk memantau data dan mengelola pengajian. Jika Anda ingin melakukan <b>Absen Kehadiran Pribadi</b>, silakan gunakan fitur <b><i class="fa fa-sync-alt mx-1"></i> Ganti Peran</b> di menu samping kiri dan pilih <b>"Jamaah Biasa"</b>.</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-stat p-4 border-0 h-100 <?= ($kegiatan) ? 'bg-white border-start border-success border-5 shadow-sm' : 'bg-light'; ?>">
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-dark"><i class="fa fa-broadcast-tower text-primary me-2"></i>Monitor Pengajian Terkini</h5>
                
                <?php if($kegiatan): ?>
                    <div class="mb-3">
                        <?php if($kegiatan['is_selesai'] == 1): ?>
                            <span class="badge bg-danger mb-2"><i class="fa fa-lock me-1"></i> SESI DITUTUP ADMIN</span>
                        <?php else: ?>
                            <span class="badge bg-success mb-2"><i class="fa fa-door-open me-1"></i> SEDANG AKTIF</span>
                        <?php endif; ?>
                        
                        <span class="badge bg-dark ms-1 mb-2">Target Jenjang: <?= $kegiatan['target_jenjang']; ?></span>
                        <h4 class="fw-bold mb-1 text-dark"><?= $kegiatan['judul_pengajian']; ?></h4>
                        <p class="text-muted mb-3"><i class="fa fa-map-marker-alt text-danger me-2"></i><?= $kegiatan['tempat_pengajian']; ?></p>
                    </div>
                    
                    <a href="detail_absen.php?id_kegiatan=<?= $kegiatan['id_kegiatan']; ?>" class="btn btn-warning w-100 fw-bold shadow-sm text-dark">
                        <i class="fa fa-cogs me-2"></i> BUKA PANEL PENGATURAN PENGAJIAN
                    </a>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa fa-calendar-times fa-3x mb-3 opacity-25"></i>
                        <h5>Belum ada jadwal pengajian aktif di wilayah Anda.</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-stat p-4 bg-white shadow-sm border-top border-warning border-4 h-100">
                <h5 class="fw-bold border-bottom pb-2 mb-3 text-dark"><i class="fa fa-bolt text-warning me-2"></i>Akses Cepat Pengurus</h5>
                
                <div class="d-flex flex-column gap-3 mt-2">
                    
                    <?php if(!in_array($level, ['keimaman', 'keimaman_desa', 'ketua_mudai'])): ?>
                    <a href="buka_pengajian.php" class="text-decoration-none text-dark">
                        <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                            <div class="icon-box bg-primary-subtle text-primary me-3"><i class="fa fa-plus-circle"></i></div>
                            <div><h6 class="fw-bold mb-0">Buat Jadwal Baru</h6><small class="text-muted">Buka pengajian & absen jamaah</small></div>
                        </div>
                    </a>
                    
                    <a href="scan.php" class="text-decoration-none text-dark">
                        <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                            <div class="icon-box bg-dark-subtle text-dark me-3"><i class="fa fa-qrcode"></i></div>
                            <div><h6 class="fw-bold mb-0">Scan QR Code Kamera</h6><small class="text-muted">Pindai kartu absen Caberawit/Manual</small></div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <a href="kelola_izin.php" class="text-decoration-none text-dark">
                        <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                            <div class="icon-box bg-warning-subtle text-warning-emphasis me-3"><i class="fa fa-envelope-open-text"></i></div>
                            <div><h6 class="fw-bold mb-0">Validasi Izin</h6><small class="text-muted">Tinjau permohonan sakit & halangan</small></div>
                        </div>
                    </a>

                    <a href="data_jamaah.php" class="text-decoration-none text-dark">
                        <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                            <div class="icon-box bg-success-subtle text-success me-3"><i class="fa fa-users"></i></div>
                            <div><h6 class="fw-bold mb-0">Data Jamaah</h6><small class="text-muted">Kelola & pantau profil database</small></div>
                        </div>
                    </a>

                </div>
            </div>
        </div>
    </div>
</div>

<?php if($sudah_isi_bio): ?>
<div class="modal fade" id="modalQR" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="bg-dark text-white text-center p-3">
                <h5 class="fw-bold mb-0"><i class="fa fa-id-badge me-2 text-warning"></i>KARTU ABSENSI SAYA</h5>
            </div>
            <div class="modal-body text-center p-4 bg-white">
                <div class="bg-light p-2 rounded mb-4 d-flex justify-content-center mx-auto" style="width:200px;height:200px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($kode_qr_unik); ?>" class="img-fluid border border-3 border-dark rounded">
                </div>
                <div class="border border-primary border-2 border-dashed rounded p-3 bg-light text-start">
                    <small class="text-muted fw-bold">NAMA LENGKAP</small><h6 class="fw-bold text-dark mb-3"><?= strtoupper($nama); ?></h6>
                    <div class="row g-0">
                        <div class="col-6 border-end border-primary pe-2"><small class="text-muted fw-bold">JENJANG</small><h6 class="fw-bold text-primary mb-0"><?= $user_jenjang; ?></h6></div>
                        <div class="col-6 ps-3"><small class="text-muted fw-bold">KELOMPOK</small><h6 class="fw-bold text-success mb-0"><?= strtoupper($kelompok); ?></h6></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .hover-bg-light:hover { background-color: #f8f9fa !important; transition: 0.2s; }
</style>
</body>
</html>