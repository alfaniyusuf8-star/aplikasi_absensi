<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// 1. PROTEKSI HAK AKSES
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$level = $_SESSION['level'];
$kelompok = $_SESSION['kelompok'];
$can_edit = !in_array($level, ['keimaman', 'keimaman_desa', 'ketua_mudai']);
$id_kegiatan_url = isset($_GET['id_kegiatan']) ? mysqli_real_escape_string($conn, $_GET['id_kegiatan']) : 0;

// =========================================================================
// LOGIKA FILTER KELOMPOK & JENJANG (SESUAI JABATAN)
// =========================================================================
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'admin_mudai_desa']);

// 2. AMBIL DATA PENGAJIAN
$q_keg_awal = mysqli_query($conn, "SELECT * FROM kegiatan WHERE id_kegiatan = '$id_kegiatan_url'");
if(mysqli_num_rows($q_keg_awal) == 0) {
    echo "<script>alert('Data pengajian tidak ditemukan!'); window.location='riwayat_pengajian.php';</script>";
    exit;
}
$kegiatan = mysqli_fetch_assoc($q_keg_awal);

$judul_asli = $kegiatan['judul_pengajian']; // Untuk text notifikasi
$judul_sync = mysqli_real_escape_string($conn, $kegiatan['judul_pengajian']);
$tgl_buat_sync = $kegiatan['tgl_buat'];
$where_sync = "judul_pengajian = '$judul_sync' AND tgl_buat = '$tgl_buat_sync'";

$q_kembar = mysqli_query($conn, "SELECT id_kegiatan, target_kelompok FROM kegiatan WHERE $where_sync");
$arr_id_keg = [];
$arr_target_kel = [];
while($k = mysqli_fetch_assoc($q_kembar)) {
    $arr_id_keg[] = $k['id_kegiatan'];
    $arr_target_kel[] = $k['target_kelompok'];
}
$in_id_keg = implode(',', $arr_id_keg); 

// DETEKSI APAKAH INI ACARA GABUNGAN
$is_event_gabungan = (in_array('Semua', $arr_target_kel) || count($arr_target_kel) > 1);

// HAK AKSES SAKLAR SISTEM: Admin Lokal tidak boleh matikan/hidupkan acara gabungan Desa
$can_switch = $can_edit;
if (!$is_pusat && $is_event_gabungan) {
    $can_switch = false;
}

