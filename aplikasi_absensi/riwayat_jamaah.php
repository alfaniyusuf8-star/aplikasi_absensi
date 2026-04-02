<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$level   = $_SESSION['level'];

// Tentukan arah tombol kembali / dashboard
$link_dashboard = ($level == 'karyawan') ? 'dashboard.php' : 'dashboard_keimaman.php';

// 1. Ambil Kelompok, Jenjang, dan Tanggal Daftar
$q_user = mysqli_query($conn, "SELECT u.kelompok, u.tgl_daftar, b.jenjang, b.nama_lengkap 
                               FROM users u 
                               LEFT JOIN biodata_jamaah b ON u.id_user = b.id_user 
                               WHERE u.id_user = '$id_user'");
$d_user = mysqli_fetch_assoc($q_user);

$kel_user = $d_user['kelompok'] ?? '';
$jenjang_user = $d_user['jenjang'] ?? '';
$nama_user = $d_user['nama_lengkap'] ?? $_SESSION['username'];

// Ambil tanggal daftar
$tgl_daftar = '2000-01-01 00:00:00';
if (!empty($d_user['tgl_daftar']) && $d_user['tgl_daftar'] != '0000-00-00 00:00:00') {
    $tgl_daftar = $d_user['tgl_daftar'];
}

// 2. Query Ambil Riwayat Pengajian SAYA
$query_riwayat = "
    SELECT k.id_kegiatan, k.judul_pengajian, k.tgl_buat, 
           p.status_absen,
           z.status_izin, z.jenis_izin, z.status_konfirmasi
    FROM kegiatan k 
    LEFT JOIN presensi p ON k.id_kegiatan = p.id_kegiatan AND p.id_user = '$id_user'
    LEFT JOIN perizinan z ON k.id_kegiatan = z.id_kegiatan AND z.id_user = '$id_user' AND z.status_izin = 'disetujui'
    WHERE k.is_selesai = 1 
    AND (k.target_kelompok = 'Semua' OR k.target_kelompok = '$kel_user')
    AND (k.target_jenjang = 'Semua' OR k.target_jenjang = '$jenjang_user')
    AND k.tgl_buat >= '$tgl_daftar'
    ORDER BY k.tgl_buat DESC
";
$result = mysqli_query($conn, $query_riwayat);

// 3. Olah Data dan Kelompokkan Per Bulan
$data_per_bulan = [];
$bulan_indo = [
    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
    'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli',
    'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober',
    'November' => 'November', 'December' => 'Desember'
];

while($row = mysqli_fetch_assoc($result)) {
    $kode_bulan = date('Y-m', strtotime($row['tgl_buat']));
    $nama_bulan_eng = date('F Y', strtotime($row['tgl_buat']));
    $nama_bulan_id = strtr($nama_bulan_eng, $bulan_indo);

    // Penentuan Status Final
    if (!empty($row['status_absen']) && in_array(strtolower($row['status_absen']), ['hadir', 'tepat waktu'])) {
        $status_final = 'Hadir Tepat'; $badge = 'bg-success'; $icon = 'fa-check-circle';
    } elseif (!empty($row['status_absen']) && strtolower($row['status_absen']) == 'terlambat') {
        $status_final = 'Hadir Telat'; $badge = 'bg-warning text-dark'; $icon = 'fa-clock';
    } elseif (!empty($row['status_izin']) && $row['status_izin'] == 'disetujui') {
        if ($row['jenis_izin'] == 'Online' && $row['status_konfirmasi'] == 'Disetujui') {
            $status_final = 'Hadir Online'; $badge = 'bg-primary'; $icon = 'fa-video';
        } else {
            $status_final = 'Izin / Sakit'; $badge = 'bg-secondary'; $icon = 'fa-envelope-open-text';
        }
    } else {
        $status_final = 'Alpa'; $badge = 'bg-danger'; $icon = 'fa-times-circle';
    }

    if (!isset($data_per_bulan[$kode_bulan])) {
        $data_per_bulan[$kode_bulan] = [
            'nama_bulan' => $nama_bulan_id,
            'total' => 0, 'hadir_off' => 0, 'hadir_on' => 0, 'izin' => 0, 'alpa' => 0,
            'kegiatan' => []
        ];
    }

    $data_per_bulan[$kode_bulan]['total']++;
    if ($status_final == 'Hadir Tepat' || $status_final == 'Hadir Telat') {
        $data_per_bulan[$kode_bulan]['hadir_off']++;
    } elseif ($status_final == 'Hadir Online') {
        $data_per_bulan[$kode_bulan]['hadir_on']++;
    } elseif ($status_final == 'Izin / Sakit') {
        $data_per_bulan[$kode_bulan]['izin']++;
    } else {
        $data_per_bulan[$kode_bulan]['alpa']++;
    }

    $row['status_final'] = $status_final;
    $row['badge_final'] = $badge;
    $row['icon_final'] = $icon;
    $data_per_bulan[$kode_bulan]['kegiatan'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat & Statistik | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #1a535c; color: white; padding: 20px; z-index: 1000; overflow-y: auto;}
        .main-content { margin-left: 250px; padding: 30px; }
        .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 5px; border-radius: 8px; transition: 0.3s; padding: 10px 15px; }
        .nav-link.active, .nav-link:hover { background: #4ecdc4; color: #1a535c; font-weight: bold; }
        .accordion-button:not(.collapsed) { background-color: #e0f8f5; color: #1a535c; font-weight: bold; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold text-dark"><i class="fa fa-history text-primary me-2"></i>Riwayat Bulanan</h2>
        <a href="<?= $link_dashboard; ?>" class="btn btn-outline-dark fw-bold btn-sm shadow-sm d-md-none"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <?php if(empty($jenjang_user)): ?>
        <div class="alert alert-danger shadow-sm border-start border-danger border-5">
            <b>Perhatian!</b> Anda belum mengisi biodata. Riwayat pengajian tidak akan muncul sampai Anda melengkapi profil jenjang dan kelompok Anda.
        </div>
    <?php elseif(empty($data_per_bulan)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa fa-folder-open fa-4x mb-3 opacity-25"></i>
            <h4>Belum ada riwayat pengajian</h4>
            <p>Jadwal pengajian yang ditargetkan untuk Anda dan sudah ditutup oleh Admin akan terekap di sini.</p>
        </div>
    <?php else: ?>

        <div class="accordion shadow-sm" id="accordionRiwayat">
            <?php 
            $i = 0;
            foreach($data_per_bulan as $kode => $data): 
                $total_masuk_bulan = $data['hadir_off'] + $data['hadir_on'];
                $persentase = ($data['total'] > 0) ? round(($total_masuk_bulan / $data['total']) * 100, 1) : 0;
                
                $bg_progress = 'bg-success';
                if($persentase < 50) $bg_progress = 'bg-danger';
                elseif($persentase < 80) $bg_progress = 'bg-warning';
            ?>
                <div class="accordion-item border-0 border-bottom mb-2" style="border-radius: 10px; overflow: hidden;">
                    <h2 class="accordion-header" id="heading-<?= $kode; ?>">
                        <button class="accordion-button <?= ($i == 0) ? '' : 'collapsed'; ?> p-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $kode; ?>">
                            <div class="w-100 pe-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-calendar-alt text-primary me-2"></i><?= $data['nama_bulan']; ?></h5>
                                    <h4 class="fw-bold mb-0 text-primary"><?= $persentase; ?>%</h4>
                                </div>
                                
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar <?= $bg_progress; ?> progress-bar-striped" style="width: <?= $persentase; ?>%;"></div>
                                </div>

                                <div class="d-flex gap-3 small text-muted fw-bold mt-2 flex-wrap">
                                    <span class="text-success"><i class="fa fa-building"></i> Off: <?= $data['hadir_off']; ?></span>
                                    <span class="text-primary"><i class="fa fa-video"></i> On: <?= $data['hadir_on']; ?></span>
                                    <span class="text-secondary"><i class="fa fa-envelope"></i> Izin: <?= $data['izin']; ?></span>
                                    <span class="text-danger"><i class="fa fa-times-circle"></i> Tidak Hadir: <?= $data['alpa']; ?></span>
                                </div>
                            </div>
                        </button>
                    </h2>
                    
                    <div id="collapse-<?= $kode; ?>" class="accordion-collapse collapse <?= ($i == 0) ? 'show' : ''; ?>" data-bs-parent="#accordionRiwayat">
                        <div class="accordion-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach($data['kegiatan'] as $keg): ?>
                                    <li class="list-group-item p-3 d-flex justify-content-between align-items-center bg-light flex-wrap">
                                        <div class="mb-2 mb-md-0">
                                            <h6 class="fw-bold text-dark mb-1"><?= $keg['judul_pengajian']; ?></h6>
                                            <small class="text-muted"><i class="fa fa-clock me-1"></i><?= date('d M Y, H:i', strtotime($keg['tgl_buat'])); ?></small>
                                        </div>
                                        <span class="badge <?= $keg['badge_final']; ?> px-3 py-2 rounded-pill shadow-sm fs-6">
                                            <i class="fa <?= $keg['icon_final']; ?> me-1"></i> <?= $keg['status_final']; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php $i++; endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>