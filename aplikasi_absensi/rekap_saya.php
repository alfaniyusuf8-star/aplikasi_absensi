<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user   = $_SESSION['id_user'];
$nama      = $_SESSION['nama'] ?? $_SESSION['username'];
$level     = $_SESSION['level']; 
$kelompok  = $_SESSION['kelompok'] ?? ''; 
$bulan_ini = date('m');
$tahun_ini = date('Y');

// KODE SATPAM "HANYA KARYAWAN" TELAH DIHAPUS DI SINI.
// SEKARANG SEMUA ROLE BISA MELIHAT REKAP PRIBADINYA!

$cek_bio = mysqli_query($conn, "SELECT jenjang FROM biodata_jamaah WHERE id_user = '$id_user'");
if (mysqli_num_rows($cek_bio) > 0) {
    $user_jenjang = mysqli_fetch_assoc($cek_bio)['jenjang'];
} else {
    $user_jenjang = '';
}

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

$nama_bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$filter_tahun_rekap = isset($_GET['tahun_rekap']) ? $_GET['tahun_rekap'] : $tahun_ini;
$batas_bulan = ($filter_tahun_rekap == $tahun_ini) ? $bulan_ini : 12;

$data_rekap_bulanan = [];
for ($m = $batas_bulan; $m >= 1; $m--) { 
    $b_pad = str_pad($m, 2, "0", STR_PAD_LEFT);
    
    // Hadir Offline Murni
    $q_hdr_b = mysqli_query($conn, "SELECT COUNT(p.id_presensi) AS total_hadir FROM presensi p INNER JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan WHERE p.id_user = '$id_user' AND p.status_absen IN ('tepat waktu', 'terlambat') AND MONTH(k.tgl_buat) = '$b_pad' AND YEAR(k.tgl_buat) = '$filter_tahun_rekap'");
    $h_off_b = mysqli_fetch_assoc($q_hdr_b)['total_hadir'];

    // Hadir Online Murni
    $q_h_on_b = mysqli_query($conn, "SELECT COUNT(*) AS h FROM perizinan z JOIN kegiatan k ON z.id_kegiatan = k.id_kegiatan WHERE z.id_user = '$id_user' AND z.jenis_izin = 'Online' AND z.status_izin = 'disetujui' AND MONTH(k.tgl_buat) = '$b_pad' AND YEAR(k.tgl_buat) = '$filter_tahun_rekap'");
    $h_on_b = mysqli_fetch_assoc($q_h_on_b)['h'];

    // Izin Tidak Hadir
    $q_iz_b = mysqli_query($conn, "SELECT COUNT(*) AS i FROM perizinan z JOIN kegiatan k ON z.id_kegiatan = k.id_kegiatan WHERE z.id_user = '$id_user' AND z.status_izin = 'disetujui' AND z.jenis_izin = 'Tidak Hadir' AND MONTH(k.tgl_buat) = '$b_pad' AND YEAR(k.tgl_buat) = '$filter_tahun_rekap'");
    $iz_b = mysqli_fetch_assoc($q_iz_b)['i'];

    // Tidak Hadir 
    $query_th_b = "SELECT COUNT(*) as th FROM kegiatan 
                   WHERE (target_kelompok = 'Semua' OR target_kelompok = '$kelompok') 
                   AND $filter_jenjang_dashboard 
                   AND is_selesai = 1 
                   AND DATE(tgl_buat) >= DATE('$tgl_daftar') 
                   AND MONTH(tgl_buat) = '$b_pad' 
                   AND YEAR(tgl_buat) = '$filter_tahun_rekap' 
                   AND id_kegiatan NOT IN (SELECT id_kegiatan FROM presensi WHERE id_user = '$id_user') 
                   AND id_kegiatan NOT IN (SELECT id_kegiatan FROM perizinan WHERE id_user = '$id_user' AND status_izin = 'disetujui')";
    $th_b = mysqli_fetch_assoc(mysqli_query($conn, $query_th_b))['th'];

    $tot_masuk_b = $h_off_b + $h_on_b;
    $tot_target_b = $tot_masuk_b + $iz_b + $th_b;

    $persen_b = ($tot_target_b > 0) ? round(($tot_masuk_b / $tot_target_b) * 100, 1) : 0;
    if($persen_b > 100) $persen_b = 100;

    $data_rekap_bulanan[] = [
        'nama_bulan' => $nama_bulan_indo[$m - 1],
        'target' => $tot_target_b,
        'masuk_offline' => $h_off_b,
        'masuk_online' => $h_on_b,
        'masuk_total' => $tot_masuk_b,
        'izin' => $iz_b,
        'tidak_hadir' => $th_b,
        'persen' => $persen_b
    ];
}

