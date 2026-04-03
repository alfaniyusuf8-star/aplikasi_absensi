<?php
session_start();
include 'koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: data_jamaah.php");
    exit;
}

$id_biodata = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM biodata_jamaah WHERE id_biodata = '$id_biodata'");
$d = mysqli_fetch_assoc($query);

if (!$d) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='data_jamaah.php';</script>";
    exit;
}

// Menentukan warna badge jenjang
$warna_badge = 'secondary';
if($d['jenjang'] == 'Muda/i') $warna_badge = 'success';
if($d['jenjang'] == 'Umum') $warna_badge = 'primary';
if($d['jenjang'] == 'Remaja') $warna_badge = 'info text-dark';
if($d['jenjang'] == 'Pra Remaja') $warna_badge = 'danger';
if($d['jenjang'] == 'Caberawit') $warna_badge = 'warning text-dark';

// Logika Nasab (Bin / Binti) khusus Anak-anak
$nama_tampil = $d['nama_lengkap'];
$nasab_tampil = "";
if (in_array($d['jenjang'], ['Remaja', 'Pra Remaja', 'Caberawit']) && !empty($d['nama_ortu'])) {
    $nasab = ($d['jenis_kelamin'] == 'L') ? 'bin' : 'binti';
    $nasab_tampil = "<div class='text-muted fs-6 fst-italic mt-1'>$nasab " . $d['nama_ortu'] . "</div>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Biodata | AbsenNgaji</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#1a535c">
<link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/3652/3652191.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .foto-profil { width: 100%; max-width: 250px; border-radius: 15px; object-fit: cover; aspect-ratio: 3/4; border: 3px solid #eee; }
        .detail-label { font-size: 0.85rem; color: #6c757d; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-size: 1.1rem; font-weight: 500; color: #2b2d42; margin-bottom: 15px; border-bottom: 1px dashed #ddd; padding-bottom: 5px; }
    </style>
</head>
<body class="py-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa fa-id-card text-primary me-2"></i>Detail Biodata Jamaah</h3>
        </div>
        <a href="data_jamaah.php" class="btn btn-outline-dark fw-bold rounded-pill px-4"><i class="fa fa-arrow-left me-2"></i>Kembali</a>
    </div>

    <div class="row g-4">
        <div class="col-md-4 col-lg-3 text-center">
            <div class="card card-custom p-3 bg-white">
                <?php if (!empty($d['foto']) && file_exists("uploads/" . $d['foto'])): ?>
                    <img src="uploads/<?= $d['foto']; ?>" alt="Foto" class="foto-profil shadow-sm mb-3">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center flex-column shadow-sm mb-3" style="width: 100%; aspect-ratio: 3/4; border-radius: 15px; border: 3px solid #eee;">
                        <i class="fa fa-user-circle fa-5x text-secondary opacity-50 mb-2"></i>
                        <span class="text-muted small">Tidak ada foto</span>
                    </div>
                <?php endif; ?>
                
                <span class="badge bg-<?= $warna_badge; ?> fs-6 mb-2"><?= $d['jenjang']; ?></span>
                <h5 class="fw-bold text-dark mb-0"><?= $d['nama_panggilan']; ?></h5>
            </div>
        </div>

        <div class="col-md-8 col-lg-9">
            <div class="card card-custom p-4 bg-white mb-4">
                <h5 class="fw-bold text-primary mb-4"><i class="fa fa-info-circle me-2"></i>Informasi Dasar</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Nama Lengkap</div>
                        <div class="detail-value">
                            <?= $nama_tampil; ?>
                            <?= $nasab_tampil; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Jenis Kelamin</div>
                        <div class="detail-value"><?= ($d['jenis_kelamin'] == 'L') ? 'Laki-Laki' : 'Perempuan'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">No. HP / WhatsApp</div>
                        <div class="detail-value">
                            <?php if(!empty($d['no_hp'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $d['no_hp']); ?>" target="_blank" class="text-success text-decoration-none">
                                    <i class="fab fa-whatsapp me-1"></i> <?= $d['no_hp']; ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Alamat Asal / KTP</div>
                        <div class="detail-value"><?= nl2br($d['alamat_asal']); ?></div>
                    </div>
                </div>
            </div>

            <?php if ($d['jenjang'] != 'Umum'): ?>
            <div class="card card-custom p-4 bg-white mb-4 border-top border-success border-4">
                <h5 class="fw-bold text-success mb-4"><i class="fa fa-user-graduate me-2"></i>Data Lanjutan (<?= $d['jenjang']; ?>)</h5>
                
                <?php if ($d['jenjang'] == 'Muda/i'): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Tempat, Tanggal Lahir</div>
                        <div class="detail-value">
                            <?= !empty($d['tempat_lahir']) ? $d['tempat_lahir'] : '-'; ?>, 
                            <?= !empty($d['tanggal_lahir']) ? date('d M Y', strtotime($d['tanggal_lahir'])) : '-'; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Status Kemubalighan</div>
                        <div class="detail-value"><?= !empty($d['status_mubaligh']) ? $d['status_mubaligh'] : '-'; ?></div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Alamat di Surabaya</div>
                        <div class="detail-value"><?= !empty($d['alamat_surabaya']) ? nl2br($d['alamat_surabaya']) : '-'; ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <h6 class="fw-bold text-dark mt-4 border-bottom pb-2">Informasi Orang Tua (Ayah)</h6>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="detail-label">Nama Ayah</div>
                        <div class="detail-value"><?= !empty($d['nama_ortu']) ? $d['nama_ortu'] : '-'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">No. HP Orang Tua</div>
                        <div class="detail-value"><?= !empty($d['hp_ortu']) ? $d['hp_ortu'] : '-'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Status Sambung Ortu</div>
                        <div class="detail-value">
                            <?= !empty($d['status_ortu']) ? $d['status_ortu'] : '-'; ?> 
                            <?php if($d['jenjang'] == 'Muda/i' && !empty($d['tempat_sambung_ortu'])) echo '('.$d['tempat_sambung_ortu'].')'; ?>
                        </div>
                    </div>
                </div>

                <?php if ($d['jenjang'] == 'Muda/i'): ?>
                <h6 class="fw-bold text-dark mt-4 border-bottom pb-2">Kontak Darurat di Surabaya</h6>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="detail-label">Nama Kontak</div>
                        <div class="detail-value"><?= !empty($d['darurat_nama']) ? $d['darurat_nama'] : '-'; ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">Hubungan</div>
                        <div class="detail-value"><?= !empty($d['darurat_hubungan']) ? $d['darurat_hubungan'] : '-'; ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">No. HP Darurat</div>
                        <div class="detail-value text-danger fw-bold"><?= !empty($d['darurat_hp']) ? $d['darurat_hp'] : '-'; ?></div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mt-4 border-bottom pb-2">Kegiatan (<?= $d['kegiatan_surabaya'] ?? 'Belum Diisi'; ?>)</h6>
                <div class="row mt-3">
                    <?php if($d['kegiatan_surabaya'] == 'Kuliah'): ?>
                        <div class="col-md-4"><div class="detail-label">Universitas</div><div class="detail-value"><?= !empty($d['universitas']) ? $d['universitas'] : '-'; ?></div></div>
                        <div class="col-md-4"><div class="detail-label">Jurusan</div><div class="detail-value"><?= !empty($d['jurusan']) ? $d['jurusan'] : '-'; ?></div></div>
                        <div class="col-md-4"><div class="detail-label">Angkatan</div><div class="detail-value"><?= !empty($d['angkatan']) ? $d['angkatan'] : '-'; ?></div></div>
                    <?php elseif($d['kegiatan_surabaya'] == 'Bekerja'): ?>
                        <div class="col-md-6"><div class="detail-label">Tempat Kerja</div><div class="detail-value"><?= !empty($d['tempat_kerja']) ? $d['tempat_kerja'] : '-'; ?></div></div>
                        <div class="col-md-6"><div class="detail-label">Alamat Tempat Kerja</div><div class="detail-value"><?= !empty($d['alamat_kerja']) ? $d['alamat_kerja'] : '-'; ?></div></div>
                    <?php else: ?>
                        <div class="col-12 text-muted fst-italic">Informasi kampus/pekerjaan tidak diisi.</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="detail-label">Hobi / Minat</div>
                        <div class="detail-value"><?= !empty($d['hobi']) ? $d['hobi'] : '-'; ?></div>
                    </div>
                </div>

            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>