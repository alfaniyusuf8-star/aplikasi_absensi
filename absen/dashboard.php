<?php
session_start();
include 'koneksi.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user   = $_SESSION['id_user'];
$level     = $_SESSION['level']; 
$kelompok  = $_SESSION['kelompok'] ?? ''; 
$bulan_ini = date('m');
$tahun_ini = date('Y');

if ($level != 'karyawan') {
    header("Location: dashboard_keimaman.php");
    exit;
}

// =======================================================================
// CEK STATUS KELENGKAPAN BIODATA (SATPAM PINTAR)
// =======================================================================
$biodata_lengkap = true;
$cek_bio_utama = mysqli_query($conn, "SELECT jenjang, nama_lengkap, foto, tempat_lahir, no_hp, alamat_surabaya FROM biodata_jamaah WHERE id_user = '$id_user'");
$sudah_isi_bio = mysqli_num_rows($cek_bio_utama) > 0;

if ($sudah_isi_bio) {
    $d_bio = mysqli_fetch_assoc($cek_bio_utama);
    $user_jenjang = $d_bio['jenjang'];
    $nama = !empty($d_bio['nama_lengkap']) ? $d_bio['nama_lengkap'] : $_SESSION['username'];
    
    // Syarat Wajib Semua Jenjang: Nomor HP
    if (empty($d_bio['no_hp'])) {
        $biodata_lengkap = false;
    }

    // Syarat Wajib Khusus Muda/i (Foto, Tempat Lahir, Alamat Surabaya)
    if ($user_jenjang == 'Muda/i') {
        if (empty($d_bio['foto']) || empty($d_bio['tempat_lahir']) || empty($d_bio['alamat_surabaya'])) {
            $biodata_lengkap = false;
        }
    }
    
    // Syarat Wajib Khusus Anak/Remaja (Foto, Tempat Lahir)
    if (in_array($user_jenjang, ['Caberawit', 'Pra Remaja', 'Remaja'])) {
        if (empty($d_bio['foto']) || empty($d_bio['tempat_lahir'])) {
            $biodata_lengkap = false;
        }
    }
} else {
    $user_jenjang = '';
    $nama = $_SESSION['username']; 
    $biodata_lengkap = false; // Belum ada data sama sekali = belum lengkap
}
// =======================================================================

$kode_qr_unik = "ABSENGAJI-" . $id_user . "-" . strtoupper(substr(md5($nama), 0, 5));

if ($user_jenjang == 'Caberawit') {
    $filter_jenjang_dashboard = "target_jenjang = 'Caberawit'";
} else {
    $filter_jenjang_dashboard = "(target_jenjang = 'Semua' OR target_jenjang = 'Umum' OR target_jenjang = '$user_jenjang')";
}

$has_created = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'created_at'")) > 0;
if($has_created) {
    $q_tgl = mysqli_query($conn, "SELECT created_at FROM users WHERE id_user = '$id_user'");
    $tgl_daftar = mysqli_fetch_assoc($q_tgl)['created_at'] ?? '2000-01-01 00:00:00';
} else {
    $tgl_daftar = '2000-01-01 00:00:00';
}

$q_hadir = mysqli_query($conn, "SELECT COUNT(CASE WHEN status_absen = 'tepat waktu' THEN 1 END) as tepat, COUNT(CASE WHEN status_absen = 'terlambat' THEN 1 END) as telat FROM presensi WHERE id_user = '$id_user' AND MONTH(tgl_presensi) = '$bulan_ini' AND YEAR(tgl_presensi) = '$tahun_ini'");
$d_hadir = mysqli_fetch_assoc($q_hadir);

$q_izin = mysqli_query($conn, "SELECT COUNT(*) as izin FROM perizinan WHERE id_user = '$id_user' AND status_izin = 'disetujui' AND jenis_izin = 'Tidak Hadir' AND MONTH(tgl_pengajuan) = '$bulan_ini' AND YEAR(tgl_pengajuan) = '$tahun_ini'");
$jml_izin = mysqli_fetch_assoc($q_izin)['izin'];

$q_online = mysqli_query($conn, "SELECT COUNT(*) as online FROM perizinan WHERE id_user = '$id_user' AND status_izin = 'disetujui' AND jenis_izin = 'Online' AND MONTH(tgl_pengajuan) = '$bulan_ini' AND YEAR(tgl_pengajuan) = '$tahun_ini'");
$jml_online = mysqli_fetch_assoc($q_online)['online'];

