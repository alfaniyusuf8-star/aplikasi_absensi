<?php
// Pastikan session dan koneksi sudah ada sebelum file ini di-include
$current_page = basename($_SERVER['PHP_SELF']);
$lvl_sidebar = isset($_SESSION['level']) ? $_SESSION['level'] : 'karyawan';
$id_user_sidebar = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 0;

// Ambil data utama user
$q_utama = mysqli_query($conn, "
    SELECT u.level, u.kelompok, b.jenjang, b.status_pernikahan, b.foto, b.tempat_lahir, b.no_hp, b.alamat_surabaya 
    FROM users u 
    LEFT JOIN biodata_jamaah b ON u.id_user = b.id_user 
    WHERE u.id_user = '$id_user_sidebar'
");
$d_utama = mysqli_fetch_assoc($q_utama);

// Cek jabatan rangkap
$q_rangkap = mysqli_query($conn, "SELECT * FROM jabatan_rangkap WHERE id_user = '$id_user_sidebar'");
$punya_rangkap = (mysqli_num_rows($q_rangkap) > 0);

function formatJabatan($lvl) {
    if($lvl == 'karyawan') return "Jamaah";
    return ucwords(str_replace('_', ' ', $lvl));
}

// Hitung Notifikasi
$jml_notif = 0;
if(isset($conn)) {
    $q_notif = mysqli_query($conn, "SELECT COUNT(*) as total FROM notifikasi WHERE id_user = '$id_user_sidebar' AND is_read = 0");
    if($q_notif) {
        $notif_data = mysqli_fetch_assoc($q_notif);
        $jml_notif = $notif_data['total'];
    }
}

// Konfigurasi Akses
$akses_semua_pengurus = ['superadmin', 'admin_desa', 'admin', 'keimaman_desa', 'keimaman', 'ketua_mudai_desa', 'admin_mudai_desa', 'ketua_mudai', 'admin_mudai', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
$akses_pengurus_inti = ['superadmin', 'admin_desa', 'admin', 'keimaman_desa', 'keimaman'];
?>

<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#1a535c">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="GemaSemampir">
<link rel="apple-touch-icon" href="icon-192.png">
<link rel="stylesheet" href="style_mobile.css">

<style>
    body { background: #f4f7f6; font-family: 'Inter', sans-serif; padding-top: 60px; padding-bottom: 80px; -webkit-tap-highlight-color: transparent;}
    .main-content { margin-left: 0 !important; padding: 15px !important; }
    
    /* TOPBAR */
    .app-topbar { background: #fff; height: 60px; position: fixed; top: 0; left: 0; right: 0; z-index: 1030; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    
    /* BOTTOM NAVIGATION */
    .app-bottom-nav { background: #fff; height: 65px; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1040; display: flex; justify-content: space-around; align-items: center; box-shadow: 0 -2px 15px rgba(0,0,0,0.05); border-top-left-radius: 20px; border-top-right-radius: 20px; padding: 0 10px; }
    .nav-item { display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: #a0aec0; width: 60px; transition: 0.3s; cursor: pointer;}
    .nav-item.active { color: #1a535c; }
    .nav-item i { font-size: 1.3rem; margin-bottom: 3px; }
    .nav-item.active i { font-size: 1.4rem; transform: translateY(-2px); }
    .nav-item span { font-size: 0.65rem; font-weight: 600; }
    .nav-fab { background: linear-gradient(135deg, #1a535c, #4ecdc4); width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white !important; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4); transform: translateY(-20px); border: 4px solid #f4f7f6; }
    
    /* LIST MENU */
    .menu-list-item { display: flex; align-items: center; padding: 15px; text-decoration: none; color: #333; border-bottom: 1px solid #f1f5f9; }
    .menu-list-item:hover { background: #f8fafc; color: #1a535c; }
    .menu-icon { width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #e2e8f0; color: #1a535c; margin-right: 15px; font-size: 1rem;}

    /* CUSTOM BOTTOM SHEET (POPUP MENU) */
    .menu-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1020; opacity: 0; visibility: hidden; transition: 0.3s ease-in-out; }
    .popup-menu { position: fixed; bottom: -100%; left: 0; width: 100%; background: #ffffff; border-radius: 25px 25px 0 0; box-shadow: 0 -5px 20px rgba(0,0,0,0.15); z-index: 1030; padding: 20px 20px 85px 20px; max-height: 85vh; overflow-y: auto; transition: bottom 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
    .popup-menu.active { bottom: 0; }
    .menu-backdrop.active { opacity: 1; visibility: visible; }
</style>

<div class="app-topbar">
    <div class="d-flex align-items-center">
        <img src="icon-192.png" alt="Logo" style="width: 35px; height: 35px; border-radius: 8px; object-fit: cover;" class="me-2 shadow-sm">
        <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.5px;">Gemasemampir</h5>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="notifikasi.php" class="text-dark text-decoration-none position-relative">
            <i class="fa fa-bell fs-5"></i>
            <?php if($jml_notif > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.6rem; padding: 3px 5px;">
                    <?= $jml_notif; ?>
                </span>
            <?php endif; ?>
        </a>
        <span class="badge bg-light text-dark border shadow-sm px-3 py-2 rounded-pill">
            <i class="fa fa-map-marker-alt text-danger me-1"></i> Kel. <?= $d_utama['kelompok']; ?>
        </span>
    </div>
</div>

<div class="menu-backdrop" id="menuBackdrop" onclick="toggleMenu()"></div>
<div class="popup-menu" id="popupMenu">
    <div class="text-center mb-3">
        <div style="width: 50px; height: 5px; background: #cbd5e1; border-radius: 5px; margin: 0 auto;"></div>
    </div>
    <h5 class="fw-bold text-center mb-4 mt-2">Menu Utama</h5>
    
    <div class="d-flex align-items-center bg-light p-3 rounded-4 mb-4 border">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width:50px; height:50px; font-size: 1.5rem;"><i class="fa fa-user"></i></div>
        <div>
            <h6 class="fw-bold mb-0 text-dark"><?= $_SESSION['username'] ?? 'Jamaah'; ?></h6>
            <span class="badge bg-warning text-dark mt-1"><i class="fa fa-shield-alt me-1"></i> <?= formatJabatan($lvl_sidebar); ?></span>
        </div>
    </div>

    <h6 class="fw-bold text-muted small mb-2 ps-2">PRIBADI SAYA</h6>
    <div class="bg-white rounded-4 shadow-sm border mb-4 overflow-hidden">
        <a href="isi_biodata.php" class="menu-list-item"><div class="menu-icon"><i class="fa fa-address-card"></i></div> <b>Biodata Saya</b></a>
        <a href="pengaturan_akun.php" class="menu-list-item"><div class="menu-icon"><i class="fa fa-cog"></i></div> <b>Pengaturan Akun</b></a>
        <a href="notifikasi.php" class="menu-list-item">
            <div class="menu-icon bg-warning bg-opacity-25 text-warning"><i class="fa fa-bell"></i></div> <b class="flex-grow-1">Notifikasi</b>
            <?php if($jml_notif > 0): ?><span class="badge bg-danger rounded-pill"><?= $jml_notif; ?></span><?php endif; ?>
        </a>
        <a href="riwayat_jamaah.php" class="menu-list-item border-0"><div class="menu-icon bg-info bg-opacity-10 text-info"><i class="fa fa-history"></i></div> <b>Riwayat Absensi</b></a>
    </div>

    <?php if(in_array($lvl_sidebar, $akses_semua_pengurus)): ?>
        <h6 class="fw-bold text-muted small mb-2 ps-2">PANEL PENGURUS</h6>
        <div class="bg-white rounded-4 shadow-sm border mb-4 overflow-hidden">
            <a href="dashboard_keimaman.php" class="menu-list-item"><div class="menu-icon bg-success bg-opacity-10 text-success"><i class="fa fa-chart-pie"></i></div> <b>Dashboard Admin</b></a>
            <a href="data_jamaah.php" class="menu-list-item"><div class="menu-icon"><i class="fa fa-users"></i></div> <b>Data Jamaah</b></a>
            <?php if(in_array($lvl_sidebar, $akses_pengurus_inti)): ?>
                <a href="data_kk.php" class="menu-list-item"><div class="menu-icon"><i class="fa fa-sitemap"></i></div> <b>Data Kartu Keluarga</b></a>
            <?php endif; ?>
            <a href="rekap_absensi.php" class="menu-list-item border-0"><div class="menu-icon bg-primary bg-opacity-10 text-primary"><i class="fa fa-chart-line"></i></div> <b>Rekap Kehadiran</b></a>
        </div>
    <?php endif; ?>

    <div class="d-grid gap-2 mb-2">
        <?php if($punya_rangkap || $d_utama['level'] != 'karyawan'): ?>
            <button class="btn btn-outline-primary fw-bold py-3 rounded-pill" onclick="toggleMenu()" data-bs-toggle="modal" data-bs-target="#modalSwitchRole"><i class="fa fa-sync-alt me-2"></i> Ganti Peran Akun</button>
        <?php endif; ?>
        <a href="logout.php" onclick="return confirm('Yakin ingin keluar?');" class="btn btn-danger fw-bold py-3 rounded-pill shadow-sm"><i class="fa fa-sign-out-alt me-2"></i> Keluar Aplikasi</a>
    </div>
</div>

<div class="app-bottom-nav">
    <a href="dashboard.php" class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fa fa-home"></i><span>Beranda</span>
    </a>
    <a href="bursa_taaruf.php" class="nav-item <?= ($current_page == 'bursa_taaruf.php') ? 'active' : ''; ?>">
        <i class="fa fa-heart"></i><span>Ta'aruf</span>
    </a>
    
    <?php if(in_array($lvl_sidebar, $akses_semua_pengurus)): ?>
        <a href="scan.php" class="nav-item nav-fab"><i class="fa fa-qrcode"></i></a>
    <?php else: ?>
        <a href="isi_biodata.php" class="nav-item nav-fab <?= ($current_page == 'isi_biodata.php') ? 'shadow-lg' : ''; ?>">
            <i class="fa fa-user"></i>
        </a>
    <?php endif; ?>
    
    <a href="bursa_usaha.php" class="nav-item <?= ($current_page == 'bursa_usaha.php') ? 'active' : ''; ?>">
        <i class="fa fa-store"></i><span>Bursa</span>
    </a>
    <a href="javascript:void(0);" onclick="toggleMenu()" class="nav-item">
        <i class="fa fa-bars"></i><span>Menu</span>
    </a>
</div>

<div id="install-banner" style="display:none; position:fixed; bottom:85px; left:50%; transform:translateX(-50%); width:92%; background: #ffffff; color:#333; padding:15px; border-radius:15px; text-align:left; z-index:999; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid #eee;">
    <div class="d-flex align-items-center mb-3">
        <img src="icon-192.png" style="width: 45px; height: 45px; border-radius: 10px; margin-right: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div>
            <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">Pasang GemaSemampir</h6>
            <small class="text-muted" style="font-size: 0.75rem;">Akses lebih cepat & hemat kuota</small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button id="btn-install" class="btn fw-bold flex-grow-1 shadow-sm" style="background: #1a535c; color: white; border-radius: 8px; font-size: 0.85rem; padding: 10px;">PASANG SEKARANG</button>
        <button id="btn-close" class="btn btn-light fw-bold" style="border-radius: 8px; font-size: 0.85rem; padding: 10px; border: 1px solid #ddd;">NANTI</button>
    </div>
</div>

<script>
    // Menu Toggle Script
    function toggleMenu() {
        const popup = document.getElementById('popupMenu');
        const backdrop = document.getElementById('menuBackdrop');
        popup.classList.toggle('active');
        backdrop.classList.toggle('active');
    }

    // PWA Installer Script
    let deferredPrompt;
    const installBanner = document.getElementById('install-banner');
    const btnInstall = document.getElementById('btn-install');
    const btnClose = document.getElementById('btn-close');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (!sessionStorage.getItem('pwa_banner_closed')) {
            setTimeout(() => {
                installBanner.style.display = 'block';
            }, 4000);
        }
    });

    btnInstall.addEventListener('click', (e) => {
        installBanner.style.display = 'none';
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            deferredPrompt = null;
        });
    });

    btnClose.addEventListener('click', () => {
        installBanner.style.display = 'none';
        sessionStorage.setItem('pwa_banner_closed', 'true');
    });

    window.addEventListener('appinstalled', (evt) => {
        installBanner.style.display = 'none';
    });
</script>
