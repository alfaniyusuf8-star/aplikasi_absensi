<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// 1. PROTEKSI HAK AKSES (Telah ditambahkan admin_mudai_desa & ketua_mudai_desa)
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$level = $_SESSION['level'];
$kelompok = $_SESSION['kelompok'];
$bulan_ini = date('m');
$tahun_ini = date('Y');

// 2. LOGIKA FILTER WILAYAH & JENJANG BERBASIS HIERARKI
// Tingkat Desa vs Tingkat Kelompok
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'ketua_mudai_desa', 'admin_mudai_desa']);
if ($is_pusat || $kelompok == 'Semua' || empty($kelompok)) {
    $filter_wilayah = "1";
    $grup_tampil = ['Semampir', 'Keputih', 'Praja'];
} else {
    $filter_wilayah = "u.kelompok = '$kelompok'";
    $grup_tampil = [$kelompok];
}

// Hak Akses Tampilan Jenjang (Diperbarui untuk MM Desa)
$show_umum = in_array($level, ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa']);
$show_mudai = ($show_umum || in_array($level, ['ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa']));
$show_remaja = ($show_umum || $level == 'admin_remaja');
$show_praremaja = ($show_umum || $level == 'admin_praremaja');
$show_caberawit = ($show_umum || $level == 'admin_caberawit');

// Daftar jenjang yang diizinkan untuk dieksekusi dalam perulangan perhitungan
$allowed_jenjang_hitung = [];
if($show_umum) $allowed_jenjang_hitung[] = 'Umum';
if($show_mudai) $allowed_jenjang_hitung[] = 'Muda/i';
if($show_remaja) $allowed_jenjang_hitung[] = 'Remaja';
if($show_praremaja) $allowed_jenjang_hitung[] = 'Pra Remaja';
if($show_caberawit) $allowed_jenjang_hitung[] = 'Caberawit';

// Pembentukan string SQL untuk filter jenjang
if (!$show_umum) {
    if (count($allowed_jenjang_hitung) > 0) {
        $jen_sql_arr = [];
        foreach($allowed_jenjang_hitung as $j) {
            $jen_sql_arr[] = "b.jenjang = '$j'";
        }
        // Jika akun baru kosong, masukkan ke perhitungan agar tidak hilang (optional fallback)
        $jen_sql_arr[] = "b.jenjang IS NULL OR b.jenjang = ''"; 
        $filter_jenjang_sql = "(" . implode(' OR ', $jen_sql_arr) . ")";
    } else {
        $filter_jenjang_sql = "1=0"; // Fallback jika tidak ada akses
    }
} else {
    $filter_jenjang_sql = "1";
}

// 3. PERSIAPAN WADAH GRAFIK 6 BULAN
$start_date = date('Y-m-01', strtotime("-5 month"));
$grafik_keys = [];
$grafik_label = [];
$nama_bulan_indo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

for ($i = 5; $i >= 0; $i--) {
    $time = strtotime("-$i month");
    $b = date('m', $time);
    $t = date('Y', $time);
    $grafik_keys[] = "$t-$b"; 
    $grafik_label[] = $nama_bulan_indo[(int)$b - 1] . ' ' . date('y', $time);
}

$chart_data = ['Grand Total' => []];
foreach(['Semampir', 'Keputih', 'Praja'] as $k) { $chart_data[$k] = []; }
foreach($chart_data as $k => $v) {
    foreach($grafik_keys as $gk) {
        $chart_data[$k][$gk] = ['target'=>0, 'hadir'=>0];
    }
}

// 4. TARIK SEMUA DATA MENTAH 6 BULAN TERAKHIR (Dengan Filter!)
$kegiatan_6m = [];
// Filter Kegiatan: Hanya ambil kegiatan yang target jenjangnya relevan dengan Admin yang sedang login
$q_keg_6m = mysqli_query($conn, "SELECT id_kegiatan, target_kelompok, target_jenjang, tgl_buat, is_selesai, DATE_FORMAT(tgl_buat, '%Y-%m') as ym, MONTH(tgl_buat) as m, YEAR(tgl_buat) as y FROM kegiatan WHERE tgl_buat >= '$start_date'");
while($k = mysqli_fetch_assoc($q_keg_6m)) { 
    // Filter Jenjang Kegiatan
    $t_jen = $k['target_jenjang'];
    $valid_keg = false;
    if ($show_umum) { $valid_keg = true; }
    else if ($t_jen == 'Semua' || in_array($t_jen, $allowed_jenjang_hitung)) { $valid_keg = true; }
    
    // Filter Wilayah Kegiatan
    $t_kel = $k['target_kelompok'];
    $valid_wil = false;
    if ($is_pusat) { $valid_wil = true; }
    else if ($t_kel == 'Semua' || $t_kel == $kelompok) { $valid_wil = true; }
    
    if ($valid_keg && $valid_wil) {
        $kegiatan_6m[] = $k; 
    }
}

$pres_6m = [];
$q_pres_6m = mysqli_query($conn, "SELECT p.id_user, p.id_kegiatan, p.status_absen FROM presensi p JOIN users u ON p.id_user = u.id_user JOIN biodata_jamaah b ON u.id_user = b.id_user WHERE p.tgl_presensi >= '$start_date' AND p.status_absen IN ('tepat waktu', 'terlambat') AND ($filter_wilayah) AND ($filter_jenjang_sql)");
while($p = mysqli_fetch_assoc($q_pres_6m)) { $pres_6m[$p['id_user']][$p['id_kegiatan']] = strtolower($p['status_absen']); }

// PENYEDERHANAAN LOGIKA IZIN
$iz_6m = [];
$izin_6m = []; 
$q_iz_6m = mysqli_query($conn, "SELECT z.id_user, z.id_kegiatan, z.jenis_izin FROM perizinan z JOIN users u ON z.id_user = u.id_user JOIN biodata_jamaah b ON u.id_user = b.id_user WHERE z.tgl_pengajuan >= '$start_date' AND z.status_izin = 'disetujui' AND ($filter_wilayah) AND ($filter_jenjang_sql)");
while($z = mysqli_fetch_assoc($q_iz_6m)) { 
    if($z['jenis_izin'] == 'Online') {
        $iz_6m[$z['id_user']][$z['id_kegiatan']] = true; 
    } else {
        $izin_6m[$z['id_user']][$z['id_kegiatan']] = true; 
    }
}

$jml_keg = [];
foreach(['Semampir', 'Keputih', 'Praja'] as $k) {
    $jml_keg[$k] = ['Umum'=>0, 'Muda/i'=>0, 'Remaja'=>0, 'Pra Remaja'=>0, 'Caberawit'=>0];
}
$jml_keg_desa = ['Umum'=>0, 'Muda/i'=>0, 'Remaja'=>0, 'Pra Remaja'=>0, 'Caberawit'=>0];
$keg_desa_tracked = []; 

foreach($kegiatan_6m as $k) {
    if ($k['m'] == $bulan_ini && $k['y'] == $tahun_ini) {
        $t_kel = $k['target_kelompok'];
        $t_jen = $k['target_jenjang'];
        $kels = ($t_kel == 'Semua') ? ['Semampir', 'Keputih', 'Praja'] : [$t_kel];
        
        $jens = [];
        if ($t_jen == 'Semua' || $t_jen == 'Umum') { $jens = ['Umum', 'Muda/i', 'Remaja', 'Pra Remaja']; } 
        elseif ($t_jen == 'Caberawit') { $jens = ['Caberawit']; } 
        else { $jens = [$t_jen]; }

        // Batasi $jens hanya pada yang allowed
        $jens_filtered = array_intersect($jens, $allowed_jenjang_hitung);

        foreach($kels as $kel) {
            // Hanya hitung kelompok yang relevan dengan admin
            if (in_array($kel, $grup_tampil)) {
                foreach($jens_filtered as $jen) {
                    if(isset($jml_keg[$kel][$jen])) { $jml_keg[$kel][$jen]++; }
                }
            }
        }

        foreach($jens_filtered as $jen) {
            if(!isset($keg_desa_tracked[$k['id_kegiatan']][$jen])) {
                $jml_keg_desa[$jen]++;
                $keg_desa_tracked[$k['id_kegiatan']][$jen] = true;
            }
        }
    }
}

// 5. STRUKTUR ARRAY PENAMPUNG REKAP ABSENSI
$struktur_jenjang = [
    'target' => 0, 'hadir' => 0, 'tepat' => 0, 'telat' => 0, 'online' => 0, 'izin' => 0, 'alpa' => 0,
    'L' => ['target'=>0, 'hadir'=>0, 'tepat'=>0, 'telat'=>0, 'online'=>0, 'izin'=>0, 'alpa'=>0], 
    'P' => ['target'=>0, 'hadir'=>0, 'tepat'=>0, 'telat'=>0, 'online'=>0, 'izin'=>0, 'alpa'=>0]
];
$struktur_absensi = [
    'target' => 0, 'hadir' => 0, 'tepat' => 0, 'telat' => 0, 'online' => 0, 'izin' => 0, 'alpa' => 0,
    'L' => ['target'=>0, 'hadir'=>0, 'tepat'=>0, 'telat'=>0, 'online'=>0, 'izin'=>0, 'alpa'=>0], 
    'P' => ['target'=>0, 'hadir'=>0, 'tepat'=>0, 'telat'=>0, 'online'=>0, 'izin'=>0, 'alpa'=>0],
    'Umum' => $struktur_jenjang, 'Muda/i' => $struktur_jenjang,
    'Remaja' => $struktur_jenjang, 'Pra Remaja' => $struktur_jenjang, 'Caberawit' => $struktur_jenjang
];

$data_rekap = [];
foreach(['Semampir', 'Keputih', 'Praja'] as $k) { $data_rekap[$k] = $struktur_absensi; }
$grand_total = $struktur_absensi;

// 6. KALKULASI SILANG PER JAMAAH (DENGAN FILTER)
$has_created = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'created_at'")) > 0;
$col_created = $has_created ? "u.created_at," : "'2000-01-01 00:00:00' as created_at,";

$q_users = mysqli_query($conn, "SELECT $col_created u.id_user, u.kelompok, b.jenjang, b.jenis_kelamin FROM users u JOIN biodata_jamaah b ON u.id_user = b.id_user WHERE u.level = 'karyawan' AND ($filter_wilayah) AND ($filter_jenjang_sql)");

while($u = mysqli_fetch_assoc($q_users)) {
    $uid = $u['id_user'];
    $kel = $u['kelompok'];
    // Fallback jika jenjang DB kosong
    if (empty($u['jenjang'])) {
        $jen = (count($allowed_jenjang_hitung) == 1) ? $allowed_jenjang_hitung[0] : 'Umum';
    } else {
        $jen = $u['jenjang'];
    }
    
    // Pastikan jenjang yang diproses sesuai dengan hak akses (meskipun filter SQL harusnya sudah jalan)
    if (!in_array($jen, $allowed_jenjang_hitung)) { continue; }
    
    $gen = !empty($u['jenis_kelamin']) ? $u['jenis_kelamin'] : 'L';
    $tgl_daftar_date = date('Y-m-d', strtotime($u['created_at'] ?? '2000-01-01'));

    foreach($kegiatan_6m as $k) {
        $keg_date = date('Y-m-d', strtotime($k['tgl_buat']));
        if ($keg_date < $tgl_daftar_date) { continue; }

        $id_k = $k['id_kegiatan'];
        $m_key = $k['ym']; 
        $is_bulan_ini = ($k['m'] == $bulan_ini && $k['y'] == $tahun_ini);

        $match_kel = ($k['target_kelompok'] == 'Semua' || $k['target_kelompok'] == $kel);
        $match_jen = ($jen == 'Caberawit') ? ($k['target_jenjang'] == 'Caberawit') : ($k['target_jenjang'] == 'Semua' || $k['target_jenjang'] == 'Umum' || $k['target_jenjang'] == $jen);

        if($match_kel && $match_jen) {
            $is_hadir = false; $is_tepat = false; $is_telat = false; 
            $is_online = false; $is_izin = false; $is_alpa = false;

            if(isset($pres_6m[$uid][$id_k])) {
                $is_hadir = true;
                if($pres_6m[$uid][$id_k] == 'tepat waktu') $is_tepat = true;
                if($pres_6m[$uid][$id_k] == 'terlambat') $is_telat = true;
            } elseif(isset($iz_6m[$uid][$id_k])) {
                $is_hadir = true; $is_online = true;
            } elseif(isset($izin_6m[$uid][$id_k])) {
                $is_izin = true;
            } else {
                if ($k['is_selesai'] == 1) { $is_alpa = true; }
            }

            if (!$is_hadir && !$is_izin && !$is_alpa) { continue; }

            // Update Grafik Chart Data (Khusus yang relevan)
            if(isset($chart_data[$kel][$m_key])) {
                $chart_data[$kel][$m_key]['target']++;
                $chart_data['Grand Total'][$m_key]['target']++;
                if($is_hadir) {
                    $chart_data[$kel][$m_key]['hadir']++;
                    $chart_data['Grand Total'][$m_key]['hadir']++;
                }
            }

            if($is_bulan_ini && isset($data_rekap[$kel])) {
                // Total Kelompok
                $data_rekap[$kel]['target']++;
                if($is_hadir) $data_rekap[$kel]['hadir']++;
                if($is_tepat) $data_rekap[$kel]['tepat']++;
                if($is_telat) $data_rekap[$kel]['telat']++;
                if($is_online) $data_rekap[$kel]['online']++;
                if($is_izin) $data_rekap[$kel]['izin']++;
                if($is_alpa) $data_rekap[$kel]['alpa']++;

                $data_rekap[$kel][$gen]['target']++;
                if($is_hadir) $data_rekap[$kel][$gen]['hadir']++;
                if($is_tepat) $data_rekap[$kel][$gen]['tepat']++;
                if($is_telat) $data_rekap[$kel][$gen]['telat']++;
                if($is_online) $data_rekap[$kel][$gen]['online']++;
                if($is_izin) $data_rekap[$kel][$gen]['izin']++;
                if($is_alpa) $data_rekap[$kel][$gen]['alpa']++;

                // Per Jenjang di Kelompok
                if(isset($data_rekap[$kel][$jen])) {
                    $data_rekap[$kel][$jen]['target']++;
                    if($is_hadir) $data_rekap[$kel][$jen]['hadir']++;
                    if($is_tepat) $data_rekap[$kel][$jen]['tepat']++;
                    if($is_telat) $data_rekap[$kel][$jen]['telat']++;
                    if($is_online) $data_rekap[$kel][$jen]['online']++;
                    if($is_izin) $data_rekap[$kel][$jen]['izin']++;
                    if($is_alpa) $data_rekap[$kel][$jen]['alpa']++;

                    $data_rekap[$kel][$jen][$gen]['target']++;
                    if($is_hadir) $data_rekap[$kel][$jen][$gen]['hadir']++;
                    if($is_tepat) $data_rekap[$kel][$jen][$gen]['tepat']++;
                    if($is_telat) $data_rekap[$kel][$jen][$gen]['telat']++;
                    if($is_online) $data_rekap[$kel][$jen][$gen]['online']++;
                    if($is_izin) $data_rekap[$kel][$jen][$gen]['izin']++;
                    if($is_alpa) $data_rekap[$kel][$jen][$gen]['alpa']++;
                }

                // Total Desa (Grand Total)
                $grand_total['target']++;
                if($is_hadir) $grand_total['hadir']++;
                if($is_tepat) $grand_total['tepat']++;
                if($is_telat) $grand_total['telat']++;
                if($is_online) $grand_total['online']++;
                if($is_izin) $grand_total['izin']++;
                if($is_alpa) $grand_total['alpa']++;

                $grand_total[$gen]['target']++;
                if($is_hadir) $grand_total[$gen]['hadir']++;
                if($is_tepat) $grand_total[$gen]['tepat']++;
                if($is_telat) $grand_total[$gen]['telat']++;
                if($is_online) $grand_total[$gen]['online']++;
                if($is_izin) $grand_total[$gen]['izin']++;
                if($is_alpa) $grand_total[$gen]['alpa']++;

                // Per Jenjang di Desa
                if(isset($grand_total[$jen])) {
                    $grand_total[$jen]['target']++;
                    if($is_hadir) $grand_total[$jen]['hadir']++;
                    if($is_tepat) $grand_total[$jen]['tepat']++;
                    if($is_telat) $grand_total[$jen]['telat']++;
                    if($is_online) $grand_total[$jen]['online']++;
                    if($is_izin) $grand_total[$jen]['izin']++;
                    if($is_alpa) $grand_total[$jen]['alpa']++;

                    $grand_total[$jen][$gen]['target']++;
                    if($is_hadir) $grand_total[$jen][$gen]['hadir']++;
                    if($is_tepat) $grand_total[$jen][$gen]['tepat']++;
                    if($is_telat) $grand_total[$jen][$gen]['telat']++;
                    if($is_online) $grand_total[$jen][$gen]['online']++;
                    if($is_izin) $grand_total[$jen][$gen]['izin']++;
                    if($is_alpa) $grand_total[$jen][$gen]['alpa']++;
                }
            }
        }
    }
}

$js_chart_data = [];
foreach($chart_data as $k => $months) {
    $js_chart_data[$k] = [];
    foreach($grafik_keys as $gk) {
        $t = $months[$gk]['target'];
        $h = $months[$gk]['hadir'];
        $p = ($t > 0) ? round(($h / $t) * 100) : 0;
        $js_chart_data[$k][] = ($p > 100) ? 100 : $p;
    }
}

function calcP($h, $t) {
    if($t == 0) return 0;
    $p = round(($h / $t) * 100, 1);
    return $p > 100 ? 100 : $p;
}
function colorP($p) {
    if($p >= 80) return 'bg-success';
    if($p >= 50) return 'bg-warning text-dark';
    return 'bg-danger';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rekap Absensi | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; font-size: 0.85rem; }
        .main-content { margin-left: 250px; padding: 20px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); transition: 0.3s; }
        
        .stat-item { padding: 10px 0; border-bottom: 1px dashed #dee2e6; }
        .stat-item:last-child { border-bottom: none; }
        .progress-slim { height: 6px; border-radius: 10px; margin-top: 4px; }
        
        .nav-pills-scroll {
            display: flex; flex-wrap: nowrap; overflow-x: auto; 
            padding: 5px 0 10px 0; scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch; border-bottom: 1px solid transparent;
        }
        .nav-pills-scroll::-webkit-scrollbar { display: none; } 
        .nav-pills-scroll .nav-link {
            white-space: nowrap; border-radius: 50px; padding: 6px 15px; 
            font-size: 0.8rem; font-weight: bold; color: #1a535c; background: #fff; margin-right: 8px;
            border: 1px solid #cbd5e1; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .nav-pills-scroll .nav-link.active {
            background-color: #1a535c; color: #fff; border-color: #1a535c; 
            box-shadow: 0 4px 8px rgba(26,83,92,0.2);
        }

        .mini-grid { font-size: 0.7rem; border-radius: 8px; overflow: hidden; border: 1px solid #dee2e6; margin-top: 8px; background: #fff;}
        .mg-head { display: flex; text-align: center; background: #f8f9fa; font-weight: bold; border-bottom: 1px solid #dee2e6; }
        .mg-row { display: flex; text-align: center; border-bottom: 1px solid #f1f3f5; align-items: center; }
        .mg-row:last-child { border-bottom: none; }
        .mg-col { flex: 1; padding: 5px 3px; border-right: 1px solid #f1f3f5; }
        .mg-col:last-child { border-right: none; }
        .mg-col-label { flex: 1.5; padding: 5px 8px; text-align: left; font-weight: bold; border-right: 1px solid #f1f3f5; }
        
        .mini-grid-dark { border-color: #495057; background: #212529; }
        .mini-grid-dark .mg-head { background: #343a40; border-color: #495057; color: #adb5bd; }
        .mini-grid-dark .mg-row { border-color: #495057; }
        .mini-grid-dark .mg-col, .mini-grid-dark .mg-col-label { border-color: #495057; color: #f8f9fa; }

        @media (max-width: 768px) { 
            .main-content { margin-left: 0; padding: 10px; padding-bottom: 80px;} 
            .nav-pills-scroll { margin-left: -5px; margin-right: -10px; padding-left: 5px; padding-right: 10px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0"><i class="fa fa-chart-line text-primary me-2"></i>Rekap Absensi</h5>
            <small class="text-muted" style="font-size:0.75rem;">Bulan <?= date('F Y'); ?> | Mode: Ranah Anda</small>
        </div>
        <a href="dashboard_keimaman.php" class="btn btn-sm btn-outline-dark fw-bold shadow-sm" style="font-size:0.75rem;"><i class="fa fa-arrow-left"></i></a>
    </div>

    <?php if($is_pusat): $g_p = calcP($grand_total['hadir'], $grand_total['target']); ?>
    <div class="card p-3 bg-dark text-white shadow-sm border-0 mb-3" style="border-radius: 12px;">
        <h6 class="fw-bold mb-3 text-center"><i class="fa fa-globe text-warning me-1"></i> GRAND TOTAL (SE-DESA)</h6>
        
        <div class="text-center mb-3 pb-3 border-bottom border-secondary">
            <h1 class="fw-bold text-warning mb-0" style="font-size: 3rem; line-height:1;"><?= $g_p; ?>%</h1>
            <div class="progress progress-slim mt-2 mx-auto bg-secondary" style="max-width: 300px;">
                <div class="progress-bar <?= colorP($g_p); ?>" style="width: <?= $g_p; ?>%;"></div>
            </div>
            <small class="text-white-50 d-block mt-2">Berdasarkan Total Target Absen Anda</small>
        </div>

        <h6 class="fw-bold text-warning mb-2" style="font-size:0.8rem;"><i class="fa fa-map-marker-alt me-1"></i>1. Rincian Kelompok (Grafik)</h6>
        <div class="row g-2 mb-3 border-bottom border-secondary pb-3">
            <?php foreach(['Semampir', 'Keputih', 'Praja'] as $k): 
                $dt_k = $data_rekap[$k];
                $t_k = $dt_k['target'];
                $p_k = calcP($dt_k['hadir'], $t_k);
            ?>
            <div class="col-12 col-lg-4">
                <div class="bg-secondary bg-opacity-25 p-2 rounded h-100 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-white" style="font-size:0.8rem;"><?= strtoupper($k); ?></span>
                    <span class="fw-bold <?= colorP($p_k); ?> px-2 rounded text-white" style="font-size:0.8rem;"><?= $p_k; ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h6 class="fw-bold text-info mb-2" style="font-size:0.8rem;"><i class="fa fa-layer-group me-1"></i>2. Rincian Jenjang</h6>
        <div class="row g-2">
            <?php 
            foreach($allowed_jenjang_hitung as $j): 
                $t_j = $grand_total[$j]['target'];
                $t_j_l = $grand_total[$j]['L']['target'];
                $t_j_p = $grand_total[$j]['P']['target'];
                $j_p = calcP($grand_total[$j]['hadir'], $t_j);
            ?>
            <div class="<?= (count($allowed_jenjang_hitung) == 1) ? 'col-12' : 'col-12 col-md-6'; ?> mb-1">
                <div class="bg-secondary bg-opacity-25 p-2 rounded h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-info" style="font-size:0.8rem;">
                            <?= strtoupper($j); ?> 
                            <span class="badge bg-dark border border-secondary fw-normal ms-1 py-0" style="font-size:0.6rem;"><?= $jml_keg_desa[$j]; ?> Jdwl</span>
                        </span>
                        <span class="fw-bold <?= colorP($j_p); ?> px-2 rounded text-white" style="font-size:0.8rem;"><?= $j_p; ?>%</span>
                    </div>
                    
                    <div class="mini-grid mini-grid-dark shadow-sm mt-2">
                        <div class="mg-head">
                            <div class="mg-col-label">STATUS</div>
                            <div class="mg-col text-warning">TOT</div>
                            <div class="mg-col text-info">L</div>
                            <div class="mg-col text-danger">P</div>
                        </div>
                        <div class="mg-row">
                            <div class="mg-col-label text-success"><i class="fa fa-check-circle me-1"></i> Tepat</div>
                            <div class="mg-col fw-bold"><?= calcP($grand_total[$j]['tepat'], $t_j); ?>%</div>
                            <div class="mg-col"><?= calcP($grand_total[$j]['L']['tepat'], $t_j_l); ?>%</div>
                            <div class="mg-col"><?= calcP($grand_total[$j]['P']['tepat'], $t_j_p); ?>%</div>
                        </div>
                        <div class="mg-row">
                            <div class="mg-col-label text-warning"><i class="fa fa-clock me-1"></i> Telat</div>
                            <div class="mg-col fw-bold"><?= calcP($grand_total[$j]['telat'], $t_j); ?>%</div>
                            <div class="mg-col"><?= calcP($grand_total[$j]['L']['telat'], $t_j_l); ?>%</div>
                            <div class="mg-col"><?= calcP($grand_total[$j]['P']['telat'], $t_j_p); ?>%</div>
                        </div>
                        <div class="mg-row">
                            <div class="mg-col-label text-danger"><i class="fa fa-times-circle me-1"></i> Alpa</div>
                            <div class="mg-col fw-bold"><?= calcP($grand_total[$j]['alpa'], $t_j); ?>%</div>
                            <div class="mg-col"><?= calcP($grand_total[$j]['L']['alpa'], $t_j_l); ?>%</div>
                            <div class="mg-col"><?= calcP($grand_total[$j]['P']['alpa'], $t_j_p); ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3 pt-3 border-top border-secondary">
            <h6 class="text-white-50 fw-bold mb-2" style="font-size:0.75rem;"><i class="fa fa-chart-area me-1 text-warning"></i>Tren 6 Bulan Terakhir</h6>
            <div style="height: 130px; width: 100%; position: relative;">
                <canvas id="chartGrandTotal"></canvas>
            </div>
        </div>
    </div>
    
    <div class="nav nav-pills-scroll mb-3" id="pills-kelompok" role="tablist">
        <?php $first_k = true; foreach($grup_tampil as $k): ?>
            <button class="nav-link <?= $first_k ? 'active' : ''; ?>" id="pills-<?= md5($k); ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-<?= md5($k); ?>" type="button" role="tab">
                <i class="fa fa-map-marker-alt me-1 text-danger"></i> KEL. <?= strtoupper($k); ?>
            </button>
        <?php $first_k = false; endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="tab-content" id="pills-tabContent">
        <?php $first_k = true; foreach($grup_tampil as $grup): 
            $dt = $data_rekap[$grup]; 
            $kel_p = calcP($dt['hadir'], $dt['target']);
            $active_class = ($is_pusat && $first_k) || !$is_pusat ? 'show active' : '';
        ?>
        <div class="tab-pane fade <?= $active_class; ?>" id="pills-<?= md5($grup); ?>" role="tabpanel">
            
            <div class="card card-custom bg-white p-0 shadow-sm overflow-hidden border-top border-primary border-4 mx-auto" style="max-width: 900px;">
                <div class="bg-light p-2 text-center border-bottom">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.9rem;"><i class="fa fa-map-marker-alt text-danger me-1"></i>Kelompok <?= strtoupper($grup); ?></h6>
                </div>
                
                <div class="p-3">
                    <div class="text-center mb-3">
                        <h2 class="fw-bold <?= colorP($kel_p); ?> text-white d-inline-block px-4 py-2 rounded-4 shadow-sm mb-1" style="font-size: 2.5rem; line-height:1;"><?= $kel_p; ?>%</h2>
                        <span class="d-block text-muted fw-bold" style="font-size:0.75rem;">Rata-rata Kehadiran</span>
                    </div>

                    <div class="mb-3 border-bottom pb-3">
                        <div style="height: 120px; width: 100%; position: relative;">
                            <canvas id="chart_<?= md5($grup); ?>"></canvas>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mt-3" style="font-size:0.85rem;"><i class="fa fa-layer-group text-warning me-1"></i>Rincian per Jenjang</h6>
                    
                    <?php 
                    foreach($allowed_jenjang_hitung as $j):
                        $t_j = $dt[$j]['target'];
                        $t_j_l = $dt[$j]['L']['target'];
                        $t_j_p = $dt[$j]['P']['target'];
                        $j_p = calcP($dt[$j]['hadir'], $t_j);
                    ?>
                        <div class="stat-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-dark fw-bold" style="font-size:0.8rem;">
                                    <?= $j; ?> 
                                    <span class="badge bg-secondary ms-1 fw-normal py-0" style="font-size:0.6rem;"><?= $jml_keg[$grup][$j]; ?> Jdwl</span>
                                </span>
                                <span class="fw-bold <?= colorP($j_p); ?> px-2 text-white rounded" style="font-size:0.8rem;"><?= $j_p; ?>%</span>
                            </div>
                            <div class="progress progress-slim bg-light mb-2">
                                <div class="progress-bar <?= colorP($j_p); ?>" style="width: <?= $j_p; ?>%;"></div>
                            </div>
                            
                            <div class="mini-grid shadow-sm">
                                <div class="mg-head">
                                    <div class="mg-col-label text-muted">STATUS</div>
                                    <div class="mg-col text-dark">TOT</div>
                                    <div class="mg-col text-info">L</div>
                                    <div class="mg-col text-danger">P</div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-success"><i class="fa fa-check-circle me-1"></i> Tepat</div>
                                    <div class="mg-col fw-bold bg-success-subtle text-success-emphasis"><?= calcP($dt[$j]['tepat'], $t_j); ?>%</div>
                                    <div class="mg-col"><?= calcP($dt[$j]['L']['tepat'], $t_j_l); ?>%</div>
                                    <div class="mg-col"><?= calcP($dt[$j]['P']['tepat'], $t_j_p); ?>%</div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-warning text-dark"><i class="fa fa-clock me-1"></i> Telat</div>
                                    <div class="mg-col fw-bold bg-warning-subtle text-warning-emphasis"><?= calcP($dt[$j]['telat'], $t_j); ?>%</div>
                                    <div class="mg-col"><?= calcP($dt[$j]['L']['telat'], $t_j_l); ?>%</div>
                                    <div class="mg-col"><?= calcP($dt[$j]['P']['telat'], $t_j_p); ?>%</div>
                                </div>
                                <div class="mg-row">
                                    <div class="mg-col-label text-danger"><i class="fa fa-times-circle me-1"></i> Alpa</div>
                                    <div class="mg-col fw-bold bg-danger-subtle text-danger-emphasis"><?= calcP($dt[$j]['alpa'], $t_j); ?>%</div>
                                    <div class="mg-col"><?= calcP($dt[$j]['L']['alpa'], $t_j_l); ?>%</div>
                                    <div class="mg-col"><?= calcP($dt[$j]['P']['alpa'], $t_j_p); ?>%</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
            
        </div>
        <?php $first_k = false; endforeach; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labels = <?= json_encode($grafik_label); ?>;
const chartData = <?= json_encode($js_chart_data); ?>;
let myCharts = {};

function createChart(ctxId, dataPoints, isDark) {
    const canvas = document.getElementById(ctxId);
    if(!canvas) return; 

    const ctx = canvas.getContext('2d');
    const color = isDark ? '#ffc107' : '#1a535c';
    const bgColor = isDark ? 'rgba(255, 193, 7, 0.2)' : 'rgba(26, 83, 92, 0.2)';
    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
    const fontColor = isDark ? '#adb5bd' : '#6c757d';

    myCharts[ctxId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Rata-rata Kehadiran (%)',
                data: dataPoints,
                borderColor: color,
                backgroundColor: bgColor,
                borderWidth: 2,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointRadius: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: fontColor, font: {size: 10} }, grid: { color: gridColor, drawBorder: false } },
                y: { 
                    beginAtZero: true, 
                    max: 100, 
                    ticks: { color: fontColor, font: {size: 10}, callback: function(value) { return value + "%" } },
                    grid: { color: gridColor, drawBorder: false }
                }
            }
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    <?php if($is_pusat): ?>
        createChart('chartGrandTotal', chartData['Grand Total'], true);
    <?php endif; ?>

    <?php foreach($grup_tampil as $grup): ?>
        createChart('chart_<?= md5($grup); ?>', chartData['<?= $grup; ?>'], false);
    <?php endforeach; ?>
});

$('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
    for (var key in myCharts) {
        myCharts[key].resize();
    }
});
</script>

</body>
</html>