<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// 1. PROTEKSI HAK AKSES (Termasuk MM Desa)
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$level = $_SESSION['level'];
$kelompok = $_SESSION['kelompok'];

// AUTO PATCH DATABASE (Memastikan ada kolom created_at untuk patokan mulai dihitung rapor)
$cek_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'created_at'");
if(mysqli_num_rows($cek_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    mysqli_query($conn, "UPDATE users SET created_at = '2020-01-01 00:00:00'");
}

// Filter Periode Rapor
$periode_pilih = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m');
$exp_periode = explode('-', $periode_pilih);
$thn_pilih = $exp_periode[0];
$bln_pilih = $exp_periode[1];

$opsi_bulan = [];
$nama_bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

for ($i = 0; $i <= 5; $i++) {
    $time = strtotime("-$i month");
    $val = date('Y-m', $time);
    $label = $nama_bulan_indo[(int)date('m', $time) - 1] . ' ' . date('Y', $time);
    $opsi_bulan[$val] = $label;
}
$label_periode_aktif = $opsi_bulan[$periode_pilih] ?? $nama_bulan_indo[(int)$bln_pilih - 1] . ' ' . $thn_pilih;

// =========================================================================
// 2. LOGIKA FILTER WILAYAH & JENJANG
// =========================================================================
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'ketua_mudai_desa', 'admin_mudai_desa']);

if ($is_pusat || $kelompok == 'Semua' || empty($kelompok)) {
    $filter_wilayah = "1";
    $list_kelompok = ['Semampir', 'Keputih', 'Praja'];
} else {
    $filter_wilayah = "u.kelompok = '$kelompok'";
    $list_kelompok = [$kelompok];
}

$list_jenjang = [];
if (in_array($level, ['ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa'])) { 
    $list_jenjang = ['Muda/i']; 
    $filter_jenjang = "(b.jenjang = 'Muda/i' OR b.jenjang IS NULL OR b.jenjang = '')"; 
} 
elseif ($level == 'admin_remaja') { 
    $list_jenjang = ['Remaja']; 
    $filter_jenjang = "(b.jenjang = 'Remaja' OR b.jenjang IS NULL OR b.jenjang = '')"; 
} 
elseif ($level == 'admin_praremaja') { 
    $list_jenjang = ['Pra Remaja']; 
    $filter_jenjang = "(b.jenjang = 'Pra Remaja' OR b.jenjang IS NULL OR b.jenjang = '')"; 
} 
elseif ($level == 'admin_caberawit') { 
    $list_jenjang = ['Caberawit']; 
    $filter_jenjang = "(b.jenjang = 'Caberawit' OR b.jenjang IS NULL OR b.jenjang = '')"; 
} 
else { 
    $list_jenjang = ['Umum', 'Muda/i', 'Remaja', 'Pra Remaja', 'Caberawit']; 
    $filter_jenjang = "1"; 
}

// 3. TARIK DATA MENTAH
$kegiatan = [];
$q_keg = mysqli_query($conn, "SELECT * FROM kegiatan WHERE MONTH(tgl_buat) = '$bln_pilih' AND YEAR(tgl_buat) = '$thn_pilih'");
while($k = mysqli_fetch_assoc($q_keg)) { $kegiatan[] = $k; }

$presensi = [];
$q_pres = mysqli_query($conn, "SELECT id_user, id_kegiatan, status_absen FROM presensi WHERE MONTH(tgl_presensi) = '$bln_pilih' AND YEAR(tgl_presensi) = '$thn_pilih'");
while($p = mysqli_fetch_assoc($q_pres)) { $presensi[$p['id_user']][$p['id_kegiatan']] = strtolower($p['status_absen']); }

$izin = [];
$q_iz = mysqli_query($conn, "SELECT id_user, id_kegiatan, jenis_izin, status_izin, status_konfirmasi FROM perizinan WHERE MONTH(tgl_pengajuan) = '$bln_pilih' AND YEAR(tgl_pengajuan) = '$thn_pilih'");
while($z = mysqli_fetch_assoc($q_iz)) { $izin[$z['id_user']][$z['id_kegiatan']] = $z; }

$data_rapor = [];
foreach($list_kelompok as $k) { foreach($list_jenjang as $j) { $data_rapor[$k][$j] = []; } }

// =========================================================================
// 4. KALKULASI RAPOR INDIVIDU
// =========================================================================
$q_users = mysqli_query($conn, "SELECT u.created_at, u.id_user, u.username, u.kelompok, b.nama_lengkap, b.jenjang, b.jenis_kelamin, b.foto FROM users u JOIN biodata_jamaah b ON u.id_user = b.id_user WHERE u.level = 'karyawan' AND ($filter_wilayah) AND ($filter_jenjang) ORDER BY b.nama_lengkap ASC");

