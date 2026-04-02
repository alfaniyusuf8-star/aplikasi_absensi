<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// PROTEKSI HAK AKSES PENGURUS
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$id_target = isset($_GET['id_user']) ? mysqli_real_escape_string($conn, $_GET['id_user']) : '';

if(empty($id_target)) {
    echo "<script>alert('Pilih jamaah terlebih dahulu!'); window.location='rapor_jamaah.php';</script>"; exit;
}

// =========================================================================
// AUTO PATCH DATABASE: Membuat Kolom Tanggal Daftar Otomatis
// =========================================================================
$cek_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'created_at'");
if(mysqli_num_rows($cek_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    mysqli_query($conn, "UPDATE users SET created_at = '2020-01-01 00:00:00'");
}

// 1. AMBIL PROFIL JAMAAH
$q_user = mysqli_query($conn, "SELECT u.created_at, u.username, u.kelompok, b.* FROM users u JOIN biodata_jamaah b ON u.id_user = b.id_user WHERE u.id_user = '$id_target'");
if(mysqli_num_rows($q_user) == 0) {
    echo "<script>alert('Data jamaah tidak ditemukan!'); window.location='rapor_jamaah.php';</script>"; exit;
}
$user = mysqli_fetch_assoc($q_user);

$nama_tampil = !empty($user['nama_lengkap']) ? $user['nama_lengkap'] : $user['username'];
$url_foto = (!empty($user['foto']) && file_exists('uploads/'.$user['foto'])) ? 'uploads/'.$user['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($nama_tampil).'&background=random&color=fff&bold=true';
$jenjang_user = !empty($user['jenjang']) ? $user['jenjang'] : 'Umum';
$kelompok_user = $user['kelompok'];

// Tanggal Pertama Kali Akun Dibuat
$tgl_daftar_date = date('Y-m-d', strtotime($user['created_at']));

// 2. KALKULASI 6 BULAN TERAKHIR KHUSUS JAMAAH INI
$grafik_label = [];
$grafik_data = [];
$tabel_history = [];
$nama_bulan_indo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

for ($i = 5; $i >= 0; $i--) {
    $time = strtotime("-$i month");
    $b = date('m', $time);
    $t = date('Y', $time);
    $label_bln = $nama_bulan_indo[(int)$b - 1] . ' ' . $t;
    
    $grafik_label[] = $label_bln;

    // Logika Filter Pengajian yang Harus Diikuti Jamaah Ini
    if($jenjang_user == 'Caberawit') {
        $filter_kegiatan = "AND target_jenjang = 'Caberawit'";
    } else {
        $filter_kegiatan = "AND (target_jenjang = 'Semua' OR target_jenjang = 'Umum' OR target_jenjang = '$jenjang_user')";
    }

    // A. Cari Target Kegiatan bulan ini untuk user (Sertakan tgl_buat)
    $q_keg = mysqli_query($conn, "SELECT id_kegiatan, is_selesai, tgl_buat FROM kegiatan WHERE MONTH(tgl_buat) = '$b' AND YEAR(tgl_buat) = '$t' AND (target_kelompok = 'Semua' OR target_kelompok = '$kelompok_user') $filter_kegiatan");
    
    $tepat = 0; $telat = 0; $online = 0; $izin_acc = 0; $alpa = 0;

    while($keg = mysqli_fetch_assoc($q_keg)) {
        
        $keg_date = date('Y-m-d', strtotime($keg['tgl_buat']));
        
        // MENCEGAH BUG: Lewati pengajian yang terjadi SEBELUM dia mendaftar
        if ($keg_date < $tgl_daftar_date) {
            continue; 
        }
        
        $id_k = $keg['id_kegiatan'];
        $is_alpa = true;

        // Cek Hadir
        $q_pres = mysqli_query($conn, "SELECT status_absen FROM presensi WHERE id_kegiatan = '$id_k' AND id_user = '$id_target'");
        if(mysqli_num_rows($q_pres) > 0) {
            $pres = mysqli_fetch_assoc($q_pres);
            if(strtolower($pres['status_absen']) == 'tepat waktu') { $tepat++; $is_alpa = false; }
            elseif(strtolower($pres['status_absen']) == 'terlambat') { $telat++; $is_alpa = false; }
        } else {
            // Cek Izin
            $q_iz = mysqli_query($conn, "SELECT jenis_izin, status_konfirmasi FROM perizinan WHERE id_kegiatan = '$id_k' AND id_user = '$id_target' AND status_izin = 'disetujui'");
            if(mysqli_num_rows($q_iz) > 0) {
                $iz = mysqli_fetch_assoc($q_iz);
                if($iz['jenis_izin'] == 'Online' && $iz['status_konfirmasi'] == 'Disetujui') {
                    $online++; $is_alpa = false;
                } else {
                    $izin_acc++; $is_alpa = false;
                }
            }
        }

        if($is_alpa && $keg['is_selesai'] == 1) { $alpa++; }
    }

    $total_masuk = $tepat + $telat + $online;
    $total_target = $total_masuk + $izin_acc + $alpa;
    $persen = ($total_target > 0) ? round(($total_masuk / $total_target) * 100) : 0;
    if($persen > 100) $persen = 100;

    $grafik_data[] = $persen;
    
    // Simpan ke array tabel dengan di-unshift agar bulan terbaru berada di paling ATAS tabel
    array_unshift($tabel_history, [
        'bulan' => $label_bln, 'target' => $total_target, 'tepat' => $tepat, 
        'telat' => $telat, 'online' => $online, 'izin' => $izin_acc, 
        'alpa' => $alpa, 'persen' => $persen
    ]);
}

function colorBadge($p) {
    if($p >= 80) return 'bg-success'; if($p >= 50) return 'bg-warning text-dark'; return 'bg-danger';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detail Rapor: <?= $nama_tampil; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .profile-img-large { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 4px solid #1a535c; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold text-dark mb-0"><i class="fa fa-address-card text-primary me-2"></i>Rapor Detail: 6 Bulan</h2>
        <a href="rapor_jamaah.php" class="btn btn-outline-dark fw-bold shadow-sm rounded-pill px-4"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-custom bg-white p-4 text-center border-top border-primary border-5 h-100">
                <img src="<?= $url_foto; ?>" class="profile-img-large mx-auto mb-3" alt="Foto">
                <h4 class="fw-bold text-dark mb-1"><?= strtoupper($nama_tampil); ?></h4>
                <p class="text-muted mb-3"><i class="fa fa-venus-mars me-1"></i> <?= ($user['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan'; ?> | <i class="fa fa-phone-alt ms-2 me-1"></i> <?= $user['no_hp'] ?? '-'; ?></p>
                
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge bg-secondary p-2"><i class="fa fa-map-marker-alt me-1"></i> Kel. <?= $kelompok_user; ?></span>
                    <span class="badge bg-success p-2"><i class="fa fa-layer-group me-1"></i> <?= $jenjang_user; ?></span>
                </div>

                <div class="bg-light p-3 rounded text-start border shadow-sm">
                    <small class="text-muted fw-bold d-block mb-2">INFO TAMBAHAN</small>
                    <div style="font-size: 0.85rem;">
                        <div class="mb-1"><b>Status MT:</b> <span class="text-primary"><?= $user['status_mubaligh'] ?? 'Non MT'; ?></span></div>
                        <div class="mb-1"><b>Pekerjaan:</b> <?= $user['pekerjaan'] ?? '-'; ?></div>
                        <div class="mb-1"><b>Asal:</b> <?= $user['alamat_asal'] ?? '-'; ?></div>
                        <div class="mb-1 mt-2 border-top pt-2 text-muted"><i>Bergabung sejak: <?= date('d M Y', strtotime($tgl_daftar_date)); ?></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom bg-white p-4 mb-4 border-start border-info border-5">
                <h6 class="fw-bold text-dark mb-3"><i class="fa fa-chart-area text-info me-2"></i>Grafik Kedisiplinan 6 Bulan Terakhir</h6>
                <div style="height: 250px; width: 100%;">
                    <canvas id="chartTren"></canvas>
                </div>
            </div>

            <div class="card card-custom bg-white p-4 shadow-sm">
                <h6 class="fw-bold text-dark mb-3"><i class="fa fa-table text-warning me-2"></i>Rincian Absensi per Bulan</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>BULAN</th>
                                <th class="text-success"><i class="fa fa-check-circle"></i> TEPAT</th>
                                <th class="text-warning text-dark"><i class="fa fa-clock"></i> TELAT</th>
                                <th class="text-primary"><i class="fa fa-video"></i> ONLINE</th>
                                <th class="text-secondary"><i class="fa fa-envelope"></i> IZIN</th>
                                <th class="text-danger"><i class="fa fa-times-circle"></i> TIDAK HADIR</th>
                                <th>PERSENTASE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tabel_history as $th): ?>
                            <tr>
                                <td class="text-start fw-bold"><?= $th['bulan']; ?> <br><small class="text-muted fw-normal">Trgt: <?= $th['target']; ?> pgn</small></td>
                                <td class="fw-bold text-success fs-5"><?= $th['tepat']; ?></td>
                                <td class="fw-bold text-warning text-dark fs-5"><?= $th['telat']; ?></td>
                                <td class="fw-bold text-primary fs-5"><?= $th['online']; ?></td>
                                <td class="fw-bold text-secondary fs-5"><?= $th['izin']; ?></td>
                                <td class="fw-bold text-danger fs-5"><?= $th['alpa']; ?></td>
                                <td><span class="badge <?= colorBadge($th['persen']); ?> w-100 p-2 fs-6 shadow-sm"><?= $th['persen']; ?>%</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('chartTren').getContext('2d');
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(26, 83, 92, 0.4)');
    gradient.addColorStop(1, 'rgba(26, 83, 92, 0.0)');

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
                pointBackgroundColor: '#1a535c',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.3
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
});
</script>
</body>
</html>