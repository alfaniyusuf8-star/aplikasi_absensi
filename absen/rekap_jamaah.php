<?php
session_start();
include 'koneksi.php';

// 1. PROTEKSI HAK AKSES (Termasuk MM Desa)
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$level = $_SESSION['level'];
$kelompok = $_SESSION['kelompok'];

// =========================================================================
// 2. LOGIKA FILTER WILAYAH (Tingkat Desa vs Tingkat Kelompok)
// =========================================================================
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'ketua_mudai_desa', 'admin_mudai_desa']);

if ($is_pusat || $kelompok == 'Semua' || empty($kelompok)) {
    $filter_wilayah = "1";
    $grup_tampil = ['Semampir', 'Keputih', 'Praja'];
} else {
    $filter_wilayah = "u.kelompok = '$kelompok'";
    $grup_tampil = [$kelompok];
}

// =========================================================================
// 3. LOGIKA FILTER JENJANG (Pengkondisian Angka yang Dihitung)
// =========================================================================
// Agar angka "Grand Total" tidak membocorkan jumlah bapak-bapak kepada admin remaja
if (in_array($level, ['ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa'])) {
    $filter_jenjang_sql = "(b.jenjang = 'Muda/i' OR b.jenjang IS NULL OR b.jenjang = '')";
    $allowed_jenjang_hitung = ['Muda/i']; 
} elseif ($level == 'admin_remaja') {
    $filter_jenjang_sql = "(b.jenjang = 'Remaja' OR b.jenjang IS NULL OR b.jenjang = '')";
    $allowed_jenjang_hitung = ['Remaja'];
} elseif ($level == 'admin_praremaja') {
    $filter_jenjang_sql = "(b.jenjang = 'Pra Remaja' OR b.jenjang IS NULL OR b.jenjang = '')";
    $allowed_jenjang_hitung = ['Pra Remaja'];
} elseif ($level == 'admin_caberawit') {
    $filter_jenjang_sql = "(b.jenjang = 'Caberawit' OR b.jenjang IS NULL OR b.jenjang = '')";
    $allowed_jenjang_hitung = ['Caberawit'];
} else {
    $filter_jenjang_sql = "1";
    $allowed_jenjang_hitung = ['Umum', 'Muda/i', 'Remaja', 'Pra Remaja', 'Caberawit']; 
}

// 4. SIAPKAN WADAH PERHITUNGAN DATA
$struktur_demografi = [
    'total' => 0, 
    'kk' => 0, 
    'L' => 0, 'P' => 0,
    'MT' => [
        'total' => 0, 'L' => 0, 'P' => 0,
        'jenjang' => [
            'Umum' => ['total'=>0, 'L'=>0, 'P'=>0],
            'Muda/i' => ['total'=>0, 'L'=>0, 'P'=>0],
            'Remaja' => ['total'=>0, 'L'=>0, 'P'=>0],
            'Pra Remaja' => ['total'=>0, 'L'=>0, 'P'=>0],
            'Caberawit' => ['total'=>0, 'L'=>0, 'P'=>0]
        ]
    ],
    'jenjang' => [
        'Umum' => ['total'=>0, 'L'=>0, 'P'=>0],
        'Muda/i' => ['total'=>0, 'L'=>0, 'P'=>0],
        'Remaja' => ['total'=>0, 'L'=>0, 'P'=>0],
        'Pra Remaja' => ['total'=>0, 'L'=>0, 'P'=>0],
        'Caberawit' => ['total'=>0, 'L'=>0, 'P'=>0]
    ]
];

$data_rekap = [];
foreach(['Semampir', 'Keputih', 'Praja'] as $k) { $data_rekap[$k] = $struktur_demografi; }
$grand_total = $struktur_demografi;

// 5. TARIK DAN HITUNG DATA JAMAAH BERDASARKAN FILTER
$query_users = "
    SELECT u.kelompok, b.jenjang, b.jenis_kelamin, b.status_mubaligh, b.status_keluarga 
    FROM users u 
    JOIN biodata_jamaah b ON u.id_user = b.id_user 
    WHERE u.level = 'karyawan' AND ($filter_wilayah) AND ($filter_jenjang_sql)