// LOGIKA TOMBOL KEMBALI (PINTAR)
$link_kembali = ($level == 'karyawan') ? 'dashboard.php' : 'dashboard_keimaman.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kehadiran | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-rekap th { background-color: #f8f9fa; font-size: 0.85rem; color: #6c757d; }
        .table-rekap td { vertical-align: middle; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa fa-chart-bar text-primary me-2"></i>Rekapitulasi Saya</h2>
            <small class="text-muted fw-bold">Laporan Kehadiran Mengaji Bulanan Pribadi</small>
        </div>
        <a href="<?= $link_kembali; ?>" class="btn btn-outline-dark fw-bold shadow-sm rounded-pill px-4"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
                <h5 class="fw-bold border-start border-4 border-success ps-2 mb-0">Riwayat Berdasarkan Bulan</h5>
                
                <form method="GET" class="d-flex gap-2">
                    <select name="tahun_rekap" class="form-select border-secondary fw-bold shadow-sm" onchange="this.form.submit()" style="cursor: pointer;">
                        <?php for($y = date('Y'); $y >= 2024; $y--): ?>
                            <option value="<?= $y; ?>" <?= ($filter_tahun_rekap == $y) ? 'selected' : ''; ?>>Tahun <?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <div class="card card-stat p-0 bg-white border-0 overflow-hidden shadow-sm border-top border-success border-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center table-rekap">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-start ps-4">BULAN</th>
                                <th>TARGET NGAJI</th>
                                <th class="text-success"><i class="fa fa-building me-1"></i>OFFLINE</th>
                                <th class="text-primary"><i class="fa fa-video me-1"></i>ONLINE</th>
                                <th class="text-warning"><i class="fa fa-envelope me-1"></i>IZIN</th>
                                <th class="text-danger"><i class="fa fa-times-circle me-1"></i>TDK HADIR</th>
                                <th width="20%">PERSENTASE (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(count($data_rekap_bulanan) > 0):
                                foreach($data_rekap_bulanan as $rkp):
                                    $bg_color = "bg-primary";
                                    if($rkp['persen'] < 50) $bg_color = "bg-danger";
                                    elseif($rkp['persen'] < 80) $bg_color = "bg-warning";
                                    elseif($rkp['persen'] >= 100) $bg_color = "bg-success";
                            ?>
                            <tr>
                                <td class="text-start ps-4 fw-bold text-dark"><?= strtoupper($rkp['nama_bulan']); ?></td>
                                <td class="fw-bold"><?= $rkp['target']; ?></td>
                                
                                <td class="fw-bold text-success"><?= $rkp['masuk_offline']; ?></td>
                                <td class="fw-bold text-primary"><?= $rkp['masuk_online']; ?></td>
                                
                                <td class="fw-bold text-warning"><?= $rkp['izin']; ?></td>
                                <td class="fw-bold text-danger"><?= $rkp['tidak_hadir']; ?></td>
                                
                                <td>
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <span class="fw-bold w-25 text-end"><?= $rkp['persen']; ?>%</span>
                                        <div class="progress w-75" style="height: 10px; border-radius: 10px; background-color: #e9ecef;">
                                            <div class="progress-bar <?= $bg_color; ?>" style="width: <?= $rkp['persen']; ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                            <tr>
                                <td colspan="7" class="py-5 text-center text-muted">
                                    <i class="fa fa-folder-open fa-3x mb-3 opacity-25 d-block"></i>
                                    Belum ada data absensi pada tahun ini.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="alert alert-light border border-secondary text-muted small mt-3 shadow-sm">
                <i class="fa fa-info-circle me-1"></i> <b>Info:</b> Angka "Hadir" adalah gabungan dari kehadiran Tepat Waktu dan Terlambat.
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>