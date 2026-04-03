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

// =========================================================================
// LOGIKA FILTER KELOMPOK & JENJANG (SESUAI JABATAN)
// =========================================================================
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'admin_mudai_desa']);

if ($is_pusat || $kelompok == 'Semua' || empty($kelompok)) {
    $filter_wilayah = "1";
    $list_kelompok = ['Semua Wilayah', 'Semampir', 'Keputih', 'Praja'];
} else {
    $filter_wilayah = "(target_kelompok = 'Semua' OR target_kelompok = '$kelompok')";
    $list_kelompok = [$kelompok];
}

$list_jenjang = ['Campur (Semua)']; 
if (in_array($level, ['ketua_mudai', 'admin_mudai', 'admin_mudai_desa'])) {
    $filter_jenjang = "(target_jenjang = 'Semua' OR target_jenjang = 'Muda/i')";
    array_push($list_jenjang, 'Muda/i');
} elseif ($level == 'admin_remaja') {
    $filter_jenjang = "(target_jenjang = 'Semua' OR target_jenjang = 'Remaja')";
    array_push($list_jenjang, 'Remaja');
} elseif ($level == 'admin_praremaja') {
    $filter_jenjang = "(target_jenjang = 'Semua' OR target_jenjang = 'Pra Remaja')";
    array_push($list_jenjang, 'Pra Remaja');
} elseif ($level == 'admin_caberawit') {
    $filter_jenjang = "target_jenjang = 'Caberawit'";
    array_push($list_jenjang, 'Caberawit');
} else {
    $filter_jenjang = "1"; 
    array_push($list_jenjang, 'Umum', 'Muda/i', 'Remaja', 'Pra Remaja', 'Caberawit');
}

// =========================================================================
// LOGIKA HAPUS PENGAJIAN
// =========================================================================
if (isset($_GET['hapus_kegiatan']) && $can_edit) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus_kegiatan']);
    
    $q_keg = mysqli_query($conn, "SELECT judul_pengajian, tgl_buat FROM kegiatan WHERE id_kegiatan = '$id_hapus'");
    if(mysqli_num_rows($q_keg) > 0) {
        $keg = mysqli_fetch_assoc($q_keg);
        $judul_hapus = $keg['judul_pengajian'];
        $tgl_hapus = $keg['tgl_buat'];
        
        $q_kembar = mysqli_query($conn, "SELECT id_kegiatan FROM kegiatan WHERE judul_pengajian = '$judul_hapus' AND tgl_buat = '$tgl_hapus'");
        while($row_k = mysqli_fetch_assoc($q_kembar)) {
            $id_k = $row_k['id_kegiatan'];
            mysqli_query($conn, "DELETE FROM presensi WHERE id_kegiatan = '$id_k'");
            mysqli_query($conn, "DELETE FROM perizinan WHERE id_kegiatan = '$id_k'");
        }
        
        mysqli_query($conn, "DELETE FROM kegiatan WHERE judul_pengajian = '$judul_hapus' AND tgl_buat = '$tgl_hapus'");
        echo "<script>alert('Berhasil dihapus!'); window.location='riwayat_pengajian.php';</script>";
    }
    exit;
}

// =========================================================================
// AMBIL DATA & SMART GROUPING
// =========================================================================
$query_kegiatan = "SELECT * FROM kegiatan WHERE ($filter_wilayah) AND ($filter_jenjang) ORDER BY tgl_buat DESC, id_kegiatan DESC";
$result = mysqli_query($conn, $query_kegiatan);

$raw_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $raw_data[] = $row;
}

$grouped_kegiatan = [];
foreach($raw_data as $row) {
    $key = md5($row['judul_pengajian'] . $row['tgl_buat'] . $row['target_jenjang']); 
    
    if(!isset($grouped_kegiatan[$key])) {
        $grouped_kegiatan[$key] = $row;
        $grouped_kegiatan[$key]['list_kelompok'] = [$row['target_kelompok']];
    } else {
        if(!in_array($row['target_kelompok'], $grouped_kegiatan[$key]['list_kelompok'])) {
            $grouped_kegiatan[$key]['list_kelompok'][] = $row['target_kelompok'];
        }
    }
}

$data_riwayat = [];
foreach($list_kelompok as $k) {
    foreach($list_jenjang as $j) {
        $data_riwayat[$k][$j] = [];
    }
}

