<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['level'] != 'karyawan') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil Profil Singkat Jamaah
$q_profil = mysqli_query($conn, "SELECT u.kelompok, b.jenjang, b.nama_lengkap, b.foto 
                                 FROM users u 
                                 INNER JOIN biodata_jamaah b ON u.id_user = b.id_user 
                                 WHERE u.id_user = '$id_user'");
$profil = mysqli_fetch_assoc($q_profil);

$kel_user = $profil['kelompok'] ?? '';
$jenjang_user = $profil['jenjang'] ?? '';
$nama_user = $profil['nama_lengkap'] ?? $_SESSION['username'];
$foto_user = (!empty($profil['foto']) && file_exists('uploads/'.$profil['foto'])) ? 'uploads/'.$profil['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($nama_user).'&background=0D8ABC&color=fff';

function getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $bulan, $tahun) {
    $q_target = mysqli_query($conn, "SELECT COUNT(id_kegiatan) AS total_wajib FROM kegiatan 
                                     WHERE MONTH(tgl_buat) = '$bulan' AND YEAR(tgl_buat) = '$tahun' AND is_selesai = 1 
                                     AND (target_kelompok = 'Semua' OR target_kelompok = '$kel_user') 
                                     AND (target_jenjang = 'Semua' OR target_jenjang = 'Umum' OR target_jenjang = '$jenjang_user')");
    $target = mysqli_fetch_assoc($q_target)['total_wajib'];

    $q_hadir = mysqli_query($conn, "SELECT COUNT(p.id_presensi) AS total_hadir FROM presensi p
                                    INNER JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan
                                    WHERE p.id_user = '$id_user' 
                                    AND p.status_absen IN ('tepat waktu', 'terlambat', 'hadir') 
                                    AND MONTH(k.tgl_buat) = '$bulan' AND YEAR(k.tgl_buat) = '$tahun'");
    $hadir = mysqli_fetch_assoc($q_hadir)['total_hadir'];

    $q_izin = mysqli_query($conn, "SELECT COUNT(p.id_presensi) AS total_izin FROM presensi p
                                   INNER JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan
                                   WHERE p.id_user = '$id_user' 
                                   AND p.status_absen IN ('izin', 'sakit') 
                                   AND MONTH(k.tgl_buat) = '$bulan' AND YEAR(k.tgl_buat) = '$tahun'");
    $izin = mysqli_fetch_assoc($q_izin)['total_izin'];

    $persentase = ($target > 0) ? round(($hadir / $target) * 100) : 0;
    $alpa = $target - $hadir - $izin;
    if($alpa < 0) $alpa = 0;

    return ['target' => $target, 'hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa, 'persentase' => $persentase];
}

$bulan_ini = date('m'); $tahun_ini = date('Y');
$bulan_lalu = date('m', strtotime('-1 month')); $tahun_lalu = date('Y', strtotime('-1 month'));

$stat_ini = getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $bulan_ini, $tahun_ini);
$stat_lalu = getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $bulan_lalu, $tahun_lalu);

$selisih = $stat_ini['persentase'] - $stat_lalu['persentase'];
if ($selisih > 0) { $indikator_selisih = "<span class='text-success fw-bold'><i class='fa fa-arrow-up me-1'></i> Naik $selisih%</span>"; } 
elseif ($selisih < 0) { $indikator_selisih = "<span class='text-danger fw-bold'><i class='fa fa-arrow-down me-1'></i> Turun ".abs($selisih)."%</span>"; } 
else { $indikator_selisih = "<span class='text-muted fw-bold'><i class='fa fa-minus me-1'></i> Stabil</span>"; }

$label_grafik = []; $data_grafik = [];
$nama_bulan_indo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

for ($i = 5; $i >= 0; $i--) {
    $b = date('m', strtotime("-$i month"));
    $t = date('Y', strtotime("-$i month"));
    $stat = getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $b, $t);
    $label_grafik[] = $nama_bulan_indo[(int)$b - 1];
    $data_grafik[] = $stat['persentase'];
}

$q_riwayat = mysqli_query($conn, "SELECT k.judul_pengajian, k.tgl_buat, p.status_absen 
                                  FROM kegiatan k 
                                  LEFT JOIN presensi p ON k.id_kegiatan = p.id_kegiatan AND p.id_user = '$id_user'
                                  WHERE k.is_selesai = 1 
                                  AND (k.target_kelompok = 'Semua' OR k.target_kelompok = '$kel_user')
                                  AND (k.target_jenjang = 'Semua' OR k.target_jenjang = 'Umum' OR k.target_jenjang = '$jenjang_user')
                                  ORDER BY k.tgl_buat DESC LIMIT 30");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik & Riwayat | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #1a535c; color: white; padding: 20px; z-index: 1000; }
        .main-content { margin-left: 250px; padding: 30px; }
        .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 10px; border-radius: 10px; }
        .nav-link.active { background: #4ecdc4; color: #1a535c; font-weight: bold; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-box { text-align: center; padding: 12px 5px; border-radius: 12px; }
        .timeline-item { border-left: 3px solid #4ecdc4; padding-left: 15px; margin-bottom: 15px; position: relative; }
        .timeline-item::before { content: ""; position: absolute; left: -8px; top: 0; width: 13px; height: 13px; background: #1a535c; border-radius: 50%; border: 2px solid white; }
        .month-header { font-size: 0.9rem; font-weight: 800; color: #1a535c; background: #e9ecef; padding: 5px 15px; border-radius: 20px; display: inline-block; margin-bottom: 15px; margin-top: 10px; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-4"><h4 class="fw-bold"><i class="fa fa-mosque me-2"></i>AbsenNgaji</h4><hr class="opacity-25"></div>
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fa fa-home me-2"></i> Dashboard</a>
        <a href="statistik_jamaah.php" class="nav-link active"><i class="fa fa-chart-pie me-2"></i> Statistik & Riwayat</a>
        <a href="edit_biodata.php" class="nav-link"><i class="fa fa-address-card me-2"></i> Biodata Saya</a>
        <a href="logout.php" class="nav-link text-danger mt-5"><i class="fa fa-sign-out-alt me-2"></i> Keluar</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h3 class="fw-bold text-dark"><i class="fa fa-chart-pie text-primary me-2"></i>Statistik & Riwayat Absen</h3>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card card-custom p-4 bg-white h-100 text-center border-top border-primary border-4">
                <h6 class="fw-bold text-dark mb-3 text-uppercase">Statistik Bulan Ini</h6>
                <div class="position-relative mx-auto mb-3" style="width: 150px; height: 150px;">
                    <canvas id="chartBulanIni"></canvas>
                    <div class="position-absolute top-50 start-50 translate-middle text-center"><h3 class="fw-bold text-dark mb-0"><?= $stat_ini['persentase']; ?>%</h3></div>
                </div>
                <div class="small bg-light p-2 rounded-pill d-inline-block px-3 mb-4"><?= $indikator_selisih; ?> dari bulan lalu</div>
                <div class="row g-2">
                    <div class="col-4"><div class="stat-box bg-light"><h4 class="fw-bold text-success mb-0"><?= $stat_ini['hadir']; ?></h4><small>Hadir</small></div></div>
                    <div class="col-4"><div class="stat-box bg-light"><h4 class="fw-bold text-warning mb-0"><?= $stat_ini['izin']; ?></h4><small>Izin</small></div></div>
                    <div class="col-4"><div class="stat-box bg-light"><h4 class="fw-bold text-danger mb-0"><?= $stat_ini['alpa']; ?></h4><small>Alpa</small></div></div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom p-4 bg-white h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="fa fa-chart-line text-primary me-2"></i>Tren 6 Bulan Terakhir</h6>
                <div style="height: 250px; width: 100%;"><canvas id="kehadiranChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card card-custom p-4 bg-white">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="fa fa-history text-primary me-2"></i>Riwayat Pengajian Saya</h6>
        <?php 
        if(mysqli_num_rows($q_riwayat) > 0): 
            $bulan_sebelumnya = "";
            $bulan_indo_full = ['January'=>'Januari', 'February'=>'Februari', 'March'=>'Maret', 'April'=>'April', 'May'=>'Mei', 'June'=>'Juni', 'July'=>'Juli', 'August'=>'Agustus', 'September'=>'September', 'October'=>'Oktober', 'November'=>'November', 'December'=>'Desember'];

            while($r = mysqli_fetch_assoc($q_riwayat)): 
                $bulan_inggris = date('F Y', strtotime($r['tgl_buat']));
                $bulan_tahun_sekarang = strtr($bulan_inggris, $bulan_indo_full);

                if ($bulan_sebelumnya != $bulan_tahun_sekarang) {
                    echo "<div class='w-100'><span class='month-header'><i class='fa fa-calendar-alt me-2'></i>$bulan_tahun_sekarang</span></div>";
                    $bulan_sebelumnya = $bulan_tahun_sekarang;
                }

                $status = strtolower($r['status_absen'] ?? '');
                if(in_array($status, ['tepat waktu', 'terlambat', 'hadir'])) { $badge = 'bg-success'; $teks = 'HADIR'; $icon = 'fa-check'; } 
                elseif($status == 'izin') { $badge = 'bg-warning text-dark'; $teks = 'IZIN'; $icon = 'fa-envelope'; } 
                elseif($status == 'sakit') { $badge = 'bg-info text-dark'; $teks = 'SAKIT'; $icon = 'fa-hospital'; } 
                else { $badge = 'bg-danger'; $teks = 'ALPA'; $icon = 'fa-times'; }
        ?>
            <div class="timeline-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark"><?= $r['judul_pengajian']; ?></h6>
                        <small class="text-muted"><i class="fa fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($r['tgl_buat'])); ?> WIB</small>
                    </div>
                    <span class="badge <?= $badge; ?> px-3 py-2 rounded-pill"><i class="fa <?= $icon; ?> me-1"></i> <?= $teks; ?></span>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="text-center text-muted my-4"><i class="fa fa-folder-open fa-3x mb-3 opacity-25"></i><p>Belum ada riwayat pengajian tercatat.</p></div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxDoughnut = document.getElementById('chartBulanIni').getContext('2d');
new Chart(ctxDoughnut, {
    type: 'doughnut',
    data: { labels: ['Hadir', 'Izin/Sakit', 'Alpa'], datasets: [{ data: [<?= $stat_ini['hadir']; ?>, <?= $stat_ini['izin']; ?>, <?= $stat_ini['alpa']; ?>], backgroundColor: ['#4ecdc4', '#ffe66d', '#ff6b6b'], borderWidth: 2, borderColor: '#ffffff' }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
});

const ctxLine = document.getElementById('kehadiranChart').getContext('2d');
let gradient = ctxLine.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(78, 205, 196, 0.5)'); gradient.addColorStop(1, 'rgba(78, 205, 196, 0.0)');

new Chart(ctxLine, {
    type: 'line',
    data: { labels: <?= json_encode($label_grafik); ?>, datasets: [{ data: <?= json_encode($data_grafik); ?>, borderColor: '#1a535c', backgroundColor: gradient, borderWidth: 3, pointBackgroundColor: '#fff', pointBorderColor: '#1a535c', fill: true, tension: 0.4 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>
</body>
</html>