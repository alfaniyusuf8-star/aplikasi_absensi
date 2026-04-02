<?php
session_start();
include 'koneksi.php';

// Proteksi: Hanya Jamaah (Karyawan) yang boleh masuk
if (!isset($_SESSION['id_user']) || $_SESSION['level'] != 'karyawan') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 1. Ambil Profil Singkat Jamaah
$q_profil = mysqli_query($conn, "SELECT u.kelompok, b.jenjang, b.nama_lengkap, b.foto 
                                 FROM users u 
                                 INNER JOIN biodata_jamaah b ON u.id_user = b.id_user 
                                 WHERE u.id_user = '$id_user'");
$profil = mysqli_fetch_assoc($q_profil);

$kel_user = $profil['kelompok'] ?? '';
$jenjang_user = $profil['jenjang'] ?? '';
$nama_user = $profil['nama_lengkap'] ?? $_SESSION['username'];
$foto_user = !empty($profil['foto']) ? 'uploads/'.$profil['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($nama_user).'&background=0D8ABC&color=fff';

// 2. FUNGSI MENGHITUNG PERSENTASE (Bulan Ini & Bulan Lalu)
function getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $bulan, $tahun) {
    // Hitung Total Pengajian yang WAJIB diikuti jamaah ini pada bulan tsb
    $q_target = mysqli_query($conn, "SELECT COUNT(id_kegiatan) AS total_wajib FROM kegiatan 
                                     WHERE MONTH(tgl_buat) = '$bulan' AND YEAR(tgl_buat) = '$tahun' AND is_selesai = 1 
                                     AND (target_kelompok = 'Semua' OR target_kelompok = '$kel_user') 
                                     AND (target_jenjang = 'Semua' OR target_jenjang = 'Umum' OR target_jenjang = '$jenjang_user')");
    $target = mysqli_fetch_assoc($q_target)['total_wajib'];

    // Hitung Total Kehadiran jamaah ini pada bulan tsb
    $q_hadir = mysqli_query($conn, "SELECT COUNT(p.id_presensi) AS total_hadir FROM presensi p
                                    INNER JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan
                                    WHERE p.id_user = '$id_user' 
                                    AND p.status_absen IN ('tepat waktu', 'terlambat', 'hadir') 
                                    AND MONTH(k.tgl_buat) = '$bulan' AND YEAR(k.tgl_buat) = '$tahun'");
    $hadir = mysqli_fetch_assoc($q_hadir)['total_hadir'];

    // Hitung Total Izin/Sakit
    $q_izin = mysqli_query($conn, "SELECT COUNT(p.id_presensi) AS total_izin FROM presensi p
                                   INNER JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan
                                   WHERE p.id_user = '$id_user' 
                                   AND p.status_absen IN ('izin', 'sakit') 
                                   AND MONTH(k.tgl_buat) = '$bulan' AND YEAR(k.tgl_buat) = '$tahun'");
    $izin = mysqli_fetch_assoc($q_izin)['total_izin'];

    $persentase = ($target > 0) ? round(($hadir / $target) * 100) : 0;
    $alpa = $target - $hadir - $izin;
    if($alpa < 0) $alpa = 0; // Mencegah angka minus jika ada anomali

    return ['target' => $target, 'hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa, 'persentase' => $persentase];
}

$bulan_ini = date('m'); $tahun_ini = date('Y');
$bulan_lalu = date('m', strtotime('-1 month')); $tahun_lalu = date('Y', strtotime('-1 month'));

$stat_ini = getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $bulan_ini, $tahun_ini);
$stat_lalu = getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $bulan_lalu, $tahun_lalu);

// Hitung Selisih Persentase
$selisih = $stat_ini['persentase'] - $stat_lalu['persentase'];
$indikator_selisih = "";
if ($selisih > 0) {
    $indikator_selisih = "<span class='text-success fw-bold'><i class='fa fa-arrow-up me-1'></i> Naik $selisih%</span> dari bulan lalu";
} elseif ($selisih < 0) {
    $indikator_selisih = "<span class='text-danger fw-bold'><i class='fa fa-arrow-down me-1'></i> Turun ".abs($selisih)."%</span> dari bulan lalu";
} else {
    $indikator_selisih = "<span class='text-muted fw-bold'><i class='fa fa-minus me-1'></i> Stabil</span> (Sama dengan bulan lalu)";
}

// 3. AMBIL DATA GRAFIK (6 BULAN TERAKHIR)
$label_grafik = []; $data_grafik = [];
for ($i = 5; $i >= 0; $i--) {
    $b = date('m', strtotime("-$i month"));
    $t = date('Y', strtotime("-$i month"));
    $nama_bulan = date('M', strtotime("-$i month")); // Contoh: Jan, Feb, Mar
    
    $stat = getStatistikBulan($conn, $id_user, $kel_user, $jenjang_user, $b, $t);
    $label_grafik[] = $nama_bulan;
    $data_grafik[] = $stat['persentase'];
}

// 4. AMBIL RIWAYAT ABSEN TERBARU (5 Terakhir)
$q_riwayat = mysqli_query($conn, "SELECT k.judul_pengajian, k.tgl_buat, p.status_absen 
                                  FROM kegiatan k 
                                  LEFT JOIN presensi p ON k.id_kegiatan = p.id_kegiatan AND p.id_user = '$id_user'
                                  WHERE k.is_selesai = 1 
                                  AND (k.target_kelompok = 'Semua' OR k.target_kelompok = '$kel_user')
                                  AND (k.target_jenjang = 'Semua' OR k.target_jenjang = 'Umum' OR k.target_jenjang = '$jenjang_user')
                                  ORDER BY k.tgl_buat DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Jamaah | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; padding-bottom: 50px; }
        .navbar-custom { background: #1a535c; padding: 15px 20px; color: white; border-radius: 0 0 20px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;}
        
        /* Lingkaran Persentase */
        .progress-circle { width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: conic-gradient(#4ecdc4 <?= $stat_ini['persentase']; ?>%, #e9ecef 0deg); position: relative; margin: 0 auto; }
        .progress-circle::after { content: ""; width: 95px; height: 95px; background: white; border-radius: 50%; position: absolute; }
        .progress-text { position: absolute; z-index: 1; font-weight: 900; font-size: 1.8rem; color: #1a535c; }
        
        .stat-box { text-align: center; padding: 15px; border-radius: 12px; }
        .stat-box.hadir { background: #e0f8f5; color: #1a535c; }
        .stat-box.izin { background: #fff5e6; color: #d35400; }
        .stat-box.alpa { background: #fde8e8; color: #c0392b; }
        
        .timeline-item { border-left: 3px solid #4ecdc4; padding-left: 15px; margin-bottom: 15px; position: relative; }
        .timeline-item::before { content: ""; position: absolute; left: -8px; top: 0; width: 13px; height: 13px; background: #1a535c; border-radius: 50%; border: 2px solid white; }
    </style>
</head>
<body>

<div class="navbar-custom d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
        <img src="<?= $foto_user; ?>" class="rounded-circle border border-2 border-white me-3" width="50" height="50" style="object-fit:cover;">
        <div>
            <h5 class="fw-bold mb-0">Halo, <?= strtoupper($nama_user); ?>!</h5>
            <small class="opacity-75"><i class="fa fa-map-marker-alt me-1"></i> Kel. <?= $kel_user; ?> | <?= $jenjang_user; ?></small>
        </div>
    </div>
    <a href="logout.php" class="btn btn-outline-light btn-sm fw-bold rounded-pill"><i class="fa fa-sign-out-alt"></i> Keluar</a>
</div>

<div class="container">
    <div class="row g-4 mb-4">
        
        <div class="col-lg-4">
            <div class="card card-custom p-4 bg-white h-100 text-center border-top border-primary border-4">
                <h6 class="fw-bold text-dark mb-4 text-uppercase">Rapor Kehadiran Bulan Ini</h6>
                
                <div class="progress-circle mb-3">
                    <span class="progress-text"><?= $stat_ini['persentase']; ?>%</span>
                </div>
                
                <div class="small bg-light p-2 rounded-pill d-inline-block px-3 mb-4">
                    <?= $indikator_selisih; ?>
                </div>

                <div class="row g-2">
                    <div class="col-4">
                        <div class="stat-box hadir">
                            <h4 class="fw-bold mb-0"><?= $stat_ini['hadir']; ?></h4><small class="fw-bold text-uppercase" style="font-size:0.7rem;">Hadir</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box izin">
                            <h4 class="fw-bold mb-0"><?= $stat_ini['izin']; ?></h4><small class="fw-bold text-uppercase" style="font-size:0.7rem;">Izin/S</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box alpa">
                            <h4 class="fw-bold mb-0"><?= $stat_ini['alpa']; ?></h4><small class="fw-bold text-uppercase" style="font-size:0.7rem;">Alpa</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa fa-chart-line text-primary me-2"></i>Grafik Kehadiran (6 Bulan Terakhir)</h6>
                </div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="kehadiranChart"></canvas>
                </div>
            </div>
        </div>
        
    </div>

    <div class="card card-custom p-4 bg-white">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa fa-history text-primary me-2"></i>5 Pengajian Terakhir Anda</h6>
        
        <?php if(mysqli_num_rows($q_riwayat) > 0): ?>
            <?php while($r = mysqli_fetch_assoc($q_riwayat)): 
                // Penentuan Label & Warna
                $status = strtolower($r['status_absen'] ?? '');
                if(in_array($status, ['tepat waktu', 'terlambat', 'hadir'])) { 
                    $badge = 'bg-success'; $teks = 'HADIR'; $icon = 'fa-check';
                } elseif($status == 'izin') { 
                    $badge = 'bg-warning text-dark'; $teks = 'IZIN'; $icon = 'fa-envelope';
                } elseif($status == 'sakit') { 
                    $badge = 'bg-info text-dark'; $teks = 'SAKIT'; $icon = 'fa-hospital';
                } else { 
                    $badge = 'bg-danger'; $teks = 'ALPA (Tanpa Keterangan)'; $icon = 'fa-times';
                }
            ?>
            <div class="timeline-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark"><?= $r['judul_pengajian']; ?></h6>
                        <small class="text-muted"><i class="fa fa-calendar-alt me-1"></i> <?= date('d M Y, H:i', strtotime($r['tgl_buat'])); ?></small>
                    </div>
                    <span class="badge <?= $badge; ?> px-3 py-2 rounded-pill"><i class="fa <?= $icon; ?> me-1"></i> <?= $teks; ?></span>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center text-muted my-4">
                <i class="fa fa-folder-open fa-3x mb-3 opacity-25"></i>
                <p>Belum ada riwayat pengajian yang tercatat untuk kelompok/jenjang Anda.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Data dari PHP
const labelsBulan = <?= json_encode($label_grafik); ?>;
const dataPersen = <?= json_encode($data_grafik); ?>;

// Render Grafik Garis (Line Chart)
const ctx = document.getElementById('kehadiranChart').getContext('2d');

// Membuat efek gradien biru di bawah garis
let gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(78, 205, 196, 0.5)'); // Warna #4ecdc4 transparan
gradient.addColorStop(1, 'rgba(78, 205, 196, 0.0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labelsBulan,
        datasets: [{
            label: 'Persentase Kehadiran (%)',
            data: dataPersen,
            borderColor: '#1a535c',
            backgroundColor: gradient,
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#1a535c',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true, // Mengisi area bawah garis
            tension: 0.4 // Membuat garis melengkung (smooth)
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) { return ' Kehadiran: ' + context.parsed.y + '%'; }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100, // Karena persentase maksimal 100
                ticks: { callback: function(value) { return value + '%'; } }
            }
        }
    }
});
</script>

</body>
</html>