$jml_tepat_offline = $d_hadir['tepat'] ?? 0;
$jml_telat_offline = $d_hadir['telat'] ?? 0;
$total_masuk = $jml_tepat_offline + $jml_telat_offline + $jml_online;

$query_tidak_hadir = "SELECT COUNT(*) as tidak_hadir FROM kegiatan 
               WHERE (target_kelompok = 'Semua' OR target_kelompok = '$kelompok') 
               AND $filter_jenjang_dashboard 
               AND is_selesai = 1 
               AND DATE(tgl_buat) >= DATE('$tgl_daftar') 
               AND MONTH(tgl_buat) = '$bulan_ini' 
               AND YEAR(tgl_buat) = '$tahun_ini' 
               AND id_kegiatan NOT IN (SELECT id_kegiatan FROM presensi WHERE id_user = '$id_user') 
               AND id_kegiatan NOT IN (SELECT id_kegiatan FROM perizinan WHERE id_user = '$id_user' AND status_izin = 'disetujui')";
$q_tidak_hadir = mysqli_query($conn, $query_tidak_hadir);
$jml_tidak_hadir = mysqli_fetch_assoc($q_tidak_hadir)['tidak_hadir'];

$total_pengajian = $total_masuk + $jml_izin + $jml_tidak_hadir;
$persentase = ($total_pengajian > 0) ? round(($total_masuk / $total_pengajian) * 100, 1) : 0;
if($persentase > 100) $persentase = 100;

$grafik_label = []; $grafik_data = [];
$nama_bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

for ($i = 5; $i >= 0; $i--) {
    $b = date('m', strtotime("-$i month")); $t = date('Y', strtotime("-$i month"));
    $idx_bulan = (int)$b - 1;

    $q_hdr = mysqli_query($conn, "SELECT COUNT(p.id_presensi) AS total_hadir FROM presensi p INNER JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan WHERE p.id_user = '$id_user' AND p.status_absen IN ('tepat waktu', 'terlambat') AND MONTH(k.tgl_buat) = '$b' AND YEAR(k.tgl_buat) = '$t'");
    $h_off = mysqli_fetch_assoc($q_hdr)['total_hadir'];

    $q_h_on = mysqli_query($conn, "SELECT COUNT(*) AS h FROM perizinan z JOIN kegiatan k ON z.id_kegiatan = k.id_kegiatan WHERE z.id_user = '$id_user' AND z.jenis_izin = 'Online' AND z.status_izin = 'disetujui' AND MONTH(k.tgl_buat) = '$b' AND YEAR(k.tgl_buat) = '$t'");
    $h_on = mysqli_fetch_assoc($q_h_on)['h'];

    $q_iz = mysqli_query($conn, "SELECT COUNT(*) AS i FROM perizinan z JOIN kegiatan k ON z.id_kegiatan = k.id_kegiatan WHERE z.id_user = '$id_user' AND z.status_izin = 'disetujui' AND z.jenis_izin = 'Tidak Hadir' AND MONTH(k.tgl_buat) = '$b' AND YEAR(k.tgl_buat) = '$t'");
    $iz = mysqli_fetch_assoc($q_iz)['i'];

    $query_tdk_hadir_chart = "SELECT COUNT(*) as tidak_hadir FROM kegiatan 
                   WHERE (target_kelompok = 'Semua' OR target_kelompok = '$kelompok') 
                   AND $filter_jenjang_dashboard 
                   AND is_selesai = 1 
                   AND DATE(tgl_buat) >= DATE('$tgl_daftar') 
                   AND MONTH(tgl_buat) = '$b' 
                   AND YEAR(tgl_buat) = '$t' 
                   AND id_kegiatan NOT IN (SELECT id_kegiatan FROM presensi WHERE id_user = '$id_user') 
                   AND id_kegiatan NOT IN (SELECT id_kegiatan FROM perizinan WHERE id_user = '$id_user' AND status_izin = 'disetujui')";
    $tdk_hadir_chart = mysqli_fetch_assoc(mysqli_query($conn, $query_tdk_hadir_chart))['tidak_hadir'];

    $tot_masuk_chart = $h_off + $h_on;
    $tot_target_chart = $tot_masuk_chart + $iz + $tdk_hadir_chart;

    $persen_bulan = ($tot_target_chart > 0) ? round(($tot_masuk_chart / $tot_target_chart) * 100) : 0;
    if($persen_bulan > 100) $persen_bulan = 100;
    
    $grafik_label[] = substr($nama_bulan_indo[$idx_bulan], 0, 3); 
    $grafik_data[] = $persen_bulan;
}

