<?php
session_start();
include 'koneksi.php';

// 1. PROTEKSI HAK AKSES (Hanya Keimaman & Tim Dhuafa)
$allowed_levels = ['superadmin', 'admin_desa', 'keimaman_desa', 'keimaman', 'tim_dhuafa_desa', 'tim_dhuafa'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Akses Ditolak! Khusus Keimaman dan Tim Dhuafa.'); window.location='dashboard.php';</script>";
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
    $id_users = $_POST['id_user']; // Array
    $b_pusat = $_POST['bantuan_pusat'];
    $b_daerah = $_POST['bantuan_daerah'];
    $b_desa = $_POST['bantuan_desa'];
    $b_kelompok = $_POST['bantuan_kelompok'];

    foreach($id_users as $uid) {
        $bp = mysqli_real_escape_string($conn, $b_pusat[$uid]);
        $bda = mysqli_real_escape_string($conn, $b_daerah[$uid]);
        $bde = mysqli_real_escape_string($conn, $b_desa[$uid]);
        $bk = mysqli_real_escape_string($conn, $b_kelompok[$uid]);

        // Jika semua kolom kosong, hapus dari daftar dhuafa
        if(empty($bp) && empty($bda) && empty($bde) && empty($bk)) {
            mysqli_query($conn, "DELETE FROM data_dhuafa WHERE id_user = '$uid'");
        } else {
            // Insert atau Update jika sudah ada
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
    
    // Cek Bantuan KK
    $q_dhuafa_kk = mysqli_query($conn, "SELECT * FROM data_dhuafa WHERE id_user = '$id_kk'");
    $kk['bantuan'] = mysqli_fetch_assoc($q_dhuafa_kk);
    if($kk['bantuan']) { $is_family_dhuafa = true; $total_jiwa_terbantu++; }

    // Ambil Anggota & Bantuan Mereka
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
            $total_jiwa_terbantu++;
        }
    }
    
    // Pisahkan yang dhuafa dan bukan dhuafa
    if($is_family_dhuafa) {
        $dhuafa_families[$kel_name][] = $kk;
    } else {
        $non_dhuafa_families[] = $kk; // Untuk dropdown tambah data
    }
}

$list_kelompok = array_keys($dhuafa_families);
sort($list_kelompok);

