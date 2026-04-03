<?php
session_start();
include 'koneksi.php';

// 1. PROTEKSI HAK AKSES (Hanya Keimaman & Tim Dhuafa)
$allowed_dhuafa = ['superadmin', 'keimaman_desa', 'keimaman', 'tim_dhuafa_desa', 'tim_dhuafa'];

if (!in_array($_SESSION['level'], $allowed_dhuafa)) {
    echo "<script>alert('Akses Ditolak! Ini adalah ruang rahasia Tim Dhuafa dan Keimaman.'); window.location='dashboard.php';</script>"; 
    exit;
}

$level = $_SESSION['level'];
$kel_user = $_SESSION['kelompok'];

// PENENTUAN HAK EDIT & FILTER WILAYAH
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'tim_dhuafa_desa']);
$can_edit = in_array($level, ['superadmin', 'admin_desa', 'tim_dhuafa_desa', 'tim_dhuafa']); // Keimaman hanya View

if ($is_pusat || $kel_user == 'Semua' || empty($kel_user)) {
    $filter_wilayah = "1";
} else {
    $filter_wilayah = "u.kelompok = '$kel_user'";
}

// =========================================================================
// PROSES SIMPAN / UPDATE DATA BANTUAN DHUAFA
// =========================================================================
if (isset($_POST['simpan_dhuafa']) && $can_edit) {
    $id_users = $_POST['id_user']; 

    foreach($id_users as $uid) {
        $bp = isset($_POST['bantuan_pusat'][$uid]) ? 'Ya' : '';
        $bda = isset($_POST['bantuan_daerah'][$uid]) ? 'Ya' : '';
        $bde = isset($_POST['bantuan_desa'][$uid]) ? 'Ya' : '';
        
        if(isset($_POST['bantuan_kelompok'][$uid]) && $_POST['bantuan_kelompok'][$uid] == 'Masuk Daftar') {
            $bk = 'Masuk Daftar';
        } else {
            $bk = isset($_POST['bantuan_kelompok'][$uid]) ? 'Ya' : '';
        }

        if(empty($bp) && empty($bda) && empty($bde) && empty($bk)) {
            mysqli_query($conn, "DELETE FROM data_dhuafa WHERE id_user = '$uid'");
        } else {
            mysqli_query($conn, "INSERT INTO data_dhuafa (id_user, bantuan_pusat, bantuan_daerah, bantuan_desa, bantuan_kelompok) 
                                 VALUES ('$uid', '$bp', '$bda', '$bde', '$bk') 
                                 ON DUPLICATE KEY UPDATE bantuan_pusat='$bp', bantuan_daerah='$bda', bantuan_desa='$bde', bantuan_kelompok='$bk'");
        }
    }
    echo "<script>alert('Data Bantuan Dhuafa berhasil diperbarui!'); window.location='data_dhuafa.php';</script>";
    exit;
}

// =========================================================================
// TARIK DATA KEPALA KELUARGA & DETEKSI STATUS DHUAFA
// =========================================================================
$query_kk = mysqli_query($conn, "
    SELECT b.*, u.kelompok 
    FROM biodata_jamaah b 
    JOIN users u ON b.id_user = u.id_user 
    WHERE b.status_keluarga = 'Kepala Keluarga' AND ($filter_wilayah)
    ORDER BY u.kelompok ASC, b.nama_lengkap ASC
");

$dhuafa_families = [];
$non_dhuafa_families = [];
$total_jiwa_terbantu = 0;

while($kk = mysqli_fetch_assoc($query_kk)) {
    $id_kk = $kk['id_user'];
    $kel_name = $kk['kelompok'];
    $is_family_dhuafa = false;
    
    $q_dhuafa_kk = mysqli_query($conn, "SELECT * FROM data_dhuafa WHERE id_user = '$id_kk'");
    $kk['bantuan'] = mysqli_fetch_assoc($q_dhuafa_kk);
    
    if($kk['bantuan']) { 
        $is_family_dhuafa = true; 
        if($kk['bantuan']['bantuan_pusat'] == 'Ya' || $kk['bantuan']['bantuan_daerah'] == 'Ya' || $kk['bantuan']['bantuan_desa'] == 'Ya' || $kk['bantuan']['bantuan_kelompok'] == 'Ya') {
            $total_jiwa_terbantu++; 
        }
    }

    $q_anggota = mysqli_query($conn, "
        SELECT b.*, d.bantuan_pusat, d.bantuan_daerah, d.bantuan_desa, d.bantuan_kelompok 
        FROM biodata_jamaah b 
        LEFT JOIN data_dhuafa d ON b.id_user = d.id_user
        WHERE b.id_kepala_keluarga = '$id_kk'
        ORDER BY FIELD(b.status_keluarga, 'Istri', 'Anak', 'Lainnya'), b.nama_lengkap ASC
    ");
    
    $kk['anggota'] = [];
    while($ang = mysqli_fetch_assoc($q_anggota)) {
        $kk['anggota'][] = $ang;
        if(!empty($ang['bantuan_pusat']) || !empty($ang['bantuan_daerah']) || !empty($ang['bantuan_desa']) || !empty($ang['bantuan_kelompok'])) {
            $is_family_dhuafa = true;
            if($ang['bantuan_pusat'] == 'Ya' || $ang['bantuan_daerah'] == 'Ya' || $ang['bantuan_desa'] == 'Ya' || $ang['bantuan_kelompok'] == 'Ya') {
                $total_jiwa_terbantu++;
            }
        }
    }
    
    if($is_family_dhuafa) { $dhuafa_families[$kel_name][] = $kk; } 
    else { $non_dhuafa_families[] = $kk; }
}

$list_kelompok = array_keys($dhuafa_families);
sort($list_kelompok);

$total_kk_dhuafa = 0;
foreach($dhuafa_families as $kel => $kks) { $total_kk_dhuafa += count($kks); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Data Dhuafa | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; font-size: 0.85rem; }
        .main-content { padding: 0 !important; padding-bottom: 90px !important; }
        
        .header-title { background: #fff; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; z-index: 1020; }
        
        /* HORIZONTAL SCROLL TABS */
        .nav-pills-scroll {
            display: flex; flex-wrap: nowrap; overflow-x: auto; 
            padding: 10px 15px; scroll-behavior: smooth; background: #fff;
            -webkit-overflow-scrolling: touch; border-bottom: 1px solid #f1f5f9;
        }
        .nav-pills-scroll::-webkit-scrollbar { display: none; }
        .nav-pills-scroll .nav-link {
            white-space: nowrap; border-radius: 20px; padding: 6px 15px; font-size: 0.75rem;
            font-weight: 700; color: #1a535c; background: #f1f5f9; margin-right: 8px; border: none;
        }
        .nav-pills-scroll .nav-link.active { background: #198754; color: #fff; }

        /* KARTU STATISTIK MINI */
        .stat-card { border-radius: 12px; padding: 12px; display: flex; align-items: center; gap: 12px; border: 1px solid #f1f5f9; background: #fff;}
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 1.2rem; }

        /* KARTU DAFTAR DHUAFA */
        table.dataTable thead { display: none; } 
        table.dataTable tbody td { padding: 0 !important; border: none !important; }
        table.dataTable { border-collapse: collapse !important; border: none !important; margin-top: 0 !important; }
        .dataTables_wrapper .row:first-child { padding: 10px 15px; background: #fff; border-bottom: 1px solid #f1f5f9; margin: 0; }
        div.dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 20px; padding: 6px 15px; outline: none; width: 100%; font-size: 0.85rem;}
        div.dataTables_filter label { width: 100%; color: transparent; font-size: 0; }
        div.dataTables_length, div.dataTables_info { display: none; }
        
        .dhuafa-item { background: #fff; border-bottom: 1px solid #f1f5f9; padding: 15px; }
        
        /* MODAL EDIT MUNGIL */
        .member-edit-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 10px; }
        .cb-wrapper { display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .form-check-input { transform: scale(1.3); margin: 0; cursor: pointer; }
        .cb-label { font-size: 0.65rem; font-weight: 700; color: #64748b; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-title shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><i class="fa fa-hand-holding-heart text-success me-2"></i>Data Dhuafa</h6>
                <small class="text-muted" style="font-size:0.7rem;">Distribusi Bantuan Sosial</small>
            </div>
            <?php if($can_edit): ?>
                <button class="btn btn-sm btn-success rounded-pill fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDhuafa">
                    <i class="fa fa-plus me-1"></i> KK Baru
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="px-3 pt-3">
        <div class="row g-2 mb-2">
            <div class="col-6">
                <div class="stat-card shadow-sm border-warning">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa fa-home"></i></div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark" style="line-height:1;"><?= $total_kk_dhuafa; ?></h4>
                        <span class="text-muted fw-bold" style="font-size:0.65rem;">TOTAL KK</span>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card shadow-sm border-success">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa fa-users"></i></div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark" style="line-height:1;"><?= $total_jiwa_terbantu; ?></h4>
                        <span class="text-muted fw-bold" style="font-size:0.65rem;">JIWA TERBANTU</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if($is_pusat && count($list_kelompok) > 0): ?>
        <div class="nav nav-pills-scroll" id="pills-kelompok" role="tablist">
            <?php $first_k = true; foreach($list_kelompok as $k): ?>
                <button class="nav-link <?= $first_k ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-<?= md5($k); ?>" type="button">
                    KEL. <?= strtoupper($k); ?>
                    <span class="badge <?= $first_k ? 'bg-white text-success' : 'bg-success text-white'; ?> ms-1 rounded-pill badge-k"><?= count($dhuafa_families[$k]); ?></span>
                </button>
            <?php $first_k = false; endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="tab-content">
        <?php 
        if(count($list_kelompok) == 0): 
            echo '<div class="text-center py-5"><i class="fa fa-info-circle fa-3x text-warning opacity-50 mb-3"></i><h6 class="text-muted fw-bold">Belum ada data dhuafa.</h6></div>';
        endif;

        $first_k = true; 
        foreach($list_kelompok as $k): 
            $data_tabel = $dhuafa_families[$k];
        ?>
        <div class="tab-pane fade <?= $first_k ? 'show active' : ''; ?>" id="tab-<?= md5($k); ?>" role="tabpanel">
            
            <table class="table tabel-dhuafa w-100 mb-0">
                <thead><tr><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach($data_tabel as $kk): 
                        $jiwa_dhuafa_di_kk = 0;
                        if($kk['bantuan'] && in_array('Ya', [$kk['bantuan']['bantuan_pusat'], $kk['bantuan']['bantuan_daerah'], $kk['bantuan']['bantuan_desa'], $kk['bantuan']['bantuan_kelompok']])) {
                            $jiwa_dhuafa_di_kk++;
                        }
                        foreach($kk['anggota'] as $a) {
                            if($a['bantuan_pusat'] == 'Ya' || $a['bantuan_daerah'] == 'Ya' || $a['bantuan_desa'] == 'Ya' || $a['bantuan_kelompok'] == 'Ya') {
                                $jiwa_dhuafa_di_kk++;
                            }
                        }

                        $family_view = [];
                        $family_view[] = [
                            'nama' => $kk['nama_lengkap'], 'status' => 'Kepala Keluarga',
                            'b_pusat' => $kk['bantuan']['bantuan_pusat'] ?? '', 'b_daerah' => $kk['bantuan']['bantuan_daerah'] ?? '',
                            'b_desa' => $kk['bantuan']['bantuan_desa'] ?? '', 'b_kelompok' => $kk['bantuan']['bantuan_kelompok'] ?? ''
                        ];
                        foreach($kk['anggota'] as $ang) {
                            $family_view[] = [
                                'nama' => $ang['nama_lengkap'], 'status' => $ang['status_keluarga'],
                                'b_pusat' => $ang['bantuan_pusat'] ?? '', 'b_daerah' => $ang['bantuan_daerah'] ?? '',
                                'b_desa' => $ang['bantuan_desa'] ?? '', 'b_kelompok' => $ang['bantuan_kelompok'] ?? ''
                            ];
                        }
                        $json_view = htmlspecialchars(json_encode($family_view), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td>
                            <div class="dhuafa-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Keluarga Bpk. <?= strtoupper($kk['nama_lengkap']); ?></h6>
                                        <div class="text-muted" style="font-size:0.75rem;"><i class="fa fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($kk['alamat_asal']); ?></div>
                                    </div>
                                    <?php if($jiwa_dhuafa_di_kk > 0): ?>
                                        <span class="badge bg-warning text-dark border border-warning shadow-sm"><i class="fa fa-check-circle me-1"></i><?= $jiwa_dhuafa_di_kk; ?> Penerima</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border shadow-sm"><i class="fa fa-hourglass-half me-1"></i>Menunggu</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-outline-success btn-sm flex-grow-1 fw-bold rounded-pill shadow-sm btn-expand" data-family='<?= $json_view; ?>'>
                                        <i class="fa fa-chevron-down me-1"></i> Rincian
                                    </button>
                                    <?php if($can_edit): ?>
                                        <button class="btn btn-dark btn-sm flex-grow-1 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $kk['id_user']; ?>">
                                            <i class="fa fa-edit me-1"></i> Edit Centang
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <?php if($can_edit): ?>
                    <div class="modal fade" id="modalEdit<?= $kk['id_user']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                                <div class="modal-header bg-dark text-white border-0 p-3">
                                    <h6 class="modal-title fw-bold" style="font-size:0.9rem;"><i class="fa fa-edit text-warning me-2"></i>Update Bantuan Keluarga</h6>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body p-3 bg-white">
                                        <div class="alert alert-warning border-warning small shadow-sm p-2 mb-3" style="font-size:0.75rem;">
                                            <i class="fa fa-info-circle me-1"></i> Centang jika menerima bantuan. Biarkan kosong semua untuk menghapus keluarga ini dari daftar Dhuafa.
                                        </div>
                                        
                                        <?php 
                                        $semua_keluarga = array_merge([$kk], $kk['anggota']);
                                        foreach($semua_keluarga as $m): 
                                            $is_kk_status = ($m['status_keluarga'] == 'Kepala Keluarga');
                                            $badge_role = $is_kk_status ? 'bg-primary' : (($m['status_keluarga'] == 'Istri') ? 'bg-danger' : 'bg-info text-dark');
                                            $bantuan = $is_kk_status ? $m['bantuan'] : $m; // sumber data bantuan berbeda array strukturnya
                                        ?>
                                        <div class="member-edit-box shadow-sm">
                                            <input type="hidden" name="id_user[]" value="<?= $m['id_user']; ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                                <span class="fw-bold text-dark" style="font-size:0.85rem;"><?= strtoupper($m['nama_lengkap']); ?></span>
                                                <span class="badge <?= $badge_role; ?>"><?= $m['status_keluarga']; ?></span>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between px-2">
                                                <div class="cb-wrapper">
                                                    <input type="checkbox" name="bantuan_pusat[<?= $m['id_user']; ?>]" class="form-check-input border-secondary" value="Ya" <?= (isset($bantuan['bantuan_pusat']) && $bantuan['bantuan_pusat'] == 'Ya') ? 'checked' : ''; ?>>
                                                    <span class="cb-label">PUSAT</span>
                                                </div>
                                                <div class="cb-wrapper">
                                                    <input type="checkbox" name="bantuan_daerah[<?= $m['id_user']; ?>]" class="form-check-input border-secondary" value="Ya" <?= (isset($bantuan['bantuan_daerah']) && $bantuan['bantuan_daerah'] == 'Ya') ? 'checked' : ''; ?>>
                                                    <span class="cb-label">DAERAH</span>
                                                </div>
                                                <div class="cb-wrapper">
                                                    <input type="checkbox" name="bantuan_desa[<?= $m['id_user']; ?>]" class="form-check-input border-secondary" value="Ya" <?= (isset($bantuan['bantuan_desa']) && $bantuan['bantuan_desa'] == 'Ya') ? 'checked' : ''; ?>>
                                                    <span class="cb-label">DESA</span>
                                                </div>
                                                <div class="cb-wrapper">
                                                    <input type="checkbox" name="bantuan_kelompok[<?= $m['id_user']; ?>]" class="form-check-input border-secondary" value="Ya" <?= (isset($bantuan['bantuan_kelompok']) && $bantuan['bantuan_kelompok'] == 'Ya') ? 'checked' : ''; ?>>
                                                    <span class="cb-label">KELOMPOK</span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                    </div>
                                    <div class="modal-footer bg-light border-0 p-2 d-flex">
                                        <button type="button" class="btn btn-light rounded-pill fw-bold flex-grow-1 border" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="simpan_dhuafa" class="btn btn-success rounded-pill fw-bold shadow-sm flex-grow-1"><i class="fa fa-save me-1"></i> Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $first_k = false; endforeach; ?>
    </div>
</div>

<?php if($can_edit): ?>
<div class="modal fade" id="modalTambahDhuafa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-success text-white border-0 p-3">
                <h6 class="modal-title fw-bold"><i class="fa fa-plus-circle me-2"></i>Daftarkan KK Dhuafa</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4 bg-white">
                    <label class="form-label fw-bold text-success small">Pilih Kepala Keluarga:</label>
                    <select name="id_user[]" class="form-select border-success shadow-sm" required>
                        <option value="" disabled selected>-- Pilih Kepala Keluarga --</option>
                        <?php foreach($non_dhuafa_families as $non): ?>
                            <option value="<?= $non['id_user']; ?>">Bpk. <?= $non['nama_lengkap']; ?> (Kel. <?= $non['kelompok']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="bantuan_kelompok[0]" id="dummy_input" value="Masuk Daftar">
                </div>
                <div class="modal-footer bg-light border-0 p-2 d-flex">
                    <button type="button" class="btn btn-light rounded-pill fw-bold flex-grow-1 border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_dhuafa" onclick="document.getElementById('dummy_input').name = 'bantuan_kelompok['+document.querySelector('select[name=\'id_user[]\']').value+']';" class="btn btn-success fw-bold rounded-pill shadow-sm flex-grow-1">
                        <i class="fa fa-save"></i> Tambahkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('.tabel-dhuafa').DataTable({ 
        "language": { "search": "", "searchPlaceholder": "Cari nama bapak..." },
        "pageLength": 25,
        "ordering": false,
        "dom": '<"row"<"col-12"f>>rt<"row"<"col-12"p>>'
    });

    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        $('.nav-link .badge-k').removeClass('bg-white text-success').addClass('bg-success text-white');
        $(this).find('.badge-k').removeClass('bg-success text-white').addClass('bg-white text-success');
    });

    // MESIN LACI OTOMATIS: DIDESAIN COMPACT UNTUK HP
    $('.tabel-dhuafa tbody').on('click', '.btn-expand', function () {
        var tr = $(this).closest('tr');
        var dt = $(this).closest('table').DataTable();
        var row = dt.row(tr);
        var btn = $(this);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            btn.html('<i class="fa fa-chevron-down me-1"></i> Rincian');
            btn.removeClass('btn-danger text-white').addClass('btn-outline-success');
        } else {
            var family = JSON.parse(btn.attr('data-family'));
            var iconCheck = '<i class="fa fa-check-circle text-success"></i>';
            var iconCross = '<i class="fa fa-minus text-muted opacity-25"></i>';

            var html = '<div class="px-3 pb-3 bg-light border-bottom border-success border-opacity-25">';
            var dhuafaCount = 0;
            
            family.forEach(function(m) {
                if(m.b_pusat === 'Ya' || m.b_daerah === 'Ya' || m.b_desa === 'Ya' || m.b_kelompok === 'Ya' || m.status === 'Kepala Keluarga') {
                    
                    html += '<div class="border-bottom border-secondary border-opacity-25 py-2">';
                    html += '<div class="fw-bold text-dark d-flex justify-content-between align-items-center" style="font-size:0.8rem;">' + m.nama.toUpperCase() + ' <span class="badge bg-secondary" style="font-size:0.6rem;">' + m.status + '</span></div>';
                    html += '<div class="d-flex justify-content-between mt-2 px-1" style="font-size:0.75rem; font-weight:600; color:#64748b;">';
                    html += '<span>Pus: ' + (m.b_pusat === 'Ya' ? iconCheck : iconCross) + '</span>';
                    html += '<span>Dae: ' + (m.b_daerah === 'Ya' ? iconCheck : iconCross) + '</span>';
                    html += '<span>Des: ' + (m.b_desa === 'Ya' ? iconCheck : iconCross) + '</span>';
                    html += '<span>Kel: ' + (m.b_kelompok === 'Ya' ? iconCheck : iconCross) + '</span>';
                    html += '</div></div>';
                    dhuafaCount++;
                }
            });
            
            if(dhuafaCount === 1 && family[0].b_pusat !== 'Ya' && family[0].b_daerah !== 'Ya' && family[0].b_desa !== 'Ya' && family[0].b_kelompok !== 'Ya') {
                 html += '<div class="text-muted fst-italic text-center py-2" style="font-size:0.8rem;">Belum ada anggota yang dicentang menerima bantuan.</div>';
            }
            html += '</div>';
            
            row.child(html).show();
            tr.addClass('shown');
            btn.html('<i class="fa fa-chevron-up me-1"></i> Tutup');
            btn.removeClass('btn-outline-success').addClass('btn-danger text-white');
        }
    });
});
</script>
</body>
</html>