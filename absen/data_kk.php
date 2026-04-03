<?php
session_start();
include 'koneksi.php';

// 1. PROTEKSI HAK AKSES
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Akses Ditolak! Hanya Pengurus yang berhak mengakses data KK.'); window.location='dashboard.php';</script>";
    exit;
}

$level = $_SESSION['level'];
$kelompok = $_SESSION['kelompok'];

// 2. LOGIKA FILTER WILAYAH
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa']);
if ($is_pusat || $kelompok == 'Semua' || empty($kelompok)) {
    $filter_wilayah = "1"; // Tampilkan Semua Desa
} else {
    $filter_wilayah = "u.kelompok = '$kelompok'"; // Hanya kelompoknya sendiri
}

// 3. TARIK DATA KEPALA KELUARGA & ANGGOTANYA FULL BIODATA
$query_kk = mysqli_query($conn, "
    SELECT b.*, u.kelompok 
    FROM biodata_jamaah b 
    JOIN users u ON b.id_user = u.id_user 
    WHERE b.status_keluarga = 'Kepala Keluarga' AND ($filter_wilayah)
    ORDER BY u.kelompok ASC, b.nama_lengkap ASC
");

$data_keluarga = [];
$grouped_data = []; 

while($kk = mysqli_fetch_assoc($query_kk)) {
    $id_kk = $kk['id_user'];
    $kel_kk = $kk['kelompok'];
    $kk['anggota'] = [];

    // Tarik data anak/istri FULL BIODATA
    $q_anggota = mysqli_query($conn, "
        SELECT b.*, u.kelompok 
        FROM biodata_jamaah b 
        JOIN users u ON b.id_user = u.id_user
        WHERE b.id_kepala_keluarga = '$id_kk' 
        ORDER BY FIELD(b.status_keluarga, 'Istri', 'Anak', 'Lainnya'), b.nama_lengkap ASC
    ");
    
    while($ang = mysqli_fetch_assoc($q_anggota)) {
        $kk['anggota'][] = $ang;
    }
    
    $data_keluarga[] = $kk;
    $grouped_data[$kel_kk][] = $kk;
}

$list_kelompok = empty($grouped_data) ? [] : array_keys($grouped_data);
sort($list_kelompok);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Data Kartu Keluarga (KK) | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        
        /* Edge to Edge Mobile Layout */
        .main-content { padding: 0 !important; padding-bottom: 90px !important; }
        .header-title { background: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; }
        
        /* SCROLLABLE TABS */
        .nav-pills-scroll {
            display: flex; flex-wrap: nowrap; overflow-x: auto; 
            padding: 10px 15px; scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch; border-bottom: 1px solid #f1f5f9;
            background: #fff;
        }
        .nav-pills-scroll::-webkit-scrollbar { display: none; } 
        .nav-pills-scroll .nav-link {
            white-space: nowrap; border-radius: 20px; padding: 8px 20px; 
            font-weight: 700; color: #64748b; background: #f1f5f9; margin-right: 10px;
            border: 2px solid transparent; transition: 0.3s;
        }
        .nav-pills-scroll .nav-link.active {
            background: #e0f2fe; color: #0d6efd; border-color: #0d6efd;
        }

        /* SINGLE COLUMN DATATABLES HACKS */
        table.dataTable thead { display: none; } 
        table.dataTable tbody td { padding: 0 !important; border: none !important; }
        table.dataTable { border-collapse: collapse !important; border: none !important; margin-top: 0 !important; }
        .dataTables_wrapper .row:first-child { padding: 10px 15px; background: #fff; margin: 0; }
        div.dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 20px; padding: 6px 15px; outline: none; width: 100%; font-size: 0.9rem;}
        div.dataTables_filter label { width: 100%; color: transparent; font-size: 0; }
        div.dataTables_filter label input::placeholder { color: #94a3b8; }
        div.dataTables_length, div.dataTables_info { display: none; } 
        .dataTables_wrapper .row:last-child { padding: 15px; } 

        /* KARTU KELUARGA (LIST ITEM) */
        .kk-card {
            background: #fff; border-bottom: 1px solid #f1f5f9; 
            transition: background 0.2s;
        }
        .kk-header { padding: 15px 20px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
        .kk-header:active { background: #f8fafc; }
        
        .kk-icon { width: 45px; height: 45px; border-radius: 12px; background: #e0f2fe; color: #0d6efd; display: flex; justify-content: center; align-items: center; font-size: 1.2rem; margin-right: 15px; }
        .kk-info { flex-grow: 1; }
        .kk-name { font-weight: 800; color: #1e293b; font-size: 1rem; margin-bottom: 3px; }
        .kk-meta { font-size: 0.75rem; color: #64748b; display: flex; gap: 10px; align-items: center; }
        
        .kk-body { background: #f8fafc; border-top: 1px dashed #e2e8f0; padding: 15px 20px; }
        .member-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .member-item:last-child { border-bottom: none; padding-bottom: 0; }
        
        .m-name { font-weight: 700; color: #334155; font-size: 0.9rem; }
        .m-role { font-size: 0.7rem; padding: 3px 8px; border-radius: 6px; font-weight: 700; }
        .btn-profil { font-size: 0.75rem; padding: 5px 12px; border-radius: 12px; font-weight: bold; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-title shadow-sm">
        <div>
            <h5 class="fw-bold mb-1 text-dark"><i class="fa fa-sitemap text-primary me-2"></i>Data Kartu Keluarga</h5>
            <small class="text-muted fw-bold">Direktori Keluarga <?= $is_pusat ? 'Se-Desa' : 'Kel. '.$kelompok; ?></small>
        </div>
        <div class="bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-3 text-center border border-primary border-opacity-25">
            <h4 class="fw-bold mb-0"><?= count($data_keluarga); ?></h4>
            <span style="font-size: 0.65rem; font-weight: 800;">TOTAL KK</span>
        </div>
    </div>

    <?php if($is_pusat && count($list_kelompok) > 0): ?>
        <div class="nav nav-pills-scroll" id="pills-kelompok" role="tablist">
            <?php $first_k = true; foreach($list_kelompok as $k): ?>
                <button class="nav-link shadow-sm <?= $first_k ? 'active' : ''; ?>" id="pills-<?= md5($k); ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-<?= md5($k); ?>" type="button" role="tab">
                    Kel. <?= strtoupper($k); ?> <span class="badge bg-secondary ms-1 rounded-pill"><?= count($grouped_data[$k]); ?></span>
                </button>
            <?php $first_k = false; endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="tab-content" id="pills-tabContent">
        <?php 
        if(count($list_kelompok) == 0): 
            echo '<div class="text-center py-5"><i class="fa fa-folder-open fa-3x text-muted opacity-25 mb-3"></i><h6 class="text-muted fw-bold">Belum ada data KK terdaftar.</h6></div>';
        endif;

        $first_k = true; 
        foreach($list_kelompok as $k): 
            $data_tabel = $grouped_data[$k];
        ?>
        <div class="tab-pane fade <?= $first_k ? 'show active' : ''; ?>" id="pills-<?= md5($k); ?>" role="tabpanel">
            
            <table class="table tabel-kk" style="width:100%">
                <thead><tr><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach($data_tabel as $kk): 
                        $jml_anggota = count($kk['anggota']) + 1; // +1 Bapaknya
                        $wa_number = '';
                        if(!empty($kk['no_hp'])) {
                            $wa_number = preg_replace('/[^0-9]/', '', $kk['no_hp']);
                            if(substr($wa_number, 0, 1) == '0') { $wa_number = '62' . substr($wa_number, 1); }
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="kk-card">
                                <div class="kk-header" data-bs-toggle="collapse" data-bs-target="#fam-<?= $kk['id_user']; ?>">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="kk-icon shadow-sm"><i class="fa fa-home"></i></div>
                                        <div class="kk-info">
                                            <div class="kk-name">Keluarga Bpk. <?= strtoupper($kk['nama_lengkap']); ?></div>
                                            <div class="kk-meta">
                                                <span class="badge bg-info text-dark bg-opacity-25 border border-info border-opacity-50"><i class="fa fa-users me-1"></i><?= $jml_anggota; ?> Jiwa</span>
                                                <span class="text-truncate" style="max-width: 150px;"><i class="fa fa-map-marker-alt text-danger me-1"></i><?= !empty($kk['alamat_asal']) ? htmlspecialchars($kk['alamat_asal']) : 'Alamat Kosong'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-muted ps-2"><i class="fa fa-chevron-down toggle-icon"></i></div>
                                </div>

                                <div class="collapse" id="fam-<?= $kk['id_user']; ?>">
                                    <div class="kk-body">
                                        
                                        <?php if(!empty($wa_number)): ?>
                                        <div class="mb-3 text-end">
                                            <a href="https://wa.me/<?= $wa_number; ?>" target="_blank" class="btn btn-sm btn-success fw-bold rounded-pill shadow-sm px-3 py-1" style="font-size:0.75rem;">
                                                <i class="fab fa-whatsapp me-1"></i> Hubungi Bapak
                                            </a>
                                        </div>
                                        <?php endif; ?>

                                        <div class="member-item">
                                            <div>
                                                <div class="m-name"><i class="fa fa-male text-primary w-15px text-center me-1"></i> <?= strtoupper($kk['nama_lengkap']); ?></div>
                                                <span class="m-role bg-primary text-white">Kepala Keluarga</span>
                                                <span class="text-muted" style="font-size: 0.65rem; margin-left: 5px;"><?= $kk['jenjang']; ?></span>
                                            </div>
                                            <a href="detail_jamaah.php?id=<?= $kk['id_user']; ?>" class="btn btn-outline-dark btn-profil text-decoration-none px-3 shadow-sm"><i class="fa fa-id-card me-1"></i> Profil</a>
                                        </div>

                                        <?php foreach($kk['anggota'] as $ang): 
                                            $ikon = ($ang['jenis_kelamin'] == 'P') ? 'fa-female text-danger' : 'fa-male text-primary';
                                            $bg_role = ($ang['status_keluarga'] == 'Istri') ? 'bg-danger text-white' : 'bg-warning text-dark';
                                        ?>
                                        <div class="member-item">
                                            <div>
                                                <div class="m-name"><i class="fa <?= $ikon; ?> w-15px text-center me-1"></i> <?= strtoupper($ang['nama_lengkap']); ?></div>
                                                <span class="m-role <?= $bg_role; ?>"><?= $ang['status_keluarga']; ?></span>
                                                <span class="text-muted" style="font-size: 0.65rem; margin-left: 5px;"><?= $ang['jenjang']; ?></span>
                                            </div>
                                            <a href="detail_jamaah.php?id=<?= $ang['id_user']; ?>" class="btn btn-outline-dark btn-profil text-decoration-none px-3 shadow-sm"><i class="fa fa-id-card me-1"></i> Profil</a>
                                        </div>
                                        <?php endforeach; ?>

                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

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
    // Inisialisasi DataTables Single Column
    var tables = $('.tabel-kk').DataTable({ 
        "language": { "search": "", "searchPlaceholder": "Cari Nama Bapak..." },
        "ordering": false,
        "pageLength": 15,
        "dom": '<"row"<"col-sm-12"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Menyesuaikan tabel saat tab ditekan
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // Animasi putar ikon panah saat laci dibuka/ditutup
    $('.collapse').on('show.bs.collapse', function () {
        $(this).parent().find('.toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-up text-primary');
    }).on('hide.bs.collapse', function () {
        $(this).parent().find('.toggle-icon').removeClass('fa-chevron-up text-primary').addClass('fa-chevron-down');
    });
});
</script>
</body>
</html>