while($u = mysqli_fetch_assoc($q_users)) {
    $uid = $u['id_user'];
    $kel = $u['kelompok'];
    
    // Fallback jika jenjang DB kosong agar masuk ke ranah admin yang login
    if (empty($u['jenjang'])) {
        $jen = (count($list_jenjang) == 1) ? $list_jenjang[0] : 'Umum';
    } else {
        $jen = $u['jenjang'];
    }

    $tgl_daftar_date = date('Y-m-d', strtotime($u['created_at']));
    if(!in_array($jen, $list_jenjang)) continue;

    $tepat = 0; $telat = 0; $online = 0; $izin_acc = 0; $alpa = 0;
    
    foreach($kegiatan as $k) {
        $keg_date = date('Y-m-d', strtotime($k['tgl_buat']));
        if ($keg_date < $tgl_daftar_date) continue; // Jangan hitung absen sebelum jamaah gabung
        
        $id_k = $k['id_kegiatan'];
        $match_kel = ($k['target_kelompok'] == 'Semua' || $k['target_kelompok'] == $kel);
        $match_jen = ($jen == 'Caberawit') ? ($k['target_jenjang'] == 'Caberawit') : ($k['target_jenjang'] == 'Semua' || $k['target_jenjang'] == 'Umum' || $k['target_jenjang'] == $jen);

        if($match_kel && $match_jen) {
            $is_alpa = true;
            if(isset($presensi[$uid][$id_k])) {
                if($presensi[$uid][$id_k] == 'tepat waktu') { $tepat++; $is_alpa = false; }
                elseif($presensi[$uid][$id_k] == 'terlambat') { $telat++; $is_alpa = false; }
            } elseif(isset($izin[$uid][$id_k])) {
                $z = $izin[$uid][$id_k];
                if($z['status_izin'] == 'disetujui') {
                    if($z['jenis_izin'] == 'Online' && $z['status_konfirmasi'] == 'Disetujui') { $online++; $is_alpa = false; } 
                    else { $izin_acc++; $is_alpa = false; }
                }
            }
            if($is_alpa && $k['is_selesai'] == 1) { $alpa++; }
        }
    }

    $total_masuk = $tepat + $telat + $online;
    $total_target = $total_masuk + $izin_acc + $alpa;
    $persen = ($total_target > 0) ? round(($total_masuk / $total_target) * 100) : 0;
    
    $u['tepat'] = $tepat; $u['telat'] = $telat; $u['online'] = $online;
    $u['izin'] = $izin_acc; $u['alpa'] = $alpa; $u['persen'] = $persen; $u['total_target'] = $total_target;
    $data_rapor[$kel][$jen][] = $u;
}

