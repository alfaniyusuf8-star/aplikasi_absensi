<?php
session_start();
include 'koneksi.php';

// Pastikan hanya admin/pengurus yang bisa melihat
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman_desa', 'keimaman', 'ketua_mudai', 'tim_pnkb_desa', 'tim_pnkb'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>"; 
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: data_jamaah.php");
    exit;
}

$id_detail = mysqli_real_escape_string($conn, $_GET['id']);

// AMBIL DATA JAMAAH & NAMA KEPALA KELUARGA (JIKA ADA)
$query = mysqli_query($conn, "
    SELECT b.*, u.kelompok, u.username, u.level,
    (SELECT nama_lengkap FROM biodata_jamaah WHERE id_user = b.id_kepala_keluarga) as nama_kk 
    FROM biodata_jamaah b 
    JOIN users u ON b.id_user = u.id_user 
    WHERE u.id_user = '$id_detail'
");

if (mysqli_num_rows($query) == 0) {
    echo "<script>alert('Data jamaah tidak ditemukan atau belum mengisi biodata!'); window.location='data_jamaah.php';</script>";
    exit;
}

$d = mysqli_fetch_assoc($query);
$jenjang = $d['jenjang'];

// ========================================================================
// LOGIKA VISIBILITAS BLOK INFORMASI BERDASARKAN JENJANG
// ========================================================================
$show_kelahiran = in_array($jenjang, ['Muda/i', 'Remaja', 'Pra Remaja', 'Caberawit']);
$show_ortu      = in_array($jenjang, ['Muda/i', 'Remaja', 'Pra Remaja', 'Caberawit']);
$show_hobi      = in_array($jenjang, ['Muda/i', 'Remaja', 'Pra Remaja', 'Caberawit']);
$show_mudai     = ($jenjang == 'Muda/i');
$show_mubaligh  = in_array($jenjang, ['Umum', 'Muda/i']);

// Hitung Umur
$umur = '-';
if (!empty($d['tanggal_lahir']) && $d['tanggal_lahir'] != '0000-00-00') {
    $umur = (new DateTime($d['tanggal_lahir']))->diff(new DateTime('today'))->y . ' Tahun';
}

$foto_profil = (!empty($d['foto']) && file_exists('uploads/'.$d['foto'])) ? 'uploads/'.$d['foto'] : 'https://placehold.co/400x400?text=No+Foto';

// Logika Tampilan Gender
$bg_gender   = ($d['jenis_kelamin'] == 'L') ? 'bg-info text-dark' : 'bg-danger text-white';
$icon_gender = ($d['jenis_kelamin'] == 'L') ? 'fa-mars' : 'fa-venus';
$text_gender = ($d['jenis_kelamin'] == 'L') ? 'LAKI-LAKI' : 'PEREMPUAN';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detail Profil Jamaah | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        /* EFEK ZOOM FOTO PROFIL & BADGE GENDER */
        .profile-container { position: relative; display: inline-block; margin-bottom: 25px; }
        .profile-img-wrapper { position: relative; display: inline-block; cursor: zoom-in; }
        .profile-img { width: 160px; height: 160px; object-fit: cover; border-radius: 50%; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.15); transition: 0.3s; }
        .profile-img-wrapper::after { 
            content: '\f00e'; font-family: 'Font Awesome 6 Free'; font-weight: 900; 
            position: absolute; top: 0; left: 0; 
            width: 160px; height: 160px; background: rgba(0,0,0,0.4); color: white; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 2.5rem; border-radius: 50%; opacity: 0; transition: 0.3s; 
        }
        .profile-img-wrapper:hover::after { opacity: 1; }
        
        .gender-badge {
            position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%);
            font-size: 0.85rem; padding: 6px 16px; border: 3px solid #fff; 
            box-shadow: 0 3px 8px rgba(0,0,0,0.15); z-index: 2; font-weight: bold; letter-spacing: 1px;
        }
        
        /* TYPOGRAPHY BARU: JUDUL BOLD, ISI BIASA */
        .info-label { 
            font-size: 0.95rem; color: #1a535c; font-weight: 700; 
            margin-bottom: 4px; display: block;
        }
        .info-value { 
            font-size: 1rem; color: #333333; font-weight: 400; 
            margin-bottom: 20px; border-bottom: 1px solid #e9ecef; padding-bottom: 8px; 
        }
        .section-title { 
            font-weight: 800; color: #1a535c; border-bottom: 3px solid #4ecdc4; 
            padding-bottom: 5px; margin-bottom: 25px; display: inline-block; text-transform: uppercase; letter-spacing: 1px;
        }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0"><i class="fa fa-user-circle text-primary me-2"></i>Profil Jamaah</h3>
        <button onclick="history.back()" class="btn btn-outline-dark fw-bold rounded-pill px-4 shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</button>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-custom bg-white text-center p-4 h-100 border-top border-4 border-primary">
                
                <div class="profile-container mt-2">
                    <div class="profile-img-wrapper" data-bs-toggle="modal" data-bs-target="#modalZoomFoto">
                        <img src="<?= $foto_profil; ?>" class="profile-img" alt="Foto Profil">
                    </div>
                    <span class="badge rounded-pill gender-badge <?= $bg_gender; ?>">
                        <i class="fa <?= $icon_gender; ?> me-1"></i> <?= $text_gender; ?>
                    </span>
                </div>
                
                <h4 class="fw-bold text-dark mb-1 mt-2"><?= strtoupper($d['nama_lengkap']); ?></h4>
                <p class="text-muted mb-4 font-italic">"<?= $d['nama_panggilan']; ?>"</p>
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-primary px-3 py-2 rounded-pill fs-6"><?= $d['jenjang']; ?></span>
                    <span class="badge bg-success px-3 py-2 rounded-pill fs-6">Kel. <?= $d['kelompok']; ?></span>
                </div>

                <?php if($show_mubaligh && $d['status_mubaligh'] == 'MT'): ?>
                    <span class="badge bg-warning text-dark border border-warning px-3 py-2 w-100 rounded-pill mb-2 fw-bold"><i class="fa fa-star me-1"></i> MUBALIGH / MT</span>
                <?php endif; ?>
                
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom bg-white p-4 p-md-5 h-100">
                
                <h5 class="section-title"><i class="fa fa-address-book me-2"></i>Kontak & Asal Daerah</h5>
                <div class="row">
                    <div class="col-md-6">
                        <span class="info-label">No. HP / WhatsApp</span>
                        <div class="info-value">
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $d['no_hp']); ?>" target="_blank" class="text-decoration-none text-success fw-bold">
                                <i class="fab fa-whatsapp me-1"></i> <?= $d['no_hp'] ?: '-'; ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <span class="info-label">Status dalam Keluarga</span>
                        <div class="info-value">
                            <?= $d['status_keluarga'] ?: '-'; ?>
                            <?php if(!empty($d['nama_kk'])): ?>
                                <small class="text-muted d-block mt-1"><i class="fa fa-link me-1"></i>Taut KK: <?= $d['nama_kk']; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <span class="info-label">Alamat Asal / Rumah Domisili</span>
                        <div class="info-value"><?= $d['alamat_asal'] ?: '-'; ?></div>
                    </div>
                </div>

                <?php if($show_kelahiran): ?>
                <div class="mt-4"></div>
                <h5 class="section-title"><i class="fa fa-birthday-cake me-2"></i>Data Pribadi</h5>
                <div class="row">
                    <div class="col-md-4">
                        <span class="info-label">Tempat Lahir</span>
                        <div class="info-value"><?= $d['tempat_lahir'] ?: '-'; ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Tanggal Lahir</span>
                        <div class="info-value">
                            <?= ($d['tanggal_lahir'] && $d['tanggal_lahir'] != '0000-00-00') ? date('d M Y', strtotime($d['tanggal_lahir'])) : '-'; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Umur Saat Ini</span>
                        <div class="info-value"><?= $umur; ?></div>
                    </div>
                    <?php if($show_hobi): ?>
                    <div class="col-12">
                        <span class="info-label">Hobi / Minat</span>
                        <div class="info-value border-0"><?= $d['hobi'] ?: '-'; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if($show_ortu): ?>
                <div class="mt-4"></div>
                <h5 class="section-title"><i class="fa fa-users me-2"></i>Data Orang Tua</h5>
                <div class="row">
                    <div class="col-md-6">
                        <span class="info-label">Nama Orang Tua (Ayah)</span>
                        <div class="info-value"><?= $d['nama_ortu'] ?: '-'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <span class="info-label">No. HP Orang Tua</span>
                        <div class="info-value"><?= $d['hp_ortu'] ?: '-'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <span class="info-label">Status Kepahaman Ortu</span>
                        <div class="info-value border-0"><?= $d['status_ortu'] ?: '-'; ?></div>
                    </div>
                    <?php if($jenjang == 'Muda/i'): ?>
                    <div class="col-md-6">
                        <span class="info-label">Tempat Sambung Ortu</span>
                        <div class="info-value border-0"><?= $d['tempat_sambung_ortu'] ?: '-'; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if($show_mudai): ?>
                <div class="mt-4"></div>
                <div class="p-4 bg-info bg-opacity-10 rounded-4 border border-info border-opacity-25">
                    <h5 class="section-title border-info"><i class="fa fa-graduation-cap me-2"></i>Aktivitas & Domisili Surabaya</h5>
                    <div class="row">
                        <div class="col-12">
                            <span class="info-label">Alamat Surabaya (Kost/Rumah)</span>
                            <div class="info-value"><?= $d['alamat_surabaya'] ?: '-'; ?></div>
                        </div>
                        
                        <div class="col-md-4">
                            <span class="info-label">Kegiatan Utama</span>
                            <div class="info-value"><span class="badge bg-dark px-3 py-2"><?= $d['kegiatan_surabaya'] ?: 'Belum diatur'; ?></span></div>
                        </div>

                        <?php if($d['kegiatan_surabaya'] == 'Kuliah'): ?>
                            <div class="col-md-4"><span class="info-label">Universitas</span><div class="info-value"><?= $d['universitas'] ?: '-'; ?></div></div>
                            <div class="col-md-4"><span class="info-label">Jurusan (Angkatan)</span><div class="info-value"><?= $d['jurusan'] ?: '-'; ?> <span class="text-muted">(<?= $d['angkatan']; ?>)</span></div></div>
                        <?php elseif($d['kegiatan_surabaya'] == 'Bekerja'): ?>
                            <div class="col-md-8">
                                <span class="info-label">Tempat & Alamat Kerja</span>
                                <div class="info-value"><?= $d['tempat_kerja'] ?: '-'; ?><br><small class="text-muted"><?= $d['alamat_kerja']; ?></small></div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12 mt-3">
                            <span class="info-label text-danger mb-3 bg-white p-2 rounded text-center border border-danger border-opacity-25"><i class="fa fa-phone-alt me-1"></i> Kontak Darurat di Surabaya</span>
                            <div class="row">
                                <div class="col-md-4"><span class="info-label">Nama Kontak</span><div class="info-value border-0 mb-0"><?= $d['darurat_nama'] ?: '-'; ?></div></div>
                                <div class="col-md-4"><span class="info-label">Hubungan</span><div class="info-value border-0 mb-0"><?= $d['darurat_hubungan'] ?: '-'; ?></div></div>
                                <div class="col-md-4"><span class="info-label">No. HP Darurat</span><div class="info-value border-0 mb-0 text-danger fw-bold"><?= $d['darurat_hp'] ?: '-'; ?></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalZoomFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close btn-close-white fs-4" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="<?= $foto_profil; ?>" class="img-fluid rounded-4 shadow-lg border border-4 border-white" alt="Foto Besar" style="max-height: 80vh; object-fit: contain;">
                <h4 class="text-white mt-3 fw-bold"><?= $d['nama_lengkap']; ?></h4>
                <span class="badge <?= $bg_gender; ?> rounded-pill px-3 py-2 mt-1"><i class="fa <?= $icon_gender; ?> me-1"></i> <?= $text_gender; ?></span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>