// Hitung total KK Dhuafa
$total_kk_dhuafa = 0;
foreach($dhuafa_families as $kel => $kks) { $total_kk_dhuafa += count($kks); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dhuafa | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .nav-pills .nav-link { border-radius: 10px; color: #1a535c; background: #fff; margin-right: 10px; font-weight: bold; border: 1px solid #dee2e6; padding: 10px 20px; }
        .nav-pills .nav-link.active { background-color: #1a535c; color: #fff; border-color: #1a535c; }
        td.details-control { cursor: pointer; }
        .shadow-inner { box-shadow: inset 0 0 10px rgba(0,0,0,0.05); }
        .form-control-sm { border: 1px solid #ced4da; font-size: 0.8rem; }
        .form-control-sm:focus { border-color: #4ecdc4; box-shadow: none; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa fa-hand-holding-heart text-success me-2"></i>Data Jamaah Dhuafa</h2>
            <small class="text-muted fw-bold">Manajemen Distribusi Zakat & Bantuan Sosial <?= $is_pusat ? 'Se-Desa' : 'Kel. '.$kel_user; ?></small>
        </div>
        <?php if($can_edit): ?>
            <button class="btn btn-success fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahDhuafa">
                <i class="fa fa-plus-circle me-1"></i> Tambah KK Dhuafa
            </button>
        <?php endif; ?>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 col-xl-4 mb-3 mb-xl-0">
            <div class="card card-custom bg-white p-4 shadow-sm border-start border-warning border-5 d-flex flex-row align-items-center">
                <i class="fa fa-home fa-3x text-warning me-3 opacity-50"></i>
                <div>
                    <h6 class="text-muted fw-bold mb-1">Total Keluarga (KK)</h6>
                    <h2 class="fw-bold text-dark mb-0"><?= $total_kk_dhuafa; ?> <span class="fs-6 text-muted">KK Terbantu</span></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card card-custom bg-white p-4 shadow-sm border-start border-success border-5 d-flex flex-row align-items-center">
                <i class="fa fa-users fa-3x text-success me-3 opacity-50"></i>
                <div>
                    <h6 class="text-muted fw-bold mb-1">Total Jiwa Dhuafa</h6>
                    <h2 class="fw-bold text-dark mb-0"><?= $total_jiwa_terbantu; ?> <span class="fs-6 text-muted">Orang</span></h2>
                </div>
            </div>
        </div>
    </div>

    <?php if($is_pusat && count($list_kelompok) > 0): ?>
        <ul class="nav nav-pills mb-4 overflow-auto flex-nowrap pb-2" id="pills-kelompok" role="tablist">
            <?php $first_k = true; foreach($list_kelompok as $k): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $first_k ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-<?= md5($k); ?>" type="button">
                        KEL. <?= strtoupper($k); ?>
                        <span class="badge <?= $first_k ? 'bg-white text-dark' : 'bg-success text-white'; ?> ms-1 rounded-pill badge-k"><?= count($dhuafa_families[$k]); ?></span>
                    </button>
                </li>
            <?php $first_k = false; endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="tab-content">
        <?php 
        if(count($list_kelompok) == 0): 
            echo '<div class="alert alert-warning text-center fw-bold shadow-sm p-4"><i class="fa fa-info-circle fa-2x d-block mb-2 text-warning"></i>Alhamdulillah, belum ada data jamaah dhuafa di wilayah ini.</div>';
        endif;

        $first_k = true; 
        foreach($list_kelompok as $k): 
            $data_tabel = $dhuafa_families[$k];
        ?>
        <div class="tab-pane fade <?= $first_k ? 'show active' : ''; ?>" id="tab-<?= md5($k); ?>" role="tabpanel">
            <div class="card card-custom p-4 bg-white shadow-sm border-top border-success border-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa fa-list-alt text-success me-2"></i>Daftar Penerima Bantuan - KEL. <?= strtoupper($k); ?></h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center tabel-dhuafa" style="width:100%">
                        <thead class="table-success text-dark">
                            <tr>
                                <th width="5%">NO</th>
                                <th class="text-start">NAMA KEPALA KELUARGA</th>
                                <th>STATUS</th>
                                <th>DETAIL BANTUAN</th>
                                <?php if($can_edit): ?><th>KELOLA</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach($data_tabel as $kk): 
                                // Menghitung berapa orang di KK ini yang dapat bantuan
                                $jiwa_dhuafa_di_kk = ($kk['bantuan']) ? 1 : 0;
                                foreach($kk['anggota'] as $a) {
                                    if(!empty($a['bantuan_pusat']) || !empty($a['bantuan_daerah']) || !empty($a['bantuan_desa']) || !empty($a['bantuan_kelompok'])) {
                                        $jiwa_dhuafa_di_kk++;
                                    }
                                }

                                // Menyusun data JSON untuk Laci (View Only)
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
                                <td class="fw-bold"><?= $no++; ?></td>
                                <td class="text-start">
                                    <span class="fw-bold text-dark fs-6"><?= strtoupper($kk['nama_lengkap']); ?></span><br>
                                    <small class="text-muted"><i class="fa fa-map-marker-alt text-danger me-1"></i> <?= $kk['alamat_asal']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark border border-warning px-3 py-2 rounded-pill">
                                        <i class="fa fa-hands-helping me-1"></i> <?= $jiwa_dhuafa_di_kk; ?> Orang Terbantu
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-outline-success btn-sm fw-bold shadow-sm rounded-pill px-3 btn-expand" data-family='<?= $json_view; ?>'>
                                        <i class="fa fa-chevron-down me-1"></i> Lihat Rincian
                                    </button>
                                </td>
                                
                                <?php if($can_edit): ?>
                                <td>
                                    <button class="btn btn-dark btn-sm fw-bold shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $kk['id_user']; ?>">
                                        <i class="fa fa-edit me-1"></i> Update Bantuan
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>

                            <?php if($can_edit): ?>
                            <div class="modal fade" id="modalEdit<?= $kk['id_user']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-xl modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                                        <div class="modal-header bg-dark text-white border-0 p-3">
                                            <h5 class="modal-title fw-bold"><i class="fa fa-edit text-warning me-2"></i>Update Bantuan: Keluarga Bpk. <?= strtoupper($kk['nama_lengkap']); ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body p-0 bg-light">
                                                <div class="alert alert-warning m-3 border-warning small shadow-sm">
                                                    <i class="fa fa-info-circle me-1"></i> Isi jenis bantuan (misal: PKH, BLT, Sembako) pada kolom yang tersedia. Jika anggota keluarga tidak mendapat bantuan, <b>kosongkan saja</b>. Jika semua kolom kosong, keluarga ini akan otomatis keluar dari daftar dhuafa.
                                                </div>
                                                <div class="table-responsive px-3 pb-3">
                                                    <table class="table table-bordered bg-white text-center align-middle mb-0" style="font-size: 0.85rem;">
                                                        <thead class="table-secondary">
                                                            <tr>
                                                                <th class="text-start">NAMA ANGGOTA</th>
                                                                <th>BANTUAN PUSAT</th>
                                                                <th>BANTUAN DAERAH</th>
                                                                <th>BANTUAN DESA</th>
                                                                <th>BANTUAN KELOMPOK</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-start fw-bold text-primary">
                                                                    <input type="hidden" name="id_user[]" value="<?= $kk['id_user']; ?>">
                                                                    <?= strtoupper($kk['nama_lengkap']); ?><br>
                                                                    <span class="badge bg-primary mt-1">Kepala Keluarga</span>
                                                                </td>
                                                                <td><input type="text" name="bantuan_pusat[<?= $kk['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $kk['bantuan']['bantuan_pusat'] ?? ''; ?>" placeholder="-"></td>
                                                                <td><input type="text" name="bantuan_daerah[<?= $kk['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $kk['bantuan']['bantuan_daerah'] ?? ''; ?>" placeholder="-"></td>
                                                                <td><input type="text" name="bantuan_desa[<?= $kk['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $kk['bantuan']['bantuan_desa'] ?? ''; ?>" placeholder="-"></td>
                                                                <td><input type="text" name="bantuan_kelompok[<?= $kk['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $kk['bantuan']['bantuan_kelompok'] ?? ''; ?>" placeholder="-"></td>
                                                            </tr>
                                                            <?php foreach($kk['anggota'] as $ang): ?>
                                                            <tr>
                                                                <td class="text-start fw-bold text-dark">
                                                                    <input type="hidden" name="id_user[]" value="<?= $ang['id_user']; ?>">
                                                                    <?= strtoupper($ang['nama_lengkap']); ?><br>
                                                                    <span class="badge <?= ($ang['status_keluarga']=='Istri') ? 'bg-danger' : 'bg-info text-dark'; ?> mt-1"><?= $ang['status_keluarga']; ?></span>
                                                                </td>
                                                                <td><input type="text" name="bantuan_pusat[<?= $ang['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $ang['bantuan_pusat'] ?? ''; ?>" placeholder="-"></td>
                                                                <td><input type="text" name="bantuan_daerah[<?= $ang['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $ang['bantuan_daerah'] ?? ''; ?>" placeholder="-"></td>
                                                                <td><input type="text" name="bantuan_desa[<?= $ang['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $ang['bantuan_desa'] ?? ''; ?>" placeholder="-"></td>
                                                                <td><input type="text" name="bantuan_kelompok[<?= $ang['id_user']; ?>]" class="form-control form-control-sm text-center" value="<?= $ang['bantuan_kelompok'] ?? ''; ?>" placeholder="-"></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 bg-white">
                                                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="simpan_dhuafa" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"><i class="fa fa-save me-1"></i> Simpan Perubahan</button>
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
            </div>
        </div>
        <?php $first_k = false; endforeach; ?>
    </div>
</div>

<?php if($can_edit): ?>
<div class="modal fade" id="modalTambahDhuafa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-success text-white border-0 p-3">
                <h5 class="modal-title fw-bold"><i class="fa fa-plus-circle me-2"></i>Masukkan Keluarga ke Daftar Dhuafa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4 bg-white">
                    <div class="alert alert-light border-success border-2 shadow-sm text-dark mb-4 small">
                        Pilih nama <b>Kepala Keluarga</b> yang akan dimasukkan ke dalam daftar asnaf/dhuafa. Setelah tersimpan, Anda dapat merincikan bantuannya di tabel utama.
                    </div>
                    <label class="form-label fw-bold text-success">Pilih Kepala Keluarga:</label>
                    <select name="id_user[]" class="form-select border-success border-2" required>
                        <option value="" disabled selected>-- Pilih Kepala Keluarga --</option>
                        <?php foreach($non_dhuafa_families as $non): ?>
                            <option value="<?= $non['id_user']; ?>">Bpk. <?= $non['nama_lengkap']; ?> (Kel. <?= $non['kelompok']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="hidden" name="bantuan_kelompok[0]" id="dummy_input" value="Masuk Daftar">
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_dhuafa" onclick="document.getElementById('dummy_input').name = 'bantuan_kelompok['+document.querySelector('select[name=\'id_user[]\']').value+']';" class="btn btn-success fw-bold rounded-pill shadow-sm px-4">
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
    // Inisialisasi DataTables
    var tables = $('.tabel-dhuafa').DataTable({ 
        "language": { "search": "Cari Nama KK:", "lengthMenu": "Tampil _MENU_ Data" },
        "pageLength": 25,
        "ordering": false
    });

    // Perbaiki UI Tab & Badge
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        $('.nav-link .badge-k').removeClass('bg-white text-dark').addClass('bg-success text-white');
        $(this).find('.badge-k').removeClass('bg-success text-white').addClass('bg-white text-dark');
    });

    // MESIN LACI OTOMATIS UNTUK MELIHAT RINCIAN BANTUAN (VIEW ONLY)
    $('.tabel-dhuafa tbody').on('click', '.btn-expand', function () {
        var tr = $(this).closest('tr');
        var dt = $(this).closest('table').DataTable();
        var row = dt.row(tr);
        var btn = $(this);

        if (row.child.isShown()) {
            // Tutup Laci
            row.child.hide();
            tr.removeClass('shown');
            tr.css('background-color', '');
            btn.html('<i class="fa fa-chevron-down me-1"></i> Lihat Rincian');
            btn.removeClass('btn-danger').addClass('btn-outline-success');
        } else {
            // Buka Laci
            var family = JSON.parse(btn.attr('data-family'));
            
            var html = '<div class="p-3 bg-light border border-success rounded shadow-inner ms-4">';
            html += '<h6 class="fw-bold text-success mb-3"><i class="fa fa-box-open me-2"></i>Rincian Distribusi Bantuan Keluarga</h6>';
            html += '<table class="table table-sm table-bordered bg-white text-center align-middle mb-0" style="font-size:0.85rem;">';
            html += '<thead><tr class="table-success text-dark"><th class="text-start">NAMA LENGKAP</th><th>HUBUNGAN</th><th>B. PUSAT</th><th>B. DAERAH</th><th>B. DESA</th><th>B. KELOMPOK</th></tr></thead><tbody>';
            
            var dhuafaCount = 0;
            family.forEach(function(m) {
                // Hanya cetak baris jika orang ini menerima minimal 1 bantuan (Atau jika dia KK)
                if(m.b_pusat !== '' || m.b_daerah !== '' || m.b_desa !== '' || m.b_kelompok !== '' || m.status === 'Kepala Keluarga') {
                    var badgeClass = (m.status === 'Kepala Keluarga') ? 'bg-primary' : ((m.status === 'Istri') ? 'bg-danger' : 'bg-info text-dark');
                    
                    html += '<tr>';
                    html += '<td class="text-start fw-bold ps-3">' + m.nama.toUpperCase() + '</td>';
                    html += '<td><span class="badge ' + badgeClass + '">' + m.status + '</span></td>';
                    html += '<td class="text-dark fw-bold">' + (m.b_pusat || '<span class="text-muted fw-normal">-</span>') + '</td>';
                    html += '<td class="text-dark fw-bold">' + (m.b_daerah || '<span class="text-muted fw-normal">-</span>') + '</td>';
                    html += '<td class="text-dark fw-bold">' + (m.b_desa || '<span class="text-muted fw-normal">-</span>') + '</td>';
                    html += '<td class="text-dark fw-bold">' + (m.b_kelompok || '<span class="text-muted fw-normal">-</span>') + '</td>';
                    html += '</tr>';
                    dhuafaCount++;
                }
            });
            
            if(dhuafaCount === 1 && family[0].b_pusat === '' && family[0].b_daerah === '' && family[0].b_desa === '' && family[0].b_kelompok === '') {
                 html += '<tr><td colspan="6" class="text-muted fst-italic py-3">Belum ada rincian bantuan yang diinput untuk keluarga ini.</td></tr>';
            }
            
            html += '</tbody></table></div>';
            
            row.child(html).show();
            tr.addClass('shown');
            tr.css('background-color', '#ebfaec'); 
            btn.html('<i class="fa fa-chevron-up me-1"></i> Tutup Rincian');
            btn.removeClass('btn-outline-success').addClass('btn-danger');
        }
    });
});
</script>
</body>
</html>