$q_aktif = mysqli_query($conn, "SELECT * FROM kegiatan WHERE (target_kelompok = 'Semua' OR target_kelompok = '$kelompok') AND $filter_jenjang_dashboard AND (status_buka = 1 OR status_izin = 1 OR DATE(tgl_buat) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)) ORDER BY id_kegiatan DESC LIMIT 1");
$kegiatan = mysqli_fetch_assoc($q_aktif);

$data_absen = null; $data_izin = null;
if($kegiatan) {
    $q_abs = mysqli_query($conn, "SELECT status_absen FROM presensi WHERE id_user = '$id_user' AND id_kegiatan = '".$kegiatan['id_kegiatan']."'");
    if(mysqli_num_rows($q_abs) > 0) $data_absen = mysqli_fetch_assoc($q_abs);

    $q_iz = mysqli_query($conn, "SELECT * FROM perizinan WHERE id_user = '$id_user' AND id_kegiatan = '".$kegiatan['id_kegiatan']."'");
    if(mysqli_num_rows($q_iz) > 0) $data_izin = mysqli_fetch_assoc($q_iz);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        canvas { position: relative; z-index: 1; }
        .heartbeat { animation: heartbeat 1.5s ease-in-out infinite both; }
        @keyframes heartbeat { 10%, 33% { transform: scale(0.95); } 17%, 45% { transform: scale(1); } }
        
        /* Animasi Tombol Pengingat */
        .pulse-button { animation: pulse 2s infinite; }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(220, 53, 69, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark">Assalamu'alaikum, <?= strtoupper($nama); ?>!</h2>
            <p class="text-muted mb-0">Kelompok: <span class="badge bg-primary px-3 rounded-pill"><?= $kelompok; ?></span> <?= ($sudah_isi_bio) ? '<span class="badge bg-info text-dark px-3 rounded-pill">'.$user_jenjang.'</span>' : ''; ?></p>
        </div>
        <div class="text-end d-flex gap-2">
            <?php if($sudah_isi_bio && $biodata_lengkap): ?>
                <button class="btn btn-dark fw-bold shadow-sm rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalQR"><i class="fa fa-qrcode me-2"></i>KARTU QR SAYA</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$biodata_lengkap): ?>
        <div class="card border-danger shadow-lg mb-4" style="border-radius: 15px; background: linear-gradient(to right, #ffffff, #fff5f5);">
            <div class="card-body text-center py-5 bg-transparent" style="border-radius: 15px;">
                <i class="fa fa-user-lock fa-5x text-danger mb-4 opacity-75"></i>
                <h2 class="fw-bold text-dark">Akses Absensi Terkunci!</h2>
                <p class="text-muted fs-5 mb-4 px-md-5">Ahlan wa sahlan! Untuk dapat melihat Barcode Absensi, mengajukan Izin, dan mengakses fitur-fitur lainnya, Anda <b>WAJIB</b> melengkapi Biodata dan mengunggah Foto Profil yang jelas sesuai jenjang Anda.</p>
                <a href="isi_biodata.php" class="btn btn-danger btn-lg fw-bold rounded-pill px-5 shadow-sm pulse-button">
                    <i class="fa fa-edit me-2"></i> Lengkapi Biodata Sekarang
                </a>
            </div>
        </div>

    <?php else: ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-stat p-4 border-0 <?php echo ($kegiatan) ? 'bg-white border-start border-success border-5' : 'bg-light'; ?>">
                    <?php if($kegiatan): ?>
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <span class="badge bg-primary mb-2">PENGUMUMAN PENGAJIAN</span>
                                <span class="badge bg-dark ms-1 mb-2">Khusus: <?= $kegiatan['target_jenjang']; ?></span>
                                
                                <h3 class="fw-bold mb-1 text-dark"><?= $kegiatan['judul_pengajian']; ?></h3>
                                <p class="text-muted mb-2"><i class="fa fa-map-marker-alt text-danger me-2"></i>Lokasi: <?= $kegiatan['tempat_pengajian']; ?></p>
                                
                                <?php if(!empty($kegiatan['materi'])): ?>
                                    <div class="bg-light border-start border-info border-4 p-3 mb-3 mt-2 rounded-end shadow-sm">
                                        <small class="text-info fw-bold d-block mb-1"><i class="fa fa-book-open me-1"></i> Materi / Bab Kajian:</small>
                                        <span class="text-dark fw-medium" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($kegiatan['materi'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-5 text-end">
                                <div class="d-flex flex-column text-end gap-2 ms-auto" style="max-width: 380px;">
                                    
                                    <?php if($data_absen): ?>
                                        <span class="badge <?= (strtolower($data_absen['status_absen']) == 'terlambat') ? 'bg-warning text-dark' : 'bg-success'; ?> p-3 fs-6 rounded-pill shadow-sm">
                                            <i class="fa fa-fingerprint me-2"></i> SAYA HADIR (<?= strtoupper($data_absen['status_absen']); ?>)
                                        </span>
                                    
                                    <?php elseif($data_izin): ?>
                                        <?php if($data_izin['status_izin'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark p-3 fs-6"><i class="fa fa-spinner fa-spin me-2"></i> Izin <?= $data_izin['jenis_izin']; ?> Sedang Diproses...</span>
                                        
                                        <?php elseif($data_izin['status_izin'] == 'ditolak'): ?>
                                            <span class="badge bg-danger p-3 fs-6"><i class="fa fa-times me-2"></i> Izin Ditolak</span>
                                        
                                        <?php elseif($data_izin['status_izin'] == 'disetujui'): ?>
                                            
                                            <?php if($data_izin['jenis_izin'] == 'Tidak Hadir'): ?>
                                                <div class="alert alert-secondary p-3 mb-0 text-start border-secondary shadow-sm rounded">
                                                    <h6 class="fw-bold text-secondary mb-2"><i class="fa fa-check-circle me-1"></i> Izin Tidak Hadir Di-ACC!</h6>
                                                    
                                                    <?php if(!empty($data_izin['catatan_admin'])): ?>
                                                        <div class="mt-3 p-3 bg-white rounded border-start border-success border-4 shadow-sm position-relative">
                                                            <small class="text-success fw-bold d-block mb-1"><i class="fa fa-envelope-open-text me-1"></i> Pesan dari Pengurus:</small>
                                                            <p class="mb-0 text-dark" style="font-size: 0.85rem; font-style: italic; line-height: 1.4;">
                                                                "<?= nl2br(htmlspecialchars($data_izin['catatan_admin'])); ?>"
                                                            </p>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary w-100 p-2"><i class="fa fa-bed me-1"></i> Anda dibebastugaskan hari ini.</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-primary p-3 mb-0 text-start border-primary shadow-sm rounded">
                                                    <h6 class="fw-bold text-primary mb-2"><i class="fa fa-video me-1"></i> Izin Online Di-ACC!</h6>
                                                    <?php if(!empty($kegiatan['link_zoom'])): ?>
                                                        <a href="<?= $kegiatan['link_zoom']; ?>" target="_blank" class="btn btn-primary btn-sm mb-3 w-100 fw-bold"><i class="fa fa-video me-1"></i> Buka Link Zoom</a>
                                                    <?php endif; ?>
                                                    <span class="badge bg-success w-100 p-2"><i class="fa fa-check-circle me-1"></i> Tercatat Hadir Online</span>
                                                    
                                                    <?php if(!empty($data_izin['catatan_admin'])): ?>
                                                        <div class="mt-3 p-3 bg-white rounded border-start border-primary border-4 shadow-sm position-relative">
                                                            <small class="text-primary fw-bold d-block mb-1"><i class="fa fa-envelope-open-text me-1"></i> Pesan dari Pengurus:</small>
                                                            <p class="mb-0 text-dark" style="font-size: 0.85rem; font-style: italic; line-height: 1.4;">
                                                                "<?= nl2br(htmlspecialchars($data_izin['catatan_admin'])); ?>"
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <?php if($sudah_isi_bio): ?>
                                            <div class="d-flex flex-column gap-2">
                                                
                                                <?php if($kegiatan['status_izin'] == 1 && $kegiatan['is_selesai'] == 0): ?>
                                                    <?php if($user_jenjang == 'Caberawit'): ?>
                                                        <button type="button" class="btn btn-outline-warning btn-lg fw-bold px-4 shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#modalIzinCaberawit"><i class="fa fa-envelope me-2"></i>IZIN (OLEH ORTU)</button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-warning btn-lg fw-bold px-4 shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#modalIzin"><i class="fa fa-video me-2"></i>IKUT ZOOM / IZIN</button>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if($kegiatan['status_buka'] == 1): ?>
                                                    <?php if($user_jenjang == 'Caberawit'): ?>
                                                        <div class="alert alert-info text-center shadow-sm w-100 m-0 py-2 border-info">
                                                            <i class="fa fa-qrcode fa-2x mb-1 d-block text-primary"></i>
                                                            <h6 class="fw-bold text-dark mb-1">Waktunya Ngaji!</h6>
                                                            <small class="text-muted d-block mb-2" style="font-size: 0.75rem;">Absen khusus Caberawit menggunakan metode Scan QR Code oleh Pengurus.</small>
                                                            <button type="button" class="btn btn-primary fw-bold shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#modalQR"><i class="fa fa-qrcode me-1"></i> TAMPILKAN KARTU QR</button>
                                                        </div>
                                                    <?php else: ?>
                                                        <form action="proses_absen.php" method="POST" id="formAbsen" class="m-0 text-center w-100">
                                                            
                                                            <div class="alert alert-danger mb-2 py-2 px-1 border-danger rounded shadow-sm">
                                                                <small class="fw-bold text-danger d-block" style="font-size: 0.7rem;"><i class="fa fa-exclamation-triangle me-1"></i> STATUS SAAT INI:</small>
                                                                <h5 class="fw-bold text-danger mb-0 heartbeat">TIDAK HADIR (BELUM ABSEN)</h5>
                                                            </div>

                                                            <?php 
                                                                $waktu_db = $kegiatan['waktu_buka_absen'] ?? '';
                                                                if(empty($waktu_db) || $waktu_db == '0000-00-00 00:00:00') { $waktu_db = $kegiatan['tgl_buat']; }
                                                                $waktu_buka = strtotime($waktu_db);
                                                                $batas_waktu = $waktu_buka + (10 * 60);
                                                                $selisih = $batas_waktu - time();
                                                            ?>
                                                            <div class="mb-2">
                                                                <?php if($selisih > 0): ?>
                                                                    <div class="alert alert-warning py-2 px-2 shadow-sm rounded border-warning text-center mb-2">
                                                                        <small class="fw-bold d-block text-dark"><i class="fa fa-stopwatch me-1"></i> Sisa Waktu (Tepat Waktu):</small>
                                                                        <h3 id="timer-countdown" class="fw-bold mb-0 text-dark">00:00</h3>
                                                                    </div>
                                                                    <button type="button" onclick="ambilLokasi()" class="btn btn-success btn-lg fw-bold px-4 shadow-sm w-100"><i class="fa fa-fingerprint me-2"></i>HADIR SEKARANG (GPS)</button>
                                                                <?php else: ?>
                                                                    <div class="alert alert-danger py-2 px-2 shadow-sm rounded border-danger text-center mb-2">
                                                                        <small class="fw-bold d-block text-danger"><i class="fa fa-exclamation-circle me-1"></i> Waktu Habis</small>
                                                                        <h5 class="fw-bold mb-0 text-danger">Status: TERLAMBAT</h5>
                                                                    </div>
                                                                    <button type="button" onclick="ambilLokasi()" class="btn btn-warning text-dark btn-lg fw-bold px-4 shadow-sm w-100"><i class="fa fa-walking me-2"></i>ABSEN TERLAMBAT</button>
                                                                <?php endif; ?>
                                                            </div>
                                                            <input type="hidden" name="id_kegiatan" value="<?= $kegiatan['id_kegiatan']; ?>">
                                                            <input type="hidden" name="lat" id="lat"><input type="hidden" name="long" id="long">
                                                            <input type="hidden" name="absen_masuk" value="1">
                                                        </form>
                                                    <?php endif; ?>

                                                <?php elseif($kegiatan['is_selesai'] == 1): ?>
                                                    <button class="btn btn-secondary btn-lg fw-bold px-4 w-100" disabled><i class="fa fa-lock me-2"></i>DIKUNCI</button>
                                                <?php else: ?>
                                                    <button class="btn btn-light text-secondary border-secondary btn-lg fw-bold px-4 w-100" disabled><i class="fa fa-clock me-2"></i>ABSEN BELUM DIBUKA</button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fa fa-calendar-times fa-3x mb-3 opacity-25"></i>
                            <h5>Belum ada jadwal pengajian untuk jenjang Anda.</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <h5 class="fw-bold border-start border-4 border-primary ps-2 mb-3">Statistik Bulan Ini (<?= date('F Y'); ?>)</h5>
            
            <div class="card card-stat p-4 bg-white border-start border-primary border-5 mb-3 d-flex flex-row align-items-center justify-content-between shadow-sm">
                <div>
                    <h6 class="text-muted fw-bold mb-1">PERSENTASE SAYA</h6>
                    <h2 class="fw-bold text-primary mb-0"><?= $persentase; ?>%</h2>
                    <span class="badge bg-light text-dark border mt-1"><i class="fa fa-bullseye me-1"></i> Target: <?= $total_pengajian; ?> Pengajian</span>
                </div>
                <div class="w-50 d-none d-md-block">
                    <div class="progress" style="height: 15px; border-radius: 10px;">
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: <?= $persentase; ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <div class="card card-stat p-3 bg-white text-center border-bottom border-success border-3 shadow-sm h-100 d-flex flex-column justify-content-center">
                        <div class="text-success small fw-bold mb-1"><i class="fa fa-building me-1"></i> HADIR OFFLINE</div>
                        <h3 class="fw-bold mb-0 text-success"><?= $jml_tepat_offline + $jml_telat_offline; ?></h3>
                        <div class="mt-2 border-top pt-2" style="font-size: 0.75rem;">
                            <span class="text-muted d-block mb-1"><i class="fa fa-check-circle text-success me-1"></i>Tepat: <b class="text-dark"><?= $jml_tepat_offline; ?></b></span>
                            <span class="text-muted d-block"><i class="fa fa-clock text-warning me-1"></i>Telat: <b class="text-dark"><?= $jml_telat_offline; ?></b></span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-stat p-3 bg-white text-center border-bottom border-primary border-3 shadow-sm h-100 d-flex flex-column justify-content-center">
                        <div class="text-primary small fw-bold mb-1"><i class="fa fa-video me-1"></i> HADIR ONLINE</div>
                        <h3 class="fw-bold mb-0 text-primary"><?= $jml_online; ?></h3>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-stat p-3 bg-white text-center border-bottom border-warning border-3 shadow-sm h-100 d-flex flex-column justify-content-center">
                        <div class="text-warning small fw-bold mb-1"><i class="fa fa-envelope me-1"></i> IZIN / SAKIT</div>
                        <h3 class="fw-bold mb-0 text-warning"><?= $jml_izin; ?></h3>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-stat p-3 bg-white text-center border-bottom border-danger border-3 shadow-sm h-100 d-flex flex-column justify-content-center">
                        <div class="text-danger small fw-bold mb-1"><i class="fa fa-times-circle me-1"></i> TIDAK HADIR</div>
                        <h3 class="fw-bold mb-0 text-danger"><?= $jml_tidak_hadir; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <h5 class="fw-bold border-start border-4 border-info ps-2 mb-3">Tren Kehadiran (6 Bulan Terakhir)</h5>
            <div class="card card-stat p-4 bg-white h-100 shadow-sm">
                <div style="position:relative;height:260px;width:100%;">
                    <canvas id="kehadiranChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<?php if($sudah_isi_bio && $biodata_lengkap): ?>
<div class="modal fade" id="modalQR" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="bg-dark text-white text-center p-3">
                <h5 class="fw-bold mb-0"><i class="fa fa-id-badge me-2 text-warning"></i>KARTU ABSENSI</h5>
                <small class="opacity-75">Sistem Terpadu AbsenNgaji</small>
            </div>
            <div class="modal-body text-center p-4 bg-white">
                <div class="bg-light p-2 rounded-3 mb-4 border shadow-sm d-flex justify-content-center mx-auto" style="width:200px;height:200px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($kode_qr_unik); ?>" class="img-fluid border border-3 border-dark rounded">
                </div>
                <div class="border border-primary border-2 border-dashed rounded p-3 bg-light text-start shadow-sm">
                    <small class="text-muted fw-bold d-block mb-1" style="font-size:0.7rem;">NAMA LENGKAP</small>
                    <h6 class="fw-bold text-dark mb-3"><?= strtoupper($nama); ?></h6>
                    <div class="row g-0">
                        <div class="col-6 border-end border-primary pe-2">
                            <small class="text-muted fw-bold d-block mb-1" style="font-size:0.7rem;">JENJANG</small>
                            <h6 class="fw-bold text-primary mb-0" style="font-size:0.9rem;"><?= $user_jenjang; ?></h6>
                        </div>
                        <div class="col-6 ps-3">
                            <small class="text-muted fw-bold d-block mb-1" style="font-size:0.7rem;">KELOMPOK</small>
                            <h6 class="fw-bold text-success mb-0" style="font-size:0.9rem;"><?= strtoupper($kelompok); ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($kegiatan && $user_jenjang != 'Caberawit'): ?>
<div class="modal fade" id="modalIzin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="proses_izin.php" method="POST">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fw-bold">Pengajuan Izin / Online</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_kegiatan" value="<?= $kegiatan['id_kegiatan']; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Jenis Izin:</label>
                        <select name="jenis_izin" class="form-select border-warning border-2 fw-bold text-dark" required>
                            <option value="Online">📹 Izin Ikut Online (Zoom)</option>
                            <option value="Tidak Hadir">❌ Izin Tidak Hadir (Sakit/Halangan)</option>
                        </select>
                    </div>
                    <label class="form-label fw-bold">Alasan Izin:</label>
                    <textarea name="alasan" class="form-control bg-light" rows="3" placeholder="Sebutkan alasan detail..." required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="kirim_izin" class="btn btn-warning fw-bold px-4">KIRIM PERMOHONAN</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if($kegiatan && $user_jenjang == 'Caberawit'): ?>
<div class="modal fade" id="modalIzinCaberawit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="proses_izin.php" method="POST">
                <div class="modal-header bg-info text-dark border-0">
                    <h5 class="modal-title fw-bold"><i class="fa fa-envelope-open-text me-2"></i>Form Izin Caberawit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border border-info small mb-3">
                        Formulir ini diisi oleh <b>Orang Tua / Wali</b> jika anak berhalangan hadir pada pengajian Caberawit.
                    </div>
                    <input type="hidden" name="id_kegiatan" value="<?= $kegiatan['id_kegiatan']; ?>">
                    <input type="hidden" name="jenis_izin" value="Tidak Hadir">
                    
                    <label class="form-label fw-bold">Keterangan / Alasan Anak Tidak Hadir:</label>
                    <textarea name="alasan" class="form-control bg-light border-info" rows="3" placeholder="Contoh: Sedang sakit demam, atau diajak pergi keluarga..." required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="kirim_izin" class="btn btn-info fw-bold px-4 text-dark">KIRIM KE PENGURUS</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?> <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function ambilLokasi() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('lat').value = pos.coords.latitude;
            document.getElementById('long').value = pos.coords.longitude;
            document.getElementById('formAbsen').submit();
        }, function() { alert("Gagal mengambil lokasi. Mohon aktifkan GPS (Location) di HP Anda."); });
    } else { alert("Browser Anda tidak mendukung fitur lokasi."); }
}

<?php if(isset($selisih) && $selisih > 0 && $user_jenjang != 'Caberawit'): ?>
    let waktuSisa = <?= $selisih; ?>;
    const display = document.getElementById('timer-countdown');
    if(display) {
        let timerInterval = setInterval(function () {
            let minutes = parseInt(waktuSisa / 60, 10);
            let seconds = parseInt(waktuSisa % 60, 10);
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
            display.textContent = minutes + ":" + seconds;
            if (--waktuSisa < 0) { clearInterval(timerInterval); location.reload(); }
        }, 1000);
    }
<?php endif; ?>

const ctx = document.getElementById('kehadiranChart').getContext('2d');
let gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(78, 205, 196, 0.5)');
gradient.addColorStop(1, 'rgba(78, 205, 196, 0.0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($grafik_label); ?>,
        datasets: [{
            label: 'Persentase Kehadiran (%)',
            data: <?= json_encode($grafik_data); ?>,
            borderColor: '#1a535c',
            backgroundColor: gradient,
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#1a535c',
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { 
                beginAtZero: true, 
                max: 100,
                ticks: { callback: function(value) { return value + "%" } }
            } 
        }
    }
});
</script>
</body>
</html>