<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// 1. PROTEKSI HAK AKSES (SEMUA PENGURUS BISA MASUK UNTUK MELIHAT)
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>";
    exit;
}

$level = $_SESSION['level'];
$kelompok_admin = $_SESSION['kelompok'];

// 2. TENTUKAN SIAPA YANG BOLEH KLIK TOMBOL ACC/TOLAK
$decision_makers = ['superadmin', 'keimaman_desa', 'keimaman', 'ketua_mudai_desa', 'ketua_mudai', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
$can_approve = in_array($level, $decision_makers);

// =========================================================================
// PROSES AKSI: ACC DENGAN BALASAN TEMPLATE (HANYA JIKA PUNYA WEWENANG)
// =========================================================================
if (isset($_POST['acc_dengan_balasan']) && $can_approve) {
    $id_izin = mysqli_real_escape_string($conn, $_POST['id_izin']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan_admin']);
    
    mysqli_query($conn, "UPDATE perizinan SET status_izin = 'disetujui', status_konfirmasi = 'Disetujui', catatan_admin = '$catatan' WHERE id_izin = '$id_izin'");
    echo "<script>window.location='kelola_izin.php';</script>";
}

// PROSES AKSI: TOLAK
if (isset($_GET['tolak']) && $can_approve) {
    $id_izin = mysqli_real_escape_string($conn, $_GET['tolak']);
    mysqli_query($conn, "UPDATE perizinan SET status_izin = 'ditolak', status_konfirmasi = 'Ditolak' WHERE id_izin = '$id_izin'");
    echo "<script>window.location='kelola_izin.php';</script>";
}

// =========================================================================
// LOGIKA HIERARKI: ALIRAN TAMPILAN SURAT IZIN
// =========================================================================
$kondisi_hierarki = "AND 1=0"; 

$is_keg_desa = "((SELECT COUNT(*) FROM kegiatan k2 WHERE k2.judul_pengajian = k.judul_pengajian AND k2.tgl_buat = k.tgl_buat) > 1 OR k.target_kelompok = 'Semua')";
$is_keg_kel = "((SELECT COUNT(*) FROM kegiatan k2 WHERE k2.judul_pengajian = k.judul_pengajian AND k2.tgl_buat = k.tgl_buat) = 1 AND k.target_kelompok != 'Semua' AND k.target_kelompok = '$kelompok_admin')";

if (in_array($level, ['superadmin', 'admin_desa'])) {
    $kondisi_hierarki = ""; // Pusat bisa melihat semua perizinan
} 
else if ($level == 'admin') {
    $kondisi_hierarki = "AND ($is_keg_desa OR k.target_kelompok = '$kelompok_admin')"; // Admin Kelompok lihat semua di kelompoknya
}
else if ($level == 'keimaman_desa') {
    $kondisi_hierarki = "AND $is_keg_desa AND k.target_jenjang IN ('Semua', 'Umum')";
} 
else if ($level == 'keimaman') {
    $kondisi_hierarki = "AND $is_keg_kel AND k.target_jenjang IN ('Semua', 'Umum')";
} 
else if (in_array($level, ['ketua_mudai_desa', 'admin_mudai_desa'])) {
    $kondisi_hierarki = "AND $is_keg_desa AND k.target_jenjang = 'Muda/i'"; // MM Desa
} 
else if (in_array($level, ['ketua_mudai', 'admin_mudai'])) {
    $kondisi_hierarki = "AND $is_keg_kel AND k.target_jenjang = 'Muda/i'"; // MM Kelompok
} 
else if ($level == 'admin_remaja') {
    $kondisi_hierarki = "AND $is_keg_kel AND k.target_jenjang = 'Remaja'";
} 
else if ($level == 'admin_praremaja') {
    $kondisi_hierarki = "AND $is_keg_kel AND k.target_jenjang = 'Pra Remaja'";
} 
else if ($level == 'admin_caberawit') {
    $kondisi_hierarki = "AND $is_keg_kel AND k.target_jenjang = 'Caberawit'";
}

// AMBIL DATA IZIN YANG MASIH PENDING (Sudah Terfilter)
$query_izin = mysqli_query($conn, "
    SELECT z.*, u.kelompok, u.id_user,
           b.nama_lengkap, b.jenjang, b.jenis_kelamin,
           k.judul_pengajian, k.target_kelompok, k.tgl_buat, k.target_jenjang
    FROM perizinan z
    JOIN users u ON z.id_user = u.id_user
    JOIN kegiatan k ON z.id_kegiatan = k.id_kegiatan
    JOIN biodata_jamaah b ON u.id_user = b.id_user
    WHERE z.status_izin = 'pending'
    $kondisi_hierarki
    ORDER BY z.tgl_pengajuan ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Validasi Izin Jamaah | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .izin-card { transition: 0.3s; border-left: 5px solid #ffc107; }
        .izin-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .izin-online { border-left-color: #0dcaf0; }
        .table-biodata th { background-color: #f8f9fa; color: #495057; font-weight: 600; width: 35%; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; padding-bottom: 90px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa fa-clipboard-check text-primary me-2"></i>Validasi Surat Izin</h2>
            <small class="text-muted fw-bold">Status: <?= $can_approve ? '<span class="text-success">Memiliki Wewenang ACC</span>' : '<span class="text-danger">Hanya Mode Pantau</span>'; ?></small>
        </div>
        <a href="dashboard_keimaman.php" class="btn btn-outline-dark fw-bold shadow-sm rounded-pill px-4"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <?php if(mysqli_num_rows($query_izin) == 0): ?>
        <div class="card card-custom bg-white p-5 text-center shadow-sm">
            <i class="fa fa-envelope-open fa-4x text-secondary mb-3 opacity-25"></i>
            <h4 class="fw-bold text-dark">Alhamdulillah!</h4>
            <p class="text-muted mb-0">Tidak ada permohonan izin yang mengantre di ranah pantauan Anda saat ini.</p>
        </div>
    <?php else: ?>
        <div class="alert alert-info border-info shadow-sm mb-4 fw-bold">
            <i class="fa fa-info-circle me-2"></i> Terdapat <?= mysqli_num_rows($query_izin); ?> permohonan yang menunggu diproses.
        </div>

        <div class="row g-4">
            <?php while($row = mysqli_fetch_assoc($query_izin)): 
                $is_zoom = ($row['jenis_izin'] == 'Online');
                $u_id_izin = $row['id_user'];
                
                $q_bio_full = mysqli_query($conn, "SELECT * FROM biodata_jamaah WHERE id_user = '$u_id_izin'");
                $bio_full = mysqli_fetch_assoc($q_bio_full);

                $alasan_lower = strtolower($row['alasan']);
                $is_sakit = (strpos($alasan_lower, 'sakit') !== false || strpos($alasan_lower, 'berobat') !== false || strpos($alasan_lower, 'rumah sakit') !== false || strpos($alasan_lower, 'rs') !== false || strpos($alasan_lower, 'klinik') !== false || strpos($alasan_lower, 'opname') !== false);

                $nama_jamaah = ucwords(strtolower($row['nama_lengkap']));
                if ($is_sakit) {
                    $template_balasan = "Alhamdulillah izin sudah kami ACC.\n\nSemoga Allah SWT memberikan kesembuhan dan kesehatan yang barokah untuk $nama_jamaah, diangkat penyakitnya, dan bisa beribadah kembali. Semoga Allah SWT mengampuni kita semua. Aamiin...";
                } else {
                    $template_balasan = "Alhamdulillah izin sudah kami ACC.\n\nSemoga urusan $nama_jamaah diberikan kelancaran, kemudahan, dan kebarokahan oleh Allah SWT. Semoga Allah SWT mengampuni kita semua. Aamiin... Alhamdulillah jaza kumullahu khoiro.";
                }
            ?>
                <div class="col-12 col-xl-6">
                    <div class="card card-custom p-4 bg-white shadow-sm izin-card <?= $is_zoom ? 'izin-online' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-3">
                            <div>
                                <h5 class="fw-bold text-primary mb-1"><?= strtoupper($row['nama_lengkap']); ?></h5>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-secondary">Kel. <?= $row['kelompok']; ?></span>
                                    <span class="badge bg-light text-dark border"><?= $row['jenjang']; ?></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <?php if($is_zoom): ?>
                                    <span class="badge bg-info text-dark fs-6 rounded-pill d-block mb-2"><i class="fa fa-video me-1"></i> Izin Zoom</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark fs-6 rounded-pill d-block mb-2"><i class="fa fa-envelope me-1"></i> Tidak Hadir</span>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-sm btn-outline-dark fw-bold shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalBioFull<?= $row['id_izin']; ?>">
                                    <i class="fa fa-id-card me-1"></i> Detail Jamaah
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted fw-bold d-block mb-1">Acara Pengajian:</small>
                            <span class="fw-bold text-dark"><i class="fa fa-book-open text-primary me-1"></i> <?= $row['judul_pengajian']; ?></span>
                            <small class="text-muted ms-2">(<?= date('d M, H:i', strtotime($row['tgl_buat'])); ?>)</small>
                        </div>

                        <div class="bg-light p-3 rounded border mb-4 position-relative">
                            <?php if($is_sakit): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 mt-2 me-2"><i class="fa fa-heartbeat me-1"></i> Izin Sakit</span>
                            <?php endif; ?>
                            <small class="text-muted fw-bold d-block mb-1">Keterangan / Alasan:</small>
                            <p class="mb-0 text-dark" style="font-style: italic;">"<?= nl2br(htmlspecialchars($row['alasan'])); ?>"</p>
                            <small class="text-muted mt-2 d-block text-end" style="font-size: 0.7rem;">Dikirim: <?= date('d M Y, H:i', strtotime($row['tgl_pengajuan'])); ?></small>
                        </div>

                        <?php if($can_approve): ?>
                            <div class="row g-2">
                                <div class="col-6">
                                    <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalACCBalasan<?= $row['id_izin']; ?>">
                                        <i class="fa fa-check-circle me-1"></i> ACC (TERIMA)
                                    </button>
                                </div>
                                <div class="col-6">
                                    <a href="?tolak=<?= $row['id_izin']; ?>" class="btn btn-outline-danger w-100 fw-bold shadow-sm" onclick="return confirm('Tolak permohonan ini? (Status akan menjadi Tidak Hadir)')">
                                        <i class="fa fa-times-circle me-1"></i> TOLAK
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning border-warning shadow-sm text-dark mb-0 py-2 text-center fw-bold" style="font-size:0.85rem;">
                                <i class="fa fa-hourglass-half me-1"></i> Menunggu Validasi dari Pengurus Berwenang
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <?php if($can_approve): ?>
                <div class="modal fade" id="modalACCBalasan<?= $row['id_izin']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                            <div class="modal-header bg-success text-white border-0 p-3">
                                <h5 class="modal-title fw-bold"><i class="fa fa-paper-plane me-2"></i>Kirim Balasan ke Jamaah</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body p-4 bg-white">
                                    <input type="hidden" name="id_izin" value="<?= $row['id_izin']; ?>">
                                    
                                    <div class="alert alert-light border-success border-2 shadow-sm text-dark mb-4">
                                        Anda akan meng-ACC perizinan dari <b><?= strtoupper($row['nama_lengkap']); ?></b>. Silakan periksa atau edit draf pesan balasan di bawah ini sebelum dikirim.
                                    </div>

                                    <label class="form-label fw-bold text-success">Pesan Balasan dari Pengurus:</label>
                                    <textarea name="catatan_admin" class="form-control border-success" rows="6" style="background-color: #f8fff9;" required><?= $template_balasan; ?></textarea>
                                </div>
                                <div class="modal-footer bg-light border-0">
                                    <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="acc_dengan_balasan" class="btn btn-success fw-bold rounded-pill shadow-sm px-4">
                                        <i class="fa fa-check"></i> ACC & Kirim Pesan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="modal fade" id="modalBioFull<?= $row['id_izin']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                            <div class="modal-header bg-dark text-white border-0 p-3">
                                <h5 class="modal-title fw-bold"><i class="fa fa-address-book me-2 text-warning"></i>Biodata Lengkap Jamaah</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0 bg-white">
                                
                                <div class="bg-light text-center p-4 border-bottom">
                                    <i class="fa <?= ($row['jenis_kelamin']=='L') ? 'fa-male text-primary' : 'fa-female text-danger'; ?> fa-4x mb-2"></i>
                                    <h5 class="fw-bold text-dark mb-1"><?= strtoupper($row['nama_lengkap']); ?></h5>
                                    <span class="badge bg-secondary">Kelompok <?= $row['kelompok']; ?></span>
                                    <span class="badge bg-info text-dark">Jenjang <?= $row['jenjang']; ?></span>
                                </div>

                                <div class="p-3">
                                    <table class="table table-bordered table-striped table-hover table-biodata text-dark mb-0 fs-6">
                                        <tbody>
                                            <?php 
                                            if($bio_full) {
                                                foreach($bio_full as $kolom => $isi) {
                                                    if(in_array($kolom, ['id_biodata', 'id_user', 'foto', 'id_kepala_keluarga'])) continue;

                                                    $label = ucwords(str_replace('_', ' ', $kolom));
                                                    $isi_tampil = !empty($isi) ? htmlspecialchars($isi) : '<span class="text-muted fst-italic">Belum diisi</span>';
                                                    
                                                    if (in_array(strtolower($kolom), ['no_hp', 'nohp', 'hp', 'telepon', 'whatsapp']) && !empty($isi)) {
                                                        $wa_number = preg_replace('/[^0-9]/', '', $isi);
                                                        if(substr($wa_number, 0, 1) == '0') { $wa_number = '62' . substr($wa_number, 1); }
                                                        if(!empty($wa_number)) {
                                                            $isi_tampil .= " <a href='https://wa.me/{$wa_number}' target='_blank' class='btn btn-sm btn-success fw-bold py-0 px-2 ms-2 shadow-sm rounded-pill' style='font-size: 0.75rem;'><i class='fab fa-whatsapp'></i> Hubungi</a>";
                                                        }
                                                    }

                                                    echo "<tr><th>{$label}</th><td>{$isi_tampil}</td></tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='2' class='text-center text-danger fw-bold'>Biodata belum dilengkapi oleh jamaah.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                            <div class="modal-footer bg-light border-0 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill" data-bs-dismiss="modal">TUTUP</button>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>