// 3. PROSES AKSI TOMBOL (DENGAN SUNTIKAN NOTIFIKASI)
if (isset($_GET['aksi']) && $can_edit) {
    $aksi = $_GET['aksi'];
    
    // Proses Saklar Sistem (Hanya yang berhak)
    if (in_array($aksi, ['buka_absen', 'tutup_absen', 'buka_izin', 'tutup_izin', 'selesai'])) {
        if (!$can_switch) {
            echo "<script>alert('Akses Ditolak! Karena ini adalah acara Gabungan, saklar sistem hanya boleh dikontrol oleh Admin Desa.'); window.location='detail_absen.php?id_kegiatan=$id_kegiatan_url';</script>";
            exit;
        }

        if ($aksi == 'buka_absen') {
            $waktu_sekarang = date('Y-m-d H:i:s');
            if(mysqli_query($conn, "UPDATE kegiatan SET status_buka = 1, waktu_buka_absen = '$waktu_sekarang' WHERE $where_sync")) {
                // SUNTIKAN NOTIFIKASI
                kirim_notif_semua($conn, "Absen Dibuka! 🕌", "Absensi untuk pengajian " . strtoupper($judul_asli) . " telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.", "dashboard.php");
            }
        } elseif ($aksi == 'tutup_absen') {
            mysqli_query($conn, "UPDATE kegiatan SET status_buka = 0 WHERE $where_sync");
        } elseif ($aksi == 'buka_izin') {
            if(mysqli_query($conn, "UPDATE kegiatan SET status_izin = 1 WHERE $where_sync")) {
                // SUNTIKAN NOTIFIKASI
                kirim_notif_semua($conn, "Penerimaan Izin Dibuka 📝", "Bagi jamaah yang berhalangan hadir pada acara " . strtoupper($judul_asli) . ", form pengajuan izin sudah bisa diakses.", "dashboard.php");
            }
        } elseif ($aksi == 'tutup_izin') {
            mysqli_query($conn, "UPDATE kegiatan SET status_izin = 0 WHERE $where_sync");
        } elseif ($aksi == 'selesai') {
            if(mysqli_query($conn, "UPDATE kegiatan SET is_selesai = 1, status_buka = 0, status_izin = 0 WHERE $where_sync")) {
                // SUNTIKAN NOTIFIKASI
                kirim_notif_semua($conn, "Pengajian Selesai 🚪", "Alhamdulillah, pengajian " . strtoupper($judul_asli) . " telah resmi ditutup. Sampai jumpa di pengajian berikutnya!", "dashboard.php");
            }
        }
        echo "<script>window.location='detail_absen.php?id_kegiatan=$id_kegiatan_url';</script>";
    } 
    // Proses Manual Absen (Admin Kelompok BOLEH melakukannya untuk jamaahnya sendiri)
    elseif ($aksi == 'manual_absen') {
        $u_id = mysqli_real_escape_string($conn, $_GET['u']);
        $st = mysqli_real_escape_string($conn, $_GET['st']);

        $q_ukel = mysqli_query($conn, "SELECT kelompok FROM users WHERE id_user = '$u_id'");
        $u_kel = mysqli_fetch_assoc($q_ukel)['kelompok'];

        $q_keg_user = mysqli_query($conn, "SELECT id_kegiatan FROM kegiatan WHERE $where_sync AND (target_kelompok = '$u_kel' OR target_kelompok = 'Semua') LIMIT 1");
        if(mysqli_num_rows($q_keg_user) > 0) {
            $id_keg_target = mysqli_fetch_assoc($q_keg_user)['id_kegiatan'];

            mysqli_query($conn, "DELETE FROM presensi WHERE id_user = '$u_id' AND id_kegiatan = '$id_keg_target'");
            mysqli_query($conn, "DELETE FROM perizinan WHERE id_user = '$u_id' AND id_kegiatan = '$id_keg_target'");

            $tgl_now = date('Y-m-d H:i:s');
            if ($st == 'hadir') {
                mysqli_query($conn, "INSERT INTO presensi (id_kegiatan, id_user, tgl_presensi, status_absen) VALUES ('$id_keg_target', '$u_id', '$tgl_now', 'tepat waktu')");
            } elseif ($st == 'telat') {
                mysqli_query($conn, "INSERT INTO presensi (id_kegiatan, id_user, tgl_presensi, status_absen) VALUES ('$id_keg_target', '$u_id', '$tgl_now', 'terlambat')");
            } elseif ($st == 'izin') {
                mysqli_query($conn, "INSERT INTO perizinan (id_kegiatan, id_user, jenis_izin, alasan, tgl_pengajuan, status_izin, status_konfirmasi) VALUES ('$id_keg_target', '$u_id', 'Tidak Hadir', 'Diinput manual oleh Pengurus', '$tgl_now', 'disetujui', 'Disetujui')");
            }
        }
        echo "<script>window.location='detail_absen.php?id_kegiatan=$id_kegiatan_url';</script>";
    }
}

// 4. FILTER TARGET JAMAAH
$target_jen = $kegiatan['target_jenjang'];
$kelompok_to_show = [];

if ($is_pusat) {
    if (in_array('Semua', $arr_target_kel)) {
        $filter_target_kel = "1";
        $label_kelompok_tampil = "Gabungan Se-Desa";
        $kelompok_to_show = ['Semampir', 'Keputih', 'Praja'];
    } else {
        $kel_joined = "'" . implode("','", $arr_target_kel) . "'";
        $filter_target_kel = "u.kelompok IN ($kel_joined)";
        $label_kelompok_tampil = implode(", ", $arr_target_kel);
        $kelompok_to_show = $arr_target_kel;
    }
} else {
    // JIKA LOKAL ADMIN: Hanya melihat kelompoknya sendiri!
    $filter_target_kel = "u.kelompok = '$kelompok_admin'";
    $kelompok_to_show = [$kelompok_admin];
    
    if ($is_event_gabungan) {
        $label_kelompok_tampil = "Gabungan (Fokus Data: $kelompok_admin)";
    } else {
        $label_kelompok_tampil = $kelompok_admin;
    }
}

