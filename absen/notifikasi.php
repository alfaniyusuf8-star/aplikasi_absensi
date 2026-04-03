<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 1. TANDAI SEMUA NOTIFIKASI SEBAGAI "SUDAH DIBACA" SAAT HALAMAN INI DIBUKA
mysqli_query($conn, "UPDATE notifikasi SET is_read = 1 WHERE id_user = '$id_user'");

// 2. TARIK DATA NOTIFIKASI (Gunakan nama variabel yang UNIK agar tidak ditabrak sidebar.php)
$query_daftar_notif = mysqli_query($conn, "SELECT * FROM notifikasi WHERE id_user = '$id_user' ORDER BY id_notif DESC LIMIT 50");

if (!$query_daftar_notif) {
    die("<div class='p-3 text-danger'><strong>Error Database:</strong> " . mysqli_error($conn) . "</div>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Notifikasi | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_mobile.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .main-content { padding: 0 !important; padding-bottom: 90px !important; }
        .header-title { background: #fff; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; z-index: 1020; }
        
        .notif-card { background: #fff; border-bottom: 1px solid #f1f5f9; padding: 15px; display: flex; gap: 15px; text-decoration: none; color: inherit; transition: 0.2s; }
        .notif-card:active { background: #f8fafc; }
        .notif-card.unread { background: #eff6ff; } /* Biru tipis kalau belum dibaca */
        
        .n-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 1.1rem; flex-shrink: 0; }
        .n-content { flex-grow: 1; }
        .n-title { font-weight: 800; color: #1e293b; font-size: 0.9rem; margin-bottom: 3px; }
        .n-desc { font-size: 0.75rem; color: #64748b; line-height: 1.4; margin-bottom: 5px; }
        .n-time { font-size: 0.65rem; color: #94a3b8; font-weight: 600; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header-title shadow-sm d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="fa fa-bell text-warning me-2"></i>Notifikasi Saya</h6>
        </div>
        <a href="dashboard.php" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold">Kembali</a>
    </div>

    <div>
        <?php if(mysqli_num_rows($query_daftar_notif) == 0): ?>
            <div class="text-center py-5 mt-5">
                <i class="fa fa-bell-slash fa-4x text-muted opacity-25 mb-3"></i>
                <h6 class="fw-bold text-muted">Belum ada pemberitahuan.</h6>
                <p class="small text-muted">Notifikasi sistem akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <?php while($n = mysqli_fetch_assoc($query_daftar_notif)): 
                // Tentukan ikon berdasarkan keyword judul
                $bg_icon = 'bg-primary text-white'; $fa_icon = 'fa-info';
                $judul = strtolower($n['judul'] ?? '');
                if(strpos($judul, 'ta\'aruf') !== false) { $bg_icon = 'bg-danger text-white'; $fa_icon = 'fa-heart'; }
                if(strpos($judul, 'izin') !== false) { $bg_icon = 'bg-info text-dark'; $fa_icon = 'fa-file-signature'; }
                if(strpos($judul, 'selamat') !== false || strpos($judul, 'diterima') !== false) { $bg_icon = 'bg-success text-white'; $fa_icon = 'fa-check-circle'; }
                if(strpos($judul, 'pengajian') !== false) { $bg_icon = 'bg-warning text-dark'; $fa_icon = 'fa-mosque'; }
                
                // Format Waktu yang Aman
                $waktu_tampil = (!empty($n['created_at'])) ? date('d M Y, H:i', strtotime($n['created_at'])) . ' WIB' : 'Baru saja';
            ?>
                <a href="<?= htmlspecialchars($n['link'] ?? '#'); ?>" class="notif-card <?= (isset($n['is_read']) && $n['is_read'] == 0) ? 'unread' : ''; ?>">
                    <div class="n-icon <?= $bg_icon; ?> shadow-sm"><i class="fa <?= $fa_icon; ?>"></i></div>
                    <div class="n-content">
                        <div class="n-title"><?= htmlspecialchars($n['judul'] ?? 'Pemberitahuan'); ?></div>
                        <div class="n-desc"><?= htmlspecialchars($n['pesan'] ?? ''); ?></div>
                        <div class="n-time"><i class="fa fa-clock me-1"></i> <?= $waktu_tampil; ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>