";
$q_users = mysqli_query($conn, $query_users);

while($u = mysqli_fetch_assoc($q_users)) {
    $kel = $u['kelompok'];
    // Jika jenjang kosong di DB, fallback ke Umum jika inti, atau sesuaikan dengan ranah adminnya.
    if(empty($u['jenjang'])) {
        $jen = (count($allowed_jenjang_hitung) == 1) ? $allowed_jenjang_hitung[0] : 'Umum';
    } else {
        $jen = $u['jenjang'];
    }
    
    $gen = !empty($u['jenis_kelamin']) ? $u['jenis_kelamin'] : 'L';
    $is_mt = ($u['status_mubaligh'] == 'MT');
    $is_kk = ($u['status_keluarga'] == 'Kepala Keluarga'); 

    if(isset($data_rekap[$kel])) {
        // Tambah ke Total Kelompok
        $data_rekap[$kel]['total']++;
        if($is_kk) $data_rekap[$kel]['kk']++; 
        $data_rekap[$kel][$gen]++;
        
        if(isset($data_rekap[$kel]['jenjang'][$jen])) {
            $data_rekap[$kel]['jenjang'][$jen]['total']++;
            $data_rekap[$kel]['jenjang'][$jen][$gen]++;
        }

        // Tambah ke Rincian MT Kelompok
        if($is_mt) {
            $data_rekap[$kel]['MT']['total']++;
            $data_rekap[$kel]['MT'][$gen]++;
            if(isset($data_rekap[$kel]['MT']['jenjang'][$jen])) {
                $data_rekap[$kel]['MT']['jenjang'][$jen]['total']++;
                $data_rekap[$kel]['MT']['jenjang'][$jen][$gen]++;
            }
        }
    }

    // Tambah ke Grand Total Desa
    $grand_total['total']++;
    if($is_kk) $grand_total['kk']++; 
    $grand_total[$gen]++;

    if(isset($grand_total['jenjang'][$jen])) {
        $grand_total['jenjang'][$jen]['total']++;
        $grand_total['jenjang'][$jen][$gen]++;
    }

    // Tambah ke Rincian MT Desa
    if($is_mt) {
        $grand_total['MT']['total']++;
        $grand_total['MT'][$gen]++;
        if(isset($grand_total['MT']['jenjang'][$jen])) {
            $grand_total['MT']['jenjang'][$jen]['total']++;
            $grand_total['MT']['jenjang'][$jen][$gen]++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rekap Demografi Jamaah | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .card-custom:hover { transform: translateY(-5px); }
        .gender-badge { font-size: 0.8rem; padding: 5px 12px; border-radius: 8px; font-weight: bold; }
        
        .box-jenjang { background: rgba(255,255,255,0.1); border-radius: 10px; padding: 15px; border: 1px solid rgba(255,255,255,0.15); transition: 0.3s; }
        .box-jenjang:hover { background: rgba(255,255,255,0.2); }
        
        .box-jenjang-light { background: #f8f9fa; border-radius: 10px; padding: 15px; border: 1px solid #dee2e6; transition: 0.3s; }
        .box-jenjang-light:hover { background: #e9ecef; border-color: #ced4da; }

        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; padding-bottom: 90px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa fa-users text-primary me-2"></i>Rekap Demografi</h2>
            <small class="text-muted fw-bold">Statistik Jamaah Ranah Anda</small>
        </div>
        <a href="dashboard_keimaman.php" class="btn btn-outline-dark fw-bold shadow-sm rounded-pill px-4"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <?php if($is_pusat): ?>
    <div class="card p-4 bg-dark text-white shadow-lg border-0 mb-5" style="border-radius: 20px;">
        <div class="d-flex align-items-center justify-content-between border-bottom border-secondary pb-3 mb-4">
            <h4 class="fw-bold mb-0 text-white"><i class="fa fa-globe me-2 text-warning"></i>JUMLAH JAMAAH GRAND TOTAL (SE-DESA)</h4>
        </div>
        
        <div class="row align-items-center mb-2">
            <div class="col-md-4 text-center border-end border-secondary mb-4 mb-md-0">
                <h6 class="text-white-50 fw-bold mb-1">TOTAL TERDAFTAR</h6>
                <h1 class="fw-bold text-white mb-1" style="font-size: 4rem;"><?= $grand_total['total']; ?></h1>
                
                <?php if(in_array('Umum', $allowed_jenjang_hitung)): ?>
                    <h5 class="text-warning fw-bold mb-3"><i class="fa fa-sitemap me-1"></i> <?= $grand_total['kk']; ?> Kepala Keluarga (KK)</h5>
                <?php endif; ?>
                
                <div class="d-flex justify-content-center gap-2">
                    <span class="gender-badge border border-info text-info"><i class="fa fa-male me-1"></i> Laki-laki: <?= $grand_total['L']; ?></span>
                    <span class="gender-badge border border-danger text-danger"><i class="fa fa-female me-1"></i> Perempuan: <?= $grand_total['P']; ?></span>
                </div>
            </div>
            
            <div class="col-md-8">
                <h6 class="text-white-50 fw-bold mb-3 ps-2"><i class="fa fa-layer-group me-2"></i>Rincian Jumlah per Jenjang (Se-Desa)</h6>
                <div class="row g-3">
                    <?php foreach($allowed_jenjang_hitung as $j): ?>
                    <div class="<?= (count($allowed_jenjang_hitung) == 1) ? 'col-12' : 'col-6 col-md-4'; ?>">
                        <div class="box-jenjang text-center h-100 d-flex flex-column justify-content-center">
                            <span class="fw-bold text-info d-block mb-1" style="font-size: 0.85rem;"><?= strtoupper($j); ?></span>
                            <h3 class="fw-bold text-white mb-2"><?= $grand_total['jenjang'][$j]['total']; ?></h3>
                            <div class="d-flex justify-content-center gap-2" style="font-size: 0.75rem;">
                                <span class="text-light"><i class="fa fa-male text-info"></i> <?= $grand_total['jenjang'][$j]['L']; ?></span>
                                <span class="text-white-50">|</span>
                                <span class="text-light"><i class="fa fa-female text-danger"></i> <?= $grand_total['jenjang'][$j]['P']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-4 border-top border-secondary">
            <h6 class="text-warning fw-bold mb-3"><i class="fa fa-star me-2"></i>Rincian Mubaligh/Mubalighot Tugas (MT) Se-Desa</h6>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <div class="bg-warning text-dark px-4 py-2 rounded fw-bold fs-5 shadow-sm">Total MT: <?= $grand_total['MT']['total']; ?></div>
                
                <div class="border border-secondary px-3 py-2 rounded text-light" style="font-size: 0.9rem;">
                    <i class="fa fa-male text-info"></i> L: <b><?= $grand_total['MT']['L']; ?></b> <span class="mx-2 text-secondary">|</span>
                    <i class="fa fa-female text-danger"></i> P: <b><?= $grand_total['MT']['P']; ?></b>
                </div>

                <?php foreach($allowed_jenjang_hitung as $j): ?>
                    <?php if($grand_total['MT']['jenjang'][$j]['total'] > 0): ?>
                        <div class="bg-secondary bg-opacity-25 border border-secondary px-3 py-2 rounded text-white d-flex align-items-center gap-2" style="font-size:0.85rem;">
                            <span><?= $j; ?>: <span class="fw-bold text-warning fs-6"><?= $grand_total['MT']['jenjang'][$j]['total']; ?></span></span>
                            <span class="text-white-50" style="font-size:0.75rem;">(L: <?= $grand_total['MT']['jenjang'][$j]['L']; ?> | P: <?= $grand_total['MT']['jenjang'][$j]['P']; ?>)</span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach($grup_tampil as $grup): $dt = $data_rekap[$grup]; ?>
        <div class="<?= $is_pusat ? 'col-lg-6' : 'col-lg-8 mx-auto'; ?>">
            <div class="card card-custom bg-white p-0 shadow-sm overflow-hidden border-top border-primary border-5 h-100 d-flex flex-column">
                <div class="bg-light p-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa fa-map-marker-alt text-danger me-2"></i>Kel. <?= strtoupper($grup); ?></h5>
                </div>
                
                <div class="p-4 flex-grow-1">
                    <div class="text-center mb-4 pb-4 border-bottom">
                        <h6 class="text-muted fw-bold mb-1">TOTAL TERDAFTAR</h6>
                        <h1 class="fw-bold text-primary mb-1" style="font-size: 3.5rem;"><?= $dt['total']; ?></h1>
                        
                        <?php if(in_array('Umum', $allowed_jenjang_hitung)): ?>
                            <h5 class="text-info fw-bold mb-3"><i class="fa fa-sitemap me-1"></i> <?= $dt['kk']; ?> Kepala Keluarga (KK)</h5>
                        <?php endif; ?>

                        <div class="d-flex justify-content-center gap-3">
                            <span class="gender-badge bg-info-subtle text-info-emphasis border border-info"><i class="fa fa-male me-1"></i> Laki-laki: <?= $dt['L']; ?></span>
                            <span class="gender-badge bg-danger-subtle text-danger-emphasis border border-danger"><i class="fa fa-female me-1"></i> Perempuan: <?= $dt['P']; ?></span>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3"><i class="fa fa-layer-group text-warning me-2"></i>Rincian Populasi per Jenjang</h6>
                    <div class="row g-3 mb-4">
                        <?php foreach($allowed_jenjang_hitung as $j): ?>
                        <div class="<?= (count($allowed_jenjang_hitung) == 1) ? 'col-12' : 'col-6 col-md-4'; ?>">
                            <div class="box-jenjang-light text-center h-100 d-flex flex-column justify-content-center">
                                <span class="fw-bold text-primary d-block mb-1" style="font-size: 0.8rem;"><?= strtoupper($j); ?></span>
                                <h3 class="fw-bold text-dark mb-2"><?= $dt['jenjang'][$j]['total']; ?></h3>
                                <div class="d-flex justify-content-center gap-2 text-muted" style="font-size: 0.75rem; font-weight: 600;">
                                    <span><i class="fa fa-male text-info"></i> <?= $dt['jenjang'][$j]['L']; ?></span>
                                    <span>|</span>
                                    <span><i class="fa fa-female text-danger"></i> <?= $dt['jenjang'][$j]['P']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-auto pt-3 border-top bg-light p-3 rounded text-center">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa fa-star text-warning me-1"></i> Data Mubaligh Tugas (MT) Kelompok</h6>
                        <div class="d-flex flex-wrap justify-content-center gap-2 align-items-center">
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm">Total MT: <?= $dt['MT']['total']; ?></span>
                            
                            <span class="badge bg-white text-dark border border-secondary px-3 py-2 shadow-sm">
                                <i class="fa fa-male text-info"></i> L: <?= $dt['MT']['L']; ?> <span class="mx-1">|</span> 
                                <i class="fa fa-female text-danger"></i> P: <?= $dt['MT']['P']; ?>
                            </span>

                            <?php foreach($allowed_jenjang_hitung as $j): ?>
                                <?php if($dt['MT']['jenjang'][$j]['total'] > 0): ?>
                                    <span class="badge bg-white text-dark border border-warning px-3 py-2 shadow-sm d-flex align-items-center gap-1">
                                        <?= $j; ?>: <span class="text-warning fw-bold fs-6"><?= $dt['MT']['jenjang'][$j]['total']; ?></span>
                                        <span class="text-muted fw-normal ms-1" style="font-size: 0.7rem;">(L: <?= $dt['MT']['jenjang'][$j]['L']; ?> | P: <?= $dt['MT']['jenjang'][$j]['P']; ?>)</span>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>