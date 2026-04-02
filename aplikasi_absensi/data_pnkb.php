<?php
session_start();
include 'koneksi.php';

// Pastikan hanya Pengawas & Tim PNKB yang bisa masuk
$allowed_levels = ['superadmin', 'admin_desa', 'keimaman_desa', 'keimaman', 'ketua_mudai', 'tim_pnkb_desa', 'tim_pnkb'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>"; exit;
}

$level = $_SESSION['level'];
$kel_user = $_SESSION['kelompok'] ?? '';
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'tim_pnkb_desa']);

// 1. FILTER WILAYAH
if ($is_pusat || $kel_user == 'Semua' || empty($kel_user)) {
    $filter_wilayah = "1=1";
} else {
    $filter_wilayah = "u.kelompok = '$kel_user'";
}

// 2. VARIABEL FILTER DARI URL
$f_umur   = $_GET['f_umur'] ?? '';
$f_target = $_GET['f_target'] ?? '';
$f_gender = $_GET['f_gender'] ?? '';

// 3. TARIK DATA UTAMA (KHUSUS MUDA/I)
$query_muda_mudi = mysqli_query($conn, "
    SELECT b.*, u.kelompok 
    FROM biodata_jamaah b 
    JOIN users u ON b.id_user = u.id_user 
    WHERE b.jenjang = 'Muda/i' 
    AND ($filter_wilayah)
    ORDER BY u.kelompok ASC, b.nama_lengkap ASC
");

$muda_mudi_list = [];
$count_L = 0;
$count_P = 0;
$rekap_kelompok = [];

while($row = mysqli_fetch_assoc($query_muda_mudi)) {
    // Hitung Umur
    $umur = 0;
    if(!empty($row['tanggal_lahir']) && $row['tanggal_lahir'] != '0000-00-00') {
        $umur = (new DateTime($row['tanggal_lahir']))->diff(new DateTime('today'))->y;
    }
    $row['umur_angka'] = $umur;

    // APLIKASIKAN FILTER
    if(!empty($f_gender) && $row['jenis_kelamin'] != $f_gender) continue;
    if(!empty($f_target) && $row['target_menikah'] != $f_target) continue;
    if(!empty($f_umur)) {
        if($f_umur == '20_kebawah' && $umur > 20) continue;
        if($f_umur == '21_25' && ($umur < 21 || $umur > 25)) continue;
        if($f_umur == '26_30' && ($umur < 26 || $umur > 30)) continue;
        if($f_umur == '31_keatas' && $umur < 31) continue;
    }

    // HITUNG STATISTIK GENDER
    if($row['jenis_kelamin'] == 'L') $count_L++;
    if($row['jenis_kelamin'] == 'P') $count_P++;

    // HITUNG REKAP PER KELOMPOK
    $kel = $row['kelompok'];
    if(!isset($rekap_kelompok[$kel])) {
        $rekap_kelompok[$kel] = ['L' => 0, 'P' => 0, 'total' => 0];
    }
    $rekap_kelompok[$kel][$row['jenis_kelamin']]++;
    $rekap_kelompok[$kel]['total']++;

    $muda_mudi_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Data Ta'aruf & PNKB | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
        .card-stats { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .filter-box { background: white; border-radius: 15px; padding: 20px; margin-bottom: 30px; border: 1px solid #dee2e6; }
        .table-custom th { background-color: #f8f9fa; color: #1a535c; font-weight: bold; border-bottom: 2px solid #4ecdc4; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa fa-heart text-danger me-2"></i>Data PNKB -Pernikahan dan Keluarga Bahagia</h2>
            <small class="text-muted fw-bold">Pemetaan Data <b>Muda/i</b> Desa Semampir</small>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card card-stats bg-white p-3 border-start border-4 border-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-muted small fw-bold mb-1">TOTAL MUDA/I</h6><h3 class="fw-bold mb-0"><?= count($muda_mudi_list); ?></h3></div>
                    <i class="fa fa-users fa-2x text-dark opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stats bg-white p-3 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-info small fw-bold mb-1">(LAKI-LAKI)</h6><h3 class="fw-bold mb-0"><?= $count_L; ?></h3></div>
                    <i class="fa fa-male fa-2x text-info opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stats bg-white p-3 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-danger small fw-bold mb-1">(PEREMPUAN)</h6><h3 class="fw-bold mb-0"><?= $count_P; ?></h3></div>
                    <i class="fa fa-female fa-2x text-danger opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if($is_pusat): ?>
    <div class="card card-stats mb-4 border-0">
        <div class="card-header bg-dark text-white fw-bold py-3" style="border-radius: 15px 15px 0 0;">
            <i class="fa fa-sitemap me-2"></i>Ringkasan Jumlah per Kelompok
        </div>
        <div class="card-body bg-white">
            <div class="row row-cols-2 row-cols-md-4 g-3">
                <?php foreach($rekap_kelompok as $nama_kel => $val): ?>
                    <div class="col">
                        <div class="p-2 border rounded bg-light">
                            <small class="fw-bold d-block text-truncate"><?= $nama_kel; ?></small>
                            <span class="badge bg-info text-dark"><?= $val['L']; ?> L</span>
                            <span class="badge bg-danger"><?= $val['P']; ?> P</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="filter-box">
        <form action="" method="GET" class="row g-3 align-items-end">
            <div class="col-md-2 col-6">
                <label class="form-label small text-muted fw-bold mb-1">Gender</label>
                <select name="f_gender" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="L" <?= ($f_gender == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?= ($f_gender == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small text-muted fw-bold mb-1">Rentang Umur</label>
                <select name="f_umur" class="form-select form-select-sm">
                    <option value="">Semua Umur</option>
                    <option value="20_kebawah" <?= ($f_umur == '20_kebawah') ? 'selected' : ''; ?>>20 Thn ke Bawah</option>
                    <option value="21_25" <?= ($f_umur == '21_25') ? 'selected' : ''; ?>>21 - 25 Thn</option>
                    <option value="26_30" <?= ($f_umur == '26_30') ? 'selected' : ''; ?>>26 - 30 Thn</option>
                    <option value="31_keatas" <?= ($f_umur == '31_keatas') ? 'selected' : ''; ?>>31 Thn ke Atas</option>
                </select>
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label small text-muted fw-bold mb-1">Target Menikah</label>
                <select name="f_target" class="form-select form-select-sm">
                    <option value="">Semua Target</option>
                    <option value="Tahun Ini" <?= ($f_target == 'Tahun Ini') ? 'selected' : ''; ?>>Tahun Ini (Segera)</option>
                    <option value="1 Tahun Kedepan" <?= ($f_target == '1 Tahun Kedepan') ? 'selected' : ''; ?>>1 Tahun Kedepan</option>
                    <option value="2 Tahun Kedepan" <?= ($f_target == '2 Tahun Kedepan') ? 'selected' : ''; ?>>2 Tahun Kedepan</option>
                </select>
            </div>
            <div class="col-md-auto col-12 d-flex gap-2">
                <button type="submit" class="btn btn-dark btn-sm fw-bold px-4"><i class="fa fa-search"></i></button>
                <a href="data_pnkb.php" class="btn btn-light btn-sm border px-3"><i class="fa fa-sync"></i></a>
            </div>
        </form>
    </div>

    <div class="card card-stats bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th class="ps-4">NO</th>
                        <th>NAMA LENGKAP</th>
                        <th class="text-center">L/P</th>
                        <th class="text-center">UMUR</th>
                        <th>TARGET MENIKAH</th>
                        <th>ASAL / KEGIATAN</th>
                        <th class="pe-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($muda_mudi_list) == 0): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach($muda_mudi_list as $row): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?= $no++; ?></td>
                            <td>
                                <span class="fw-bold d-block text-dark"><?= strtoupper($row['nama_lengkap']); ?></span>
                                <small class="text-muted">Kel. <?= $row['kelompok']; ?></small>
                            </td>
                            <td class="text-center fw-bold <?= ($row['jenis_kelamin'] == 'P') ? 'text-danger' : 'text-info'; ?>"><?= $row['jenis_kelamin']; ?></td>
                            <td class="text-center fw-bold"><?= $row['umur_angka']; ?> Thn</td>
                            <td><span class="fw-bold text-dark"><?= $row['target_menikah'] ?: '-'; ?></span></td>
                            <td>
                                <small class="d-block text-truncate" style="max-width: 150px;"><i class="fa fa-map-marker-alt me-1 text-danger"></i><?= $row['alamat_asal'] ?: '-'; ?></small>
                                <small class="d-block text-truncate" style="max-width: 150px;"><i class="fa fa-briefcase me-1 text-primary"></i><?= $row['kegiatan_surabaya'] ?: '-'; ?></small>
                            </td>
                            <td class="pe-4 text-center">
                                <a href="detail_jamaah.php?id=<?= $row['id_user']; ?>" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold">Profil</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>