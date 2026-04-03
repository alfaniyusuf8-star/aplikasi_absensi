<?php
session_start();
include 'koneksi.php';

// Proteksi: Hanya Para Admin (Eksekutor) yang berhak mengurus Kehadiran Online (Zoom)
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'admin_remaja', 'admin_praremaja'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Gagal! Hanya Admin Pengajian yang berhak mengesahkan kehadiran online.'); window.location='dashboard_keimaman.php';</script>";
    exit;
}

$id_user_admin = $_SESSION['id_user'];
$level_user = $_SESSION['level']; 
$kelompok_admin = $_SESSION['kelompok'];

// --- PROSES ACC KONFIRMASI HADIR ONLINE ---
if (isset($_GET['aksi_konfirm']) && isset($_GET['id'])) {
    $id_izin = mysqli_real_escape_string($conn, $_GET['id']);
    $aksi = $_GET['aksi_konfirm'];
    
    $q_detail = mysqli_query($conn, "SELECT p.*, k.target_jenjang FROM perizinan p JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan WHERE p.id_izin = '$id_izin'");
    $d_izin = mysqli_fetch_assoc($q_detail);
    
    if ($d_izin) {
        // Proteksi Lapis 2: Admin dilarang menyusup ACC jenjang lain
        if ($level_user == 'admin_mudai' && $d_izin['target_jenjang'] != 'Muda/i') {
            echo "<script>alert('Gagal! Hak akses Anda hanya untuk Muda/i.'); window.location='kelola_online.php';</script>"; exit;
        }
        if ($level_user == 'admin_remaja' && $d_izin['target_jenjang'] != 'Remaja') {
            echo "<script>alert('Gagal! Hak akses Anda hanya untuk Remaja.'); window.location='kelola_online.php';</script>"; exit;
        }
        if ($level_user == 'admin_praremaja' && $d_izin['target_jenjang'] != 'Pra Remaja') {
            echo "<script>alert('Gagal! Hak akses Anda hanya untuk Pra Remaja.'); window.location='kelola_online.php';</script>"; exit;
        }

        if ($aksi == 'acc') {
            // Update status izin
            mysqli_query($conn, "UPDATE perizinan SET status_konfirmasi = 'Disetujui' WHERE id_izin = '$id_izin'");
            // Masukkan ke presensi sebagai hadir
            mysqli_query($conn, "INSERT INTO presensi (id_user, id_kegiatan, tgl_presensi, status_absen) VALUES ('".$d_izin['id_user']."', '".$d_izin['id_kegiatan']."', NOW(), 'hadir')");
            echo "<script>alert('Kehadiran Online disahkan!'); window.location='kelola_online.php';</script>";
        } elseif ($aksi == 'tolak') {
            mysqli_query($conn, "UPDATE perizinan SET status_konfirmasi = 'Ditolak' WHERE id_izin = '$id_izin'");
            echo "<script>alert('Konfirmasi kehadiran ditolak!'); window.location='kelola_online.php';</script>";
        }
    }
}

// Filter Wilayah
$filter_wilayah = ($kelompok_admin == 'Semua' || empty($kelompok_admin) || $level_user == 'admin') ? "1" : "u.kelompok = '$kelompok_admin'";

// Filter Jenjang Spesifik untuk masing-masing Admin
$filter_jenjang = "1";
if ($level_user == 'admin_mudai') {
    $filter_jenjang = "k.target_jenjang = 'Muda/i'";
} elseif ($level_user == 'admin_remaja') {
    $filter_jenjang = "k.target_jenjang = 'Remaja'";
} elseif ($level_user == 'admin_praremaja') {
    $filter_jenjang = "k.target_jenjang = 'Pra Remaja'";
}

// Ambil Data Konfirmasi Online yang Menunggu ACC
$q_konfirm = mysqli_query($conn, "SELECT p.*, u.kelompok, b.nama_lengkap, k.judul_pengajian, k.target_jenjang 
    FROM perizinan p 
    JOIN users u ON p.id_user = u.id_user 
    LEFT JOIN biodata_jamaah b ON u.id_user = b.id_user 
    JOIN kegiatan k ON p.id_kegiatan = k.id_kegiatan 
    WHERE p.jenis_izin = 'Online' AND p.status_izin = 'disetujui' AND p.status_konfirmasi = 'Menunggu' AND ($filter_wilayah) AND ($filter_jenjang)");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>ACC Hadir Online | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold text-dark"><i class="fa fa-video text-primary me-2"></i>Validasi Hadir Online</h2>
    </div>

    <div class="card p-4 border-top border-primary border-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="fa fa-check-double me-2 text-primary"></i>Daftar Tunggu Kehadiran Zoom</h5>
        </div>

        <div class="alert alert-info small border-0 shadow-sm">
            <i class="fa fa-info-circle me-1"></i> Data di bawah ini adalah jamaah yang izin Zoom-nya sudah di-ACC, dan mereka baru saja menekan tombol "Hadir Online" setelah pengajian ditutup. Silakan sahkan kehadiran mereka.
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Jamaah</th>
                        <th>Kegiatan (Jenjang)</th>
                        <th>Tipe</th>
                        <th>Status Bukti</th>
                        <th>Aksi Sahkan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($q_konfirm) == 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4"><i class="fa fa-mug-hot fs-4 d-block mb-2 text-secondary"></i> Belum ada jamaah yang kirim konfirmasi hadir online.</td></tr>
                    <?php endif; ?>

                    <?php while($r = mysqli_fetch_assoc($q_konfirm)): ?>
                    <tr>
                        <td>
                            <span class="fw-bold text-primary"><?= strtoupper($r['nama_lengkap']); ?></span><br>
                            <small class="text-muted"><i class="fa fa-map-marker-alt me-1"></i>Kel. <?= $r['kelompok']; ?></small>
                        </td>
                        <td>
                            <small class="fw-bold d-block"><?= $r['judul_pengajian']; ?></small>
                            <span class="badge bg-light text-dark border"><?= $r['target_jenjang']; ?></span>
                        </td>
                        <td class="text-center"><span class="badge bg-primary px-3 py-2"><i class="fa fa-video me-1"></i> Zoom</span></td>
                        <td class="text-center"><small class="text-warning fw-bold"><i class="fa fa-clock me-1"></i> Menunggu Validasi</small></td>
                        <td class="text-center">
                            <div class="btn-group shadow-sm">
                                <a href="?aksi_konfirm=acc&id=<?= $r['id_izin']; ?>" class="btn btn-success btn-sm fw-bold" onclick="return confirm('Sahkan jamaah ini HADIR secara Online?')"><i class="fa fa-stamp me-1"></i> SAHKAN</a>
                                <a href="?aksi_konfirm=tolak&id=<?= $r['id_izin']; ?>" class="btn btn-outline-danger btn-sm fw-bold" onclick="return confirm('Tolak konfirmasi kehadiran ini?')"><i class="fa fa-times me-1"></i> Tolak</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>