$jenjang_to_show = [];
if ($target_jen == 'Semua') {
    $filter_target_jen = "b.jenjang != 'Caberawit'";
    $jenjang_to_show = ['Umum', 'Muda/i', 'Remaja', 'Pra Remaja'];
} else {
    $filter_target_jen = "b.jenjang = '$target_jen'";
    $jenjang_to_show = [$target_jen];
}

$base_stat = ['tepat'=>0, 'telat'=>0, 'online'=>0, 'izin'=>0, 'alpa'=>0];
$stat_matrix = [];

foreach($kelompok_to_show as $k) {
    $stat_matrix[$k] = [
        'TOTAL' => ['total'=>$base_stat, 'L'=>$base_stat, 'P'=>$base_stat]
    ];
    foreach($jenjang_to_show as $j) {
        $stat_matrix[$k][$j] = ['total'=>$base_stat, 'L'=>$base_stat, 'P'=>$base_stat];
    }
}

// 5. QUERY UTAMA
$q_jamaah = mysqli_query($conn, "
    SELECT u.id_user, u.kelompok, b.nama_lengkap, b.jenjang, b.jenis_kelamin,
           p.status_absen, p.tgl_presensi,
           z.jenis_izin, z.status_izin, z.status_konfirmasi
    FROM users u
    JOIN biodata_jamaah b ON u.id_user = b.id_user
    LEFT JOIN presensi p ON u.id_user = p.id_user AND p.id_kegiatan IN ($in_id_keg)
    LEFT JOIN perizinan z ON u.id_user = z.id_user AND z.id_kegiatan IN ($in_id_keg)
    WHERE u.level = 'karyawan' AND ($filter_target_kel) AND ($filter_target_jen)
    ORDER BY u.kelompok ASC, b.nama_lengkap ASC
");

$stat_hadir_offline = 0; $stat_hadir_online = 0; $stat_izin = 0; $stat_alpa = 0;
$data_tabel = [];

while ($row = mysqli_fetch_assoc($q_jamaah)) {
    $status_html = ""; $is_alpa = false; $kunci_stat = 'alpa'; 

    if (!empty($row['status_absen'])) {
        $st = strtolower($row['status_absen']);
        if ($st == 'tepat waktu') {
            $kunci_stat = 'tepat';
            $status_html = '<span class="badge bg-success w-100 p-2"><i class="fa fa-check me-1"></i> HADIR (Tepat)</span>';
            $stat_hadir_offline++;
        } elseif ($st == 'terlambat') {
            $kunci_stat = 'telat';
            $status_html = '<span class="badge bg-warning text-dark w-100 p-2"><i class="fa fa-clock me-1"></i> HADIR (Telat)</span>';
            $stat_hadir_offline++;
        } elseif ($st == 'hadir') {
            $kunci_stat = 'online';
            $status_html = '<span class="badge bg-primary w-100 p-2"><i class="fa fa-video me-1"></i> HADIR (Online)</span>';
            $stat_hadir_online++;
        } elseif ($st == 'izin') {
            $kunci_stat = 'izin';
            $status_html = '<span class="badge bg-secondary w-100 p-2"><i class="fa fa-envelope me-1"></i> IZIN ACC</span>';
            $stat_izin++;
        }
    } elseif (!empty($row['status_izin'])) {
        if ($row['status_izin'] == 'pending') {
            $status_html = '<span class="badge border border-warning text-warning w-100 p-2"><i class="fa fa-spinner fa-spin me-1"></i> Izin Diproses</span>';
            $is_alpa = true; 
        } elseif ($row['status_izin'] == 'ditolak') {
            $status_html = '<span class="badge bg-danger w-100 p-2"><i class="fa fa-times me-1"></i> Izin Ditolak (ALPA)</span>';
            $is_alpa = true;
        } elseif ($row['status_izin'] == 'disetujui') {
            if ($row['jenis_izin'] == 'Tidak Hadir') {
                $kunci_stat = 'izin';
                $status_html = '<span class="badge bg-secondary w-100 p-2"><i class="fa fa-envelope me-1"></i> IZIN ACC</span>';
                $stat_izin++;
            } else {
                $konf = strtolower($row['status_konfirmasi']);
                if (empty($konf) || $konf == 'belum') {
                    $status_html = '<span class="badge border border-info text-info w-100 p-2"><i class="fa fa-hourglass-half me-1"></i> Zoom (Belum ACC)</span>';
                    $is_alpa = true;
                } elseif ($konf == 'menunggu') {
                    $status_html = '<span class="badge bg-info text-dark w-100 p-2"><i class="fa fa-clock me-1"></i> Minta Sahkan Zoom</span>';
                    $is_alpa = true;
                } elseif ($konf == 'ditolak') {
                    $status_html = '<span class="badge bg-danger w-100 p-2"><i class="fa fa-times me-1"></i> Zoom Ditolak (ALPA)</span>';
                    $is_alpa = true;
                } elseif ($konf == 'disetujui') {
                    $kunci_stat = 'online';
                    $status_html = '<span class="badge bg-primary w-100 p-2"><i class="fa fa-video me-1"></i> HADIR (Online)</span>';
                    $stat_hadir_online++;
                }
            }
        }
    } else {
        $status_html = '<span class="badge bg-danger w-100 p-2"><i class="fa fa-times-circle me-1"></i> Tidak Hadir (Belum Absen)</span>';
        $is_alpa = true;
    }

    if($is_alpa) $stat_alpa++;

    $kel = $row['kelompok'];
    $jen = !empty($row['jenjang']) ? $row['jenjang'] : 'Umum';
    $gen = !empty($row['jenis_kelamin']) ? $row['jenis_kelamin'] : 'L';

    if(isset($stat_matrix[$kel])) {
        $stat_matrix[$kel]['TOTAL']['total'][$kunci_stat]++;
        $stat_matrix[$kel]['TOTAL'][$gen][$kunci_stat]++;
        
        if(isset($stat_matrix[$kel][$jen])) {
            $stat_matrix[$kel][$jen]['total'][$kunci_stat]++;
            $stat_matrix[$kel][$jen][$gen][$kunci_stat]++;
        }
    }

    $row['status_html'] = $status_html;
    $data_tabel[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel Pengajian | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-box { border-radius: 12px; padding: 15px; color: white; text-align: center; font-weight: bold; }
        .mini-grid { font-size: 0.8rem; border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6; background: #fff;}
        .mg-head { display: flex; text-align: center; background: #f8f9fa; font-weight: bold; border-bottom: 1px solid #dee2e6; }
        .mg-row { display: flex; text-align: center; border-bottom: 1px solid #f1f3f5; align-items: center; }
        .mg-row:last-child { border-bottom: none; }
        .mg-col { flex: 1; padding: 8px 5px; border-right: 1px solid #f1f3f5; }
        .mg-col:last-child { border-right: none; }
        .mg-col-label { flex: 1.5; padding: 8px 10px; text-align: left; font-weight: bold; border-right: 1px solid #f1f3f5; }
        .mg-head-primary { background: #e7f1ff; border-color: #b8daff; color: #084298; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 flex-wrap gap-2">
        <h2 class="fw-bold text-dark mb-0"><i class="fa fa-cogs text-primary me-2"></i>Ruang Kendali Pengajian</h2>
        <a href="riwayat_pengajian.php" class="btn btn-outline-dark fw-bold shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="card card-custom p-4 bg-white mb-4 shadow-sm border-start border-primary border-5">
        <div class="row align-items-center">
            <div class="col-md-7 mb-3 mb-md-0">
                <span class="badge bg-dark mb-2">TARGET: <?= $target_jen; ?></span>
                <span class="badge bg-secondary ms-1 mb-2">KELOMPOK: <?= $label_kelompok_tampil; ?></span>
                <h3 class="fw-bold mb-1"><?= strtoupper($kegiatan['judul_pengajian']); ?></h3>
                <p class="text-muted mb-0"><i class="fa fa-map-marker-alt text-danger me-2"></i><?= $kegiatan['tempat_pengajian']; ?></p>
                <small class="text-primary fw-bold"><i class="fa fa-calendar-alt me-1 mt-2"></i> Dibuat: <?= date('d M Y, H:i', strtotime($kegiatan['tgl_buat'])); ?></small>
            </div>
            
            <div class="col-md-5 border-start-md ps-md-4">
                <h6 class="fw-bold text-dark mb-1"><i class="fa fa-toggle-on me-1 text-primary"></i> SAKLAR SISTEM</h6>
                
                <?php if(!$can_switch && $kegiatan['is_selesai'] == 0): ?>
                    <div class="alert alert-warning fw-bold text-center p-2 shadow-sm border-warning text-dark mb-0" style="font-size: 0.85rem;">
                        <i class="fa fa-lock me-1"></i> Karena ini acara Gabungan, saklar hanya bisa dikontrol oleh Admin Desa. Anda hanya berhak memantau jamaah kelompok Anda.
                    </div>
                <?php elseif($kegiatan['is_selesai'] == 1): ?>
                    <div class="alert alert-danger fw-bold text-center p-2 shadow-sm border-danger">
                        <i class="fa fa-lock me-1"></i> PENGAJIAN TELAH SELESAI & DITUTUP
                    </div>
                <?php else: ?>
                    <div class="small fw-bold text-info mb-3"><i class="fa fa-sync-alt me-1"></i> Tersinkron ke Semua Kelompok Terkait</div>
                    <div class="d-flex gap-2 mb-2">
                        <?php if($kegiatan['status_buka'] == 0): ?>
                            <a href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=buka_absen" onclick="return confirm('Buka pintu absen sekarang? Timer 10 menit akan berjalan serentak di HP seluruh jamaah.')" class="btn btn-success w-100 fw-bold shadow-sm"><i class="fa fa-door-open me-1"></i> BUKA ABSEN GPS</a>
                        <?php else: ?>
                            <a href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=tutup_absen" class="btn btn-danger w-100 fw-bold shadow-sm"><i class="fa fa-door-closed me-1"></i> TUTUP ABSEN GPS</a>
                        <?php endif; ?>

                        <?php if($kegiatan['status_izin'] == 0): ?>
                            <a href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=buka_izin" class="btn btn-outline-warning w-100 fw-bold shadow-sm text-dark"><i class="fa fa-envelope-open me-1"></i> BUKA FORM IZIN</a>
                        <?php else: ?>
                            <a href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=tutup_izin" class="btn btn-warning w-100 fw-bold shadow-sm text-dark"><i class="fa fa-envelope me-1"></i> TUTUP FORM IZIN</a>
                        <?php endif; ?>
                    </div>
                    <a href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=selesai" onclick="return confirm('YAKIN INGIN MENGAKHIRI PENGAJIAN?\nSemua absen dan form izin akan dikunci permanen untuk jadwal ini di seluruh kelompok.')" class="btn btn-dark w-100 fw-bold shadow-sm mt-1"><i class="fa fa-flag-checkered me-1"></i> AKHIRI PENGAJIAN INI</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat-box bg-success shadow-sm"><div class="small fw-normal mb-1">Hadir (Offline)</div><h3 class="mb-0"><?= $stat_hadir_offline; ?></h3></div></div>
        <div class="col-6 col-md-3"><div class="stat-box bg-primary shadow-sm"><div class="small fw-normal mb-1">Hadir (Online)</div><h3 class="mb-0"><?= $stat_hadir_online; ?></h3></div></div>
        <div class="col-6 col-md-3"><div class="stat-box bg-secondary shadow-sm"><div class="small fw-normal mb-1">Izin (Di-ACC)</div><h3 class="mb-0"><?= $stat_izin; ?></h3></div></div>
        <div class="col-6 col-md-3"><div class="stat-box bg-danger shadow-sm"><div class="small fw-normal mb-1">Tidak Hadir / Belum Absen</div><h3 class="mb-0"><?= $stat_alpa; ?></h3></div></div>
    </div>

    <div class="card card-custom p-4 bg-white shadow-sm border-top border-info border-4 mb-4">
        <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-sitemap me-2 text-info"></i>Rincian Kehadiran <?= $is_pusat ? 'per Kelompok & Jenjang' : 'Kelompok Anda'; ?></h5>
        
        <?php foreach($kelompok_to_show as $k): ?>
            <div class="mb-5 pb-3 border-bottom border-secondary">
                <?php if($is_pusat): ?>
                    <h5 class="fw-bold text-primary mb-3"><i class="fa fa-map-marker-alt text-danger me-2"></i>Kelompok <?= strtoupper($k); ?></h5>
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-12 col-xl-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded h-100 border border-primary border-opacity-25 shadow-sm">
                            <span class="fw-bold text-primary fs-6 d-block mb-3 text-center">TOTAL KESELURUHAN</span>
                            <?php $st_all = $stat_matrix[$k]['TOTAL']; ?>
                            <div class="mini-grid shadow-sm border-primary border-opacity-50">
                                <div class="mg-head mg-head-primary">
                                    <div class="mg-col-label text-center">STATUS</div>
                                    <div class="mg-col fw-bold">JML</div>
                                    <div class="mg-col"><i class="fa fa-male"></i> L</div>
                                    <div class="mg-col"><i class="fa fa-female"></i> P</div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-success"><i class="fa fa-check-circle me-1"></i> Tepat</div>
                                    <div class="mg-col fw-bold text-success"><?= $st_all['total']['tepat']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['L']['tepat']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['P']['tepat']; ?></div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-warning text-dark"><i class="fa fa-clock me-1"></i> Telat</div>
                                    <div class="mg-col fw-bold text-warning text-dark"><?= $st_all['total']['telat']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['L']['telat']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['P']['telat']; ?></div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-primary"><i class="fa fa-video me-1"></i> Zoom</div>
                                    <div class="mg-col fw-bold text-primary"><?= $st_all['total']['online']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['L']['online']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['P']['online']; ?></div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-secondary"><i class="fa fa-envelope me-1"></i> Izin</div>
                                    <div class="mg-col fw-bold text-secondary"><?= $st_all['total']['izin']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['L']['izin']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['P']['izin']; ?></div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-danger"><i class="fa fa-times-circle me-1"></i> Tidak Hadir</div>
                                    <div class="mg-col fw-bold text-danger"><?= $st_all['total']['alpa']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['L']['alpa']; ?></div>
                                    <div class="mg-col text-dark"><?= $st_all['P']['alpa']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php foreach($jenjang_to_show as $j): $st_j = $stat_matrix[$k][$j]; ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="bg-light p-3 rounded h-100 border shadow-sm">
                                <span class="fw-bold text-dark fs-6 d-block mb-3 text-center">JENJANG <?= strtoupper($j); ?></span>
                                <div class="mini-grid shadow-sm">
                                    <div class="mg-head">
                                        <div class="mg-col-label text-center text-muted">STATUS</div>
                                        <div class="mg-col text-dark">JML</div>
                                        <div class="mg-col text-info"><i class="fa fa-male"></i> L</div>
                                        <div class="mg-col text-danger"><i class="fa fa-female"></i> P</div>
                                    </div>
                                    <div class="mg-row">
                                        <div class="mg-col-label text-success"><i class="fa fa-check-circle me-1"></i> Tepat</div>
                                        <div class="mg-col fw-bold text-success"><?= $st_j['total']['tepat']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['L']['tepat']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['P']['tepat']; ?></div>
                                    </div>
                                    <div class="mg-row">
                                        <div class="mg-col-label text-warning text-dark"><i class="fa fa-clock me-1"></i> Telat</div>
                                        <div class="mg-col fw-bold text-warning text-dark"><?= $st_j['total']['telat']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['L']['telat']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['P']['telat']; ?></div>
                                    </div>
                                    <div class="mg-row">
                                        <div class="mg-col-label text-primary"><i class="fa fa-video me-1"></i> Zoom</div>
                                        <div class="mg-col fw-bold text-primary"><?= $st_j['total']['online']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['L']['online']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['P']['online']; ?></div>
                                    </div>
                                    <div class="mg-row">
                                        <div class="mg-col-label text-secondary"><i class="fa fa-envelope me-1"></i> Izin</div>
                                        <div class="mg-col fw-bold text-secondary"><?= $st_j['total']['izin']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['L']['izin']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['P']['izin']; ?></div>
                                    </div>
                                    <div class="mg-row">
                                        <div class="mg-col-label text-danger"><i class="fa fa-times-circle me-1"></i> Tidak Hadir</div>
                                        <div class="mg-col fw-bold text-danger"><?= $st_j['total']['alpa']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['L']['alpa']; ?></div>
                                        <div class="mg-col text-dark"><?= $st_j['P']['alpa']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card card-custom p-4 bg-white shadow-sm border-top border-dark border-4">
        <h5 class="fw-bold mb-3 text-dark"><i class="fa fa-users me-2 text-secondary"></i>Daftar Absensi Jamaah (<?= count($data_tabel); ?> Orang)</h5>
        
        <div class="table-responsive">
            <table id="tabelPemantauan" class="table table-hover align-middle text-center" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>NO</th>
                        <th class="text-start">NAMA JAMAAH</th>
                        <th>L/P</th>
                        <th>KELOMPOK</th>
                        <th>JENJANG</th>
                        <th>STATUS SAAT INI</th>
                        <th>AKSI MANUAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach($data_tabel as $row): ?>
                    <tr>
                        <td class="fw-bold"><?= $no++; ?></td>
                        <td class="text-start">
                            <span class="fw-bold text-primary"><?= strtoupper($row['nama_lengkap']); ?></span><br>
                            <small class="text-muted fw-bold"><i class="fa fa-clock me-1"></i> <?= !empty($row['tgl_presensi']) ? date('H:i', strtotime($row['tgl_presensi'])) : '-'; ?></small>
                        </td>
                        <td><span class="badge <?= ($row['jenis_kelamin']=='L') ? 'bg-info text-dark' : 'bg-danger'; ?> rounded-circle p-2"><?= $row['jenis_kelamin']; ?></span></td>
                        <td><span class="badge bg-light text-dark border"><?= $row['kelompok']; ?></span></td>
                        <td><?= $row['jenjang'] ?: '-'; ?></td>
                        <td style="width: 180px;">
                            <?= $row['status_html']; ?>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-dark fw-bold dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" <?= ($kegiatan['is_selesai'] == 1 || !$can_edit) ? 'disabled' : ''; ?>>
                                    <i class="fa fa-edit"></i> Set
                                </button>
                                <ul class="dropdown-menu shadow-sm">
                                    <li><a class="dropdown-item text-success fw-bold" href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=manual_absen&u=<?= $row['id_user']; ?>&st=hadir"><i class="fa fa-check-circle me-2"></i> Hadir Tepat</a></li>
                                    <li><a class="dropdown-item text-warning text-dark fw-bold" href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=manual_absen&u=<?= $row['id_user']; ?>&st=telat"><i class="fa fa-clock me-2"></i> Hadir Telat</a></li>
                                    <li><a class="dropdown-item text-secondary fw-bold" href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=manual_absen&u=<?= $row['id_user']; ?>&st=izin"><i class="fa fa-envelope me-2"></i> Izin Sah</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger fw-bold" href="?id_kegiatan=<?= $id_kegiatan_url; ?>&aksi=manual_absen&u=<?= $row['id_user']; ?>&st=alpa"><i class="fa fa-times-circle me-2"></i> Tidak Hadir / Reset</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tabelPemantauan').DataTable({ 
        "language": { "search": "Cari Nama/Status:", "lengthMenu": "Tampilkan _MENU_ baris" },
        "pageLength": 25
    });
});
</script>
</body>
</html>