function colorBadge($p) {
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
    <title>Rapor Jamaah | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; font-size: 0.85rem; }
        .main-content { padding: 0 !important; padding-bottom: 90px !important; }
        
        /* STICKY HEADER COMPACT */
        .header-compact { 
            background: #fff; padding: 15px; position: sticky; top: 0; z-index: 1020;
            border-bottom: 1px solid #f1f5f9;
        }

        /* HORIZONTAL SCROLL TABS */
        .scroll-tabs {
            display: flex; flex-wrap: nowrap; overflow-x: auto; 
            padding: 10px 15px; background: #fff; border-bottom: 1px solid #f1f5f9;
            -webkit-overflow-scrolling: touch;
        }
        .scroll-tabs::-webkit-scrollbar { display: none; }
        .scroll-tabs .nav-link {
            white-space: nowrap; border-radius: 20px; padding: 6px 15px; font-size: 0.75rem;
            font-weight: 700; color: #64748b; background: #f1f5f9; margin-right: 8px; border: none;
        }
        .scroll-tabs .nav-link.active { background: #1a535c; color: #fff; }

        /* SINGLE COLUMN DATATABLES */
        table.dataTable thead { display: none; } 
        table.dataTable tbody td { padding: 0 !important; border: none !important; }
        table.dataTable { border-collapse: collapse !important; border: none !important; margin-top: 0 !important; }
        .dataTables_wrapper .row:first-child { padding: 10px 15px; background: #fff; margin: 0; }
        div.dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 20px; padding: 6px 15px; outline: none; width: 100%; font-size: 0.85rem; }
        div.dataTables_filter label { width: 100%; color: transparent; font-size: 0; }
        div.dataTables_length, div.dataTables_info { display: none; }

        /* KARTU RAPOR (COMPACT) */
        .rapor-item {
            display: flex; align-items: center; padding: 12px 15px;
            background: #fff; border-bottom: 1px solid #f1f5f9;
            text-decoration: none; color: inherit;
        }
        .rapor-item:active { background: #f8fafc; }
        .j-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; margin-right: 12px; }
        .j-info { flex-grow: 1; overflow: hidden; }
        .j-name { font-weight: 800; color: #1e293b; font-size: 0.85rem; margin-bottom: 2px; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .j-stats { display: flex; gap: 8px; font-size: 0.7rem; font-weight: 600; }
        
        .score-box { text-align: center; min-width: 45px; margin-left: 10px; }
        .score-val { font-size: 1.1rem; font-weight: 900; line-height: 1; display: block; }
        .score-label { font-size: 0.6rem; font-weight: 700; color: #94a3b8; }

        .filter-bulan { border-radius: 15px; font-size: 0.8rem; font-weight: bold; border: 1px solid #1a535c; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-compact shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa fa-user-check text-primary me-2"></i>Rapor Individu</h6>
            <a href="dashboard_keimaman.php" class="btn btn-sm btn-outline-dark rounded-pill py-0 px-3">Kembali</a>
        </div>
        <form action="" method="GET" id="formFilter" class="m-0">
            <select name="periode" class="form-select form-select-sm filter-bulan" onchange="this.form.submit();">
                <?php foreach($opsi_bulan as $val => $label): ?>
                    <option value="<?= $val; ?>" <?= ($periode_pilih == $val) ? 'selected' : ''; ?>>Periode: <?= $label; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if($is_pusat): ?>
        <div class="nav scroll-tabs" id="pills-kelompok" role="tablist">
            <?php $first_k = true; foreach($list_kelompok as $k): ?>
                <button class="nav-link <?= $first_k ? 'active' : ''; ?>" id="pills-<?= md5($k); ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-<?= md5($k); ?>" type="button" role="tab"><?= strtoupper($k); ?></button>
            <?php $first_k = false; endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="tab-content" id="pills-tabContent">
        <?php $first_k = true; foreach($list_kelompok as $k): ?>
            <div class="tab-pane fade <?= $first_k ? 'show active' : ''; ?>" id="pills-<?= md5($k); ?>" role="tabpanel">
                
                <div class="nav scroll-tabs" id="tabs-<?= md5($k); ?>" role="tablist">
                    <?php $first_j = true; foreach($list_jenjang as $j): ?>
                        <button class="nav-link <?= $first_j ? 'active' : ''; ?>" id="tab-<?= md5($k.$j); ?>-tab" data-bs-toggle="tab" data-bs-target="#tab-<?= md5($k.$j); ?>" type="button" role="tab"><?= $j; ?></button>
                    <?php $first_j = false; endforeach; ?>
                </div>

                <div class="tab-content">
                    <?php $first_j = true; foreach($list_jenjang as $j): ?>
                        <div class="tab-pane fade <?= $first_j ? 'show active' : ''; ?>" id="tab-<?= md5($k.$j); ?>" role="tabpanel">
                            
                            <table class="table table-rapor-compact w-100">
                                <thead><tr><th>Data</th></tr></thead>
                                <tbody>
                                    <?php foreach ($data_rapor[$k][$j] as $d): 
                                        $nama_tampil = !empty($d['nama_lengkap']) ? $d['nama_lengkap'] : $d['username'];
                                        $inisial = strtoupper(substr($nama_tampil, 0, 1));
                                        $url_foto = (!empty($d['foto']) && file_exists('uploads/'.$d['foto'])) ? 'uploads/'.$d['foto'] : 'https://placehold.co/100x100?text='.$inisial;
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="detail_rapor.php?id_user=<?= $d['id_user']; ?>" class="rapor-item">
                                                    <img src="<?= $url_foto; ?>" class="j-avatar shadow-sm" alt="Foto">
                                                    <div class="j-info">
                                                        <div class="j-name"><?= htmlspecialchars($nama_tampil); ?></div>
                                                        <div class="j-stats">
                                                            <span class="text-success" title="Tepat"><i class="fa fa-check-circle"></i> <?= $d['tepat']; ?></span>
                                                            <span class="text-warning" title="Telat"><i class="fa fa-clock"></i> <?= $d['telat']; ?></span>
                                                            <span class="text-primary" title="Zoom/Online"><i class="fa fa-video"></i> <?= $d['online']; ?></span>
                                                            <span class="text-danger" title="Alpa"><i class="fa fa-times-circle"></i> <?= $d['alpa']; ?></span>
                                                            <span class="text-muted"><i class="fa fa-bullseye"></i> <?= $d['total_target']; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="score-box">
                                                        <span class="score-val <?= str_replace('bg-', 'text-', colorBadge($d['persen'])); ?>"><?= $d['persen']; ?>%</span>
                                                        <span class="score-label">SKOR</span>
                                                    </div>
                                                    <div class="ms-2 text-muted opacity-50"><i class="fa fa-chevron-right fa-xs"></i></div>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>
                    <?php $first_j = false; endforeach; ?>
                </div>
            </div>
        <?php $first_k = false; endforeach; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('.table-rapor-compact').each(function() {
        $(this).DataTable({ 
            "language": { "search": "", "searchPlaceholder": "Cari jamaah di sini..." },
            "pageLength": 25,
            "ordering": false,
            "dom": '<"row"<"col-12"f>>rt<"row"<"col-12"p>>'
        });
    });

    $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});
</script>
</body>
</html>