foreach ($grouped_kegiatan as $row) {
    $j_db = $row['target_jenjang'];
    $j_key = ($j_db == 'Semua') ? 'Umum' : $j_db; 

    $target_tabs = [];
    if ($is_pusat) {
        $target_tabs[] = 'Semua Wilayah'; 
        foreach($row['list_kelompok'] as $lk) {
            if ($lk == 'Semua') {
                array_push($target_tabs, 'Semampir', 'Keputih', 'Praja');
            } else {
                $target_tabs[] = $lk; 
            }
        }
    } else {
        $target_tabs[] = $kelompok; 
    }

    $target_tabs = array_unique($target_tabs); 

    foreach ($target_tabs as $tab) {
        if (isset($data_riwayat[$tab])) {
            $data_riwayat[$tab]['Campur (Semua)'][] = $row;
            if (isset($data_riwayat[$tab][$j_key])) {
                $data_riwayat[$tab][$j_key][] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Riwayat Pengajian | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="style_mobile.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .main-content { padding: 0 !important; padding-bottom: 90px !important; overflow-x: hidden; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        
        /* 1. TABS SCROLLABLE (KEMBALI KE VERSI GESER KANAN-KIRI) */
        .nav-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
        }
        .nav-wrapper::-webkit-scrollbar { display: none; }
        
        .nav-scroll-flex {
            display: inline-flex !important;
            flex-wrap: nowrap !important; /* Paksa memanjang, jangan turun baris */
            gap: 8px;
            padding-bottom: 8px;
            padding-left: 2px;
            margin-bottom: 0;
            list-style: none;
        }

        /* Desain Tombol Tab Wilayah */
        .btn-tab-wilayah {
            background: #fff; color: #64748b; border: 1px solid #cbd5e1;
            padding: 8px 16px; border-radius: 50px; font-size: 0.8rem;
            font-weight: bold; text-decoration: none; transition: 0.2s;
            flex-shrink: 0; /* Jangan sampai menciut tergencet */
            white-space: nowrap;
        }
        .btn-tab-wilayah.active {
            background: #1e293b; color: #fff; border-color: #1e293b;
        }
        
        /* Desain Tombol Tab Jenjang */
        .btn-tab-jenjang {
            background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;
            padding: 8px 16px; border-radius: 8px; font-size: 0.8rem;
            font-weight: bold; text-decoration: none; transition: 0.2s;
            flex-shrink: 0; /* Jangan sampai menciut tergencet */
            white-space: nowrap;
        }
        .btn-tab-jenjang.active {
            background: #0ea5e9; color: #fff; border-color: #0ea5e9;
            box-shadow: 0 2px 6px rgba(14, 165, 233, 0.2);
        }

        /* 2. PENGATURAN KOTAK PENCARIAN (DATATABLES) */
        .box-pencarian { width: 100%; margin-bottom: 15px; }
        .box-pencarian label { width: 100%; text-align: left; font-size: 0.8rem; font-weight: bold; color: #64748b; }
        .box-pencarian input {
            width: 100% !important; margin-top: 5px; padding: 10px 15px;
            border-radius: 10px; border: 1px solid #cbd5e1; font-size: 0.85rem; background: #f8fafc;
        }
        .box-halaman { width: 100%; margin-top: 15px; text-align: center; }

        /* 3. DESAIN KARTU RIWAYAT (PENGGANTI TABEL) */
        .kegiatan-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 15px; margin-bottom: 10px; transition: 0.2s;
        }
        .kegiatan-card:hover { border-color: #cbd5e1; background: #f8fafc; }
        
        .kc-title { font-size: 0.9rem; font-weight: 800; color: #1e293b; line-height: 1.3; margin-bottom: 4px;}
        .kc-meta { font-size: 0.7rem; color: #64748b; margin-bottom: 10px; }
        .kc-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
        .kc-badge { font-size: 0.65rem; padding: 4px 8px; border-radius: 6px; font-weight: bold; }
        
        /* Kunci agar tabel datatables murni 1 kolom invisible */
        table.dataTable thead { display: none !important; }
        table.dataTable tbody tr { background: transparent !important; border: none !important; }
        table.dataTable tbody td { padding: 0 !important; border: none !important; display: block; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="bg-white p-3 border-bottom shadow-sm d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold text-dark mb-0"><i class="fa fa-book-open text-primary me-2"></i>Riwayat</h6>
        </div>
        <?php if($can_edit): ?>
            <a href="buka_pengajian.php" class="btn btn-sm btn-primary rounded-pill fw-bold px-3 shadow-sm" style="font-size:0.75rem;"><i class="fa fa-plus me-1"></i> Baru</a>
        <?php endif; ?>
    </div>

    <div class="px-3 pb-4">
        <?php if($is_pusat): ?>
            <div class="nav-wrapper mb-3">
                <ul class="nav nav-scroll-flex" id="pills-kelompok" role="tablist">
                    <?php $first_k = true; foreach($list_kelompok as $k): ?>
                        <li role="presentation">
                            <button class="btn-tab-wilayah <?= $first_k ? 'active' : ''; ?>" id="pills-<?= md5($k); ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-<?= md5($k); ?>" type="button" role="tab">
                                <?= ($k == 'Semua Wilayah') ? '<i class="fa fa-globe"></i>' : '<i class="fa fa-map-marker-alt text-danger"></i>'; ?> <?= strtoupper($k); ?>
                            </button>
                        </li>
                    <?php $first_k = false; endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-3 bg-white mb-4">
            <div class="tab-content" id="pills-tabContent">
                <?php $first_k = true; foreach($list_kelompok as $k): ?>
                    <div class="tab-pane fade <?= $first_k ? 'show active' : ''; ?>" id="pills-<?= md5($k); ?>" role="tabpanel">
                        
                        <div class="nav-wrapper border-bottom pb-2 mb-3">
                            <ul class="nav nav-scroll-flex" id="tabs-<?= md5($k); ?>" role="tablist">
                                <?php $first_j = true; foreach($list_jenjang as $j): ?>
                                    <li role="presentation">
                                        <button class="btn-tab-jenjang <?= $first_j ? 'active' : ''; ?>" id="tab-<?= md5($k.$j); ?>-tab" data-bs-toggle="tab" data-bs-target="#tab-<?= md5($k.$j); ?>" type="button" role="tab">
                                            <?= $j; ?>
                                        </button>
                                    </li>
                                <?php $first_j = false; endforeach; ?>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <?php $first_j = true; foreach($list_jenjang as $j): ?>
                                <div class="tab-pane fade <?= $first_j ? 'show active' : ''; ?>" id="tab-<?= md5($k.$j); ?>" role="tabpanel">
                                    
                                    <table class="table tabel-riwayat w-100 mb-0">
                                        <thead><tr><th>Data</th></tr></thead>
                                        <tbody>
                                            <?php 
                                            $data_tabel = $data_riwayat[$k][$j];
                                            if(count($data_tabel) > 0): 
                                                foreach ($data_tabel as $d): 
                                                    $is_event_gabungan = (in_array('Semua', $d['list_kelompok']) || count($d['list_kelompok']) > 1);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="kegiatan-card shadow-sm">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="kc-title pe-2"><?= strtoupper($d['judul_pengajian']); ?></div>
                                                                <div>
                                                                    <?php if($d['is_selesai'] == 1): ?>
                                                                        <span class="badge bg-danger"><i class="fa fa-lock"></i> Tutup</span>
                                                                    <?php elseif($d['status_buka'] == 1): ?>
                                                                        <span class="badge bg-success heartbeat"><i class="fa fa-door-open"></i> Buka</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary"><i class="fa fa-door-closed"></i> Standby</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="kc-meta">
                                                                <i class="fa fa-calendar-alt me-1 text-primary"></i> <?= date('d M Y, H:i', strtotime($d['tgl_buat'])); ?><br>
                                                                <i class="fa fa-map-marker-alt me-1 text-danger mt-1"></i> <?= $d['tempat_pengajian']; ?>
                                                            </div>
                                                            
                                                            <div class="kc-tags">
                                                                <span class="kc-badge bg-light text-dark border border-secondary"><?= $d['target_jenjang']; ?></span>
                                                                <?php foreach($d['list_kelompok'] as $lk): ?>
                                                                    <span class="kc-badge bg-secondary text-white"><?= ($lk == 'Semua') ? 'Gabungan' : $lk; ?></span>
                                                                <?php endforeach; ?>
                                                                <?php if($d['is_selesai'] != 1 && $d['status_izin'] == 1): ?>
                                                                    <span class="kc-badge border border-warning text-warning"><i class="fa fa-envelope-open me-1"></i> Izin ON</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="d-flex gap-2 border-top pt-3 mt-2">
                                                                <a href="detail_absen.php?id_kegiatan=<?= $d['id_kegiatan']; ?>" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm rounded-pill flex-grow-1" style="font-size:0.75rem;">
                                                                    <i class="fa fa-cogs me-1"></i> Buka Panel Pengajian
                                                                </a>
                                                                <?php if($can_edit && ($is_pusat || !$is_event_gabungan)): ?>
                                                                    <a href="?hapus_kegiatan=<?= $d['id_kegiatan']; ?>" onclick="return confirm('Yakin hapus permanen?')" class="btn btn-outline-danger btn-sm shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                                                        <i class="fa fa-trash"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                    
                                </div>
                            <?php $first_j = false; endforeach; ?>
                        </div> 
                    </div>
                <?php $first_k = false; endforeach; ?>
            </div> 
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // INISIALISASI DATA TABLES MURNI 1 KOLOM (DENGAN TAMPILAN CARD)
    var tables = $('.tabel-riwayat').DataTable({ 
        "dom": "<'box-pencarian'f>t<'box-halaman'p>", 
        "lengthChange": false, 
        "pageLength": 10,
        "language": { 
            "search": "", 
            "searchPlaceholder": "🔍 Cari jadwal pengajian...",
            "emptyTable": "Belum ada riwayat pengajian di kategori ini.",
            "paginate": { "previous": "«", "next": "»" }
        },
        "ordering": false,
        "autoWidth": false,
        "responsive": false 
    });

    $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});
</script>
<style>
.heartbeat { animation: heartbeat 1.5s ease-in-out infinite both; }
@keyframes heartbeat { 10%, 33% { transform: scale(0.95); } 17%, 45% { transform: scale(1); } }
</style>
</body>
</html>