<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// 1. PROTEKSI HAK AKSES (Mencakup Semua Jenjang & Desa)
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'keimaman', 'keimaman_desa', 'ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    header("Location: login.php");
    exit;
}

$id_user  = $_SESSION['id_user'];
$level    = $_SESSION['level'];
$kelompok = $_SESSION['kelompok'];

// =========================================================================
// CEK HAK AKSES DASAR
// =========================================================================
$is_superadmin = ($level == 'superadmin');
$is_admin_desa = ($level == 'admin_desa');

// Yang TIDAK BOLEH Edit Data (Hanya Pemantau)
$can_edit = !in_array($level, ['keimaman', 'keimaman_desa', 'ketua_mudai', 'ketua_mudai_desa']); 

// =========================================================================
// LOGIKA FILTER KELOMPOK (WILAYAH)
// =========================================================================
// Tingkat Desa (Inti & MM Desa) vs Tingkat Kelompok (MM Kel, Remaja, Pra, Cabe)
if (in_array($level, ['superadmin', 'admin_desa', 'keimaman_desa', 'ketua_mudai_desa', 'admin_mudai_desa']) || $kelompok == 'Semua' || empty($kelompok)) {
    $filter_wilayah = "1"; // Tembus ke semua wilayah
    $show_filter_kelompok = true; 
} else {
    $filter_wilayah = "u.kelompok = '$kelompok'"; // Terkunci di kelompoknya masing-masing
    $show_filter_kelompok = false; 
}

// =========================================================================
// LOGIKA FILTER JENJANG (BERDASARKAN JABATAN)
// =========================================================================
if (in_array($level, ['ketua_mudai', 'admin_mudai', 'ketua_mudai_desa', 'admin_mudai_desa'])) {
    $filter_jenjang_sql = "(b.jenjang = 'Muda/i' OR b.jenjang IS NULL OR b.jenjang = '')";
    $show_filter_jenjang = false; 
} elseif ($level == 'admin_remaja') {
    $filter_jenjang_sql = "(b.jenjang = 'Remaja' OR b.jenjang IS NULL OR b.jenjang = '')";
    $show_filter_jenjang = false; 
} elseif ($level == 'admin_praremaja') {
    $filter_jenjang_sql = "(b.jenjang = 'Pra Remaja' OR b.jenjang IS NULL OR b.jenjang = '')";
    $show_filter_jenjang = false; 
} elseif ($level == 'admin_caberawit') {
    $filter_jenjang_sql = "(b.jenjang = 'Caberawit' OR b.jenjang IS NULL OR b.jenjang = '')";
    $show_filter_jenjang = false; 
} else {
    // Inti: Superadmin, Keimaman, Admin Utama
    $filter_jenjang_sql = "1";
    $show_filter_jenjang = true; 
}

// Sembunyikan akun Superadmin dari layar selain Superadmin itu sendiri
$hide_superadmin_sql = $is_superadmin ? "1" : "u.level != 'superadmin'";

// =========================================================================
// LOGIKA TAMBAH USER
// =========================================================================
if (isset($_POST['tambah_user']) && $can_edit) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $level_baru = $is_superadmin ? mysqli_real_escape_string($conn, $_POST['level_baru']) : 'karyawan';
    $kelompok_baru = ($is_superadmin || $is_admin_desa) ? mysqli_real_escape_string($conn, $_POST['kelompok_baru']) : $kelompok;
    $tgl_daftar = date('Y-m-d H:i:s');

    $cek = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Gagal! Username sudah digunakan.'); window.location='data_jamaah.php';</script>";
    } else {
        mysqli_query($conn, "INSERT INTO users (username, password, level, kelompok, tgl_daftar) VALUES ('$username', '$password', '$level_baru', '$kelompok_baru', '$tgl_daftar')");
        $id_baru = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO biodata_jamaah (id_user) VALUES ('$id_baru')");
        echo "<script>alert('Berhasil! " . ($level_baru == 'karyawan' ? "Jamaah" : "Pengurus") . " telah ditambahkan.'); window.location='data_jamaah.php';</script>";
    }
}

// =========================================================================
// LOGIKA HAPUS USER
// =========================================================================
if (isset($_GET['hapus_user']) && $can_edit) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus_user']);
    
    $q_target = mysqli_query($conn, "SELECT level, kelompok FROM users WHERE id_user = '$id_hapus'");
    $target_data = mysqli_fetch_assoc($q_target);
    
    $boleh_hapus = false;
    if ($is_superadmin) {
        $boleh_hapus = true;
    } elseif ($is_admin_desa) {
        if (!in_array($target_data['level'], ['superadmin', 'admin_desa'])) {
            $boleh_hapus = true;
        }
    } else {
        // Admin Kelompok hanya boleh hapus kelompoknya dan tidak boleh hapus pengurus inti
        if($target_data['kelompok'] == $kelompok && !in_array($target_data['level'], ['superadmin', 'admin_desa', 'keimaman_desa'])) {
            $boleh_hapus = true;
        }
    }

    if ($boleh_hapus) {
        mysqli_query($conn, "DELETE FROM presensi WHERE id_user = '$id_hapus'");
        mysqli_query($conn, "DELETE FROM perizinan WHERE id_user = '$id_hapus'");
        mysqli_query($conn, "DELETE FROM biodata_jamaah WHERE id_user = '$id_hapus'");
        mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_hapus'");
        echo "<script>alert('Data berhasil dihapus permanen!'); window.location='data_jamaah.php';</script>";
    } else {
        echo "<script>alert('Akses Ditolak! Anda tidak berwenang menghapus akun ini.'); window.location='data_jamaah.php';</script>";
    }
}

$q_mubaligh = mysqli_query($conn, "SELECT DISTINCT status_mubaligh FROM biodata_jamaah WHERE status_mubaligh IS NOT NULL AND status_mubaligh != ''");
$q_list_kelompok = mysqli_query($conn, "SELECT DISTINCT kelompok FROM users WHERE kelompok != 'Semua' AND kelompok IS NOT NULL AND kelompok != '' ORDER BY kelompok ASC");

// EKSEKUSI QUERY JAMAAH BERDASARKAN FILTER YANG SUDAH JADI
$query_jamaah = "
    SELECT u.id_user, u.username, u.level, u.kelompok AS kelompok_user, 
           b.*, TIMESTAMPDIFF(YEAR, b.tanggal_lahir, CURDATE()) AS umur 
    FROM users u 
    LEFT JOIN biodata_jamaah b ON u.id_user = b.id_user 
    WHERE ($hide_superadmin_sql) AND ($filter_wilayah) AND ($filter_jenjang_sql) 
    ORDER BY u.kelompok ASC, b.jenjang ASC, b.nama_lengkap ASC
";
$result = mysqli_query($conn, $query_jamaah);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Database Jamaah | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        
        .main-content { padding: 0 !important; padding-bottom: 90px !important; }
        .header-title { background: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; }
        
        /* Modifikasi DataTables agar List View (Anti Tumpuk Horisontal) */
        table.dataTable thead { display: none; } 
        table.dataTable tbody td { padding: 0 !important; border: none !important; } 
        table.dataTable { border-collapse: collapse !important; border: none !important; margin-top: 0 !important; }
        
        .dataTables_wrapper .row:first-child { padding: 10px 15px; background: #fff; border-bottom: 1px solid #f1f5f9; margin: 0; }
        div.dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 20px; padding: 6px 15px; outline: none; width: 100%; font-size: 0.9rem;}
        div.dataTables_filter label { width: 100%; color: transparent; font-size: 0; }
        div.dataTables_filter label input::placeholder { color: #94a3b8; }
        div.dataTables_length, div.dataTables_info { display: none; } 
        .dataTables_wrapper .row:last-child { padding: 15px; } 

        .list-item-card { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px 20px; border-bottom: 1px solid #f1f5f9; 
            background: #fff; transition: background 0.2s; 
        }
        .list-item-card:active { background: #f8fafc; }
        .avatar-wrapper { position: relative; margin-right: 15px; }
        .j-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; background: #f1f5f9; }
        .j-info { flex-grow: 1; overflow: hidden; }
        .j-name { font-weight: 700; color: #1e293b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
        .j-meta { font-size: 0.75rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        
        .badge-custom { font-size: 0.65rem; padding: 3px 8px; border-radius: 6px; font-weight: 700; }
        .action-btns { display: flex; gap: 8px; }
        .btn-icon-only { width: 35px; height: 35px; border-radius: 10px; display: flex; justify-content: center; align-items: center; padding: 0; }

        @media print {
            body * { visibility: hidden; }
            #modalLihatQR .modal-content, #modalLihatQR .modal-content * { visibility: visible; }
            #modalLihatQR .modal-content { position: absolute; left: 50%; top: 20px; transform: translateX(-50%); width: 350px; border: 2px solid #000 !important; box-shadow: none !important; }
            .modal-footer { display: none !important; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-title shadow-sm">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-database text-primary me-2"></i>Data Jamaah</h5>
            <small class="text-muted"><?= mysqli_num_rows($result); ?> Terdaftar di Ranah Anda</small>
        </div>
        <div class="d-flex gap-2">
            <?php if($can_edit): ?>
                <button class="btn btn-primary btn-sm rounded-pill fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fa fa-user-plus me-1"></i> Tambah
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white px-3 py-2 border-bottom shadow-sm">
        <button class="btn btn-light btn-sm w-100 fw-bold text-secondary d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilter">
            <span><i class="fa fa-sliders-h me-2"></i> Filter Lanjutan</span>
            <i class="fa fa-chevron-down"></i>
        </button>
        
        <div class="collapse mt-3" id="collapseFilter">
            <div class="row g-2 pb-2">
                <?php if($show_filter_kelompok): ?>
                <div class="col-6">
                    <select id="filter_kelompok" class="form-select form-select-sm bg-light">
                        <option value="">Semua Kelompok</option>
                        <option value="Semampir">Semampir</option>
                        <option value="Keputih">Keputih</option>
                        <option value="Praja">Praja</option>
                        <?php while($k = mysqli_fetch_assoc($q_list_kelompok)): if(!in_array($k['kelompok'], ['Semampir', 'Keputih', 'Praja', 'Semua'])): ?>
                            <option value="<?= htmlspecialchars($k['kelompok']); ?>"><?= htmlspecialchars($k['kelompok']); ?></option>
                        <?php endif; endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if($show_filter_jenjang): ?>
                <div class="col-6">
                    <select id="filter_jenjang" class="form-select form-select-sm bg-light">
                        <option value="">Semua Jenjang</option>
                        <option value="Umum">Umum</option>
                        <option value="Muda/i">Muda/i</option>
                        <option value="Remaja">Remaja</option>
                        <option value="Pra Remaja">Pra Remaja</option>
                        <option value="Caberawit">Caberawit</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-4">
                    <select id="filter_jk" class="form-select form-select-sm bg-light">
                        <option value="">L / P</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="col-8">
                    <select id="filter_mubaligh" class="form-select form-select-sm bg-light">
                        <option value="">Status Mubaligh (Semua)</option>
                        <option value="-">Bukan Mubaligh / Kosong</option>
                        <?php while($m = mysqli_fetch_assoc($q_mubaligh)): ?>
                            <option value="<?= htmlspecialchars($m['status_mubaligh']); ?>"><?= strtoupper($m['status_mubaligh']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0">Umur</span>
                        <input type="number" id="min_umur" class="form-control bg-light" placeholder="Min">
                        <span class="input-group-text bg-light border-start-0 border-end-0">-</span>
                        <input type="number" id="max_umur" class="form-control bg-light" placeholder="Max">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table id="tabelJamaah" class="table" style="width:100%">
        <thead><tr><th>Data</th></tr></thead>
        <tbody>
            <?php while ($d = mysqli_fetch_assoc($result)): 
                $kode_qr = "ABSENGAJI-" . $d['id_user'] . "-" . strtoupper(substr(md5($d['username']), 0, 5));
                $nama_tampil = !empty($d['nama_lengkap']) ? $d['nama_lengkap'] : $d['username'];
                $inisial = strtoupper(substr($nama_tampil, 0, 1));
                $foto = (!empty($d['foto']) && file_exists('uploads/'.$d['foto'])) ? 'uploads/'.$d['foto'] : "https://placehold.co/100x100?text=".$inisial."&font=Inter";
                
                $nama_b64 = base64_encode($nama_tampil);
                $kel_b64 = base64_encode($d['kelompok_user']);
                $mubaligh_attr = !empty($d['status_mubaligh']) ? $d['status_mubaligh'] : '-';
                
                $badge_color = 'bg-secondary';
                if($d['jenjang'] == 'Muda/i') $badge_color = 'bg-success';
                elseif($d['jenjang'] == 'Umum') $badge_color = 'bg-primary';
                elseif(in_array($d['jenjang'], ['Remaja', 'Pra Remaja'])) $badge_color = 'bg-warning text-dark';
                elseif($d['jenjang'] == 'Caberawit') $badge_color = 'bg-info text-dark';
            ?>
            <tr data-kel="<?= htmlspecialchars($d['kelompok_user']); ?>" 
                data-jen="<?= htmlspecialchars($d['jenjang'] ?? ''); ?>" 
                data-jk="<?= htmlspecialchars($d['jenis_kelamin'] ?? ''); ?>" 
                data-mub="<?= htmlspecialchars($mubaligh_attr); ?>" 
                data-umur="<?= $d['umur'] ?? 0; ?>">
                
                <td>
                    <div class="list-item-card">
                        <div class="d-flex align-items-center w-100 pe-2" onclick="window.location='detail_jamaah.php?id=<?= $d['id_user']; ?>'" style="cursor:pointer;">
                            <div class="avatar-wrapper">
                                <img src="<?= $foto; ?>" class="j-avatar" alt="Foto">
                            </div>
                            <div class="j-info">
                                <div class="j-name">
                                    <?= htmlspecialchars($nama_tampil); ?>
                                    <?php if($d['level'] != 'karyawan'): ?>
                                        <i class="fa fa-shield-alt text-danger ms-1" style="font-size:0.7rem;" title="Pengurus"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="j-meta">
                                    <span class="badge <?= $badge_color; ?> bg-opacity-25 <?= strpos($badge_color, 'text-dark') === false ? 'text-'.str_replace('bg-', '', $badge_color) : 'text-dark'; ?> badge-custom border border-<?= str_replace('bg-', '', $badge_color); ?> border-opacity-25">
                                        <?= strtoupper($d['jenjang'] ?: 'BARU'); ?>
                                    </span>
                                    <span><i class="fa fa-map-marker-alt text-danger opacity-75 me-1"></i><?= $d['kelompok_user']; ?></span>
                                    <span>• <?= $d['jenis_kelamin'] ?: '-'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-btns">
                            <button type="button" class="btn btn-dark btn-icon-only shadow-sm" onclick="lihatQR('<?= $kode_qr; ?>', '<?= $nama_b64; ?>', '<?= $d['jenjang'] ?? '-'; ?>', '<?= $kel_b64; ?>')"><i class="fa fa-qrcode"></i></button>
                            <?php if($can_edit): ?>
                                <a href="?hapus_user=<?= $d['id_user']; ?>" onclick="return confirm('YAKIN HAPUS AKUN INI?')" class="btn btn-danger btn-icon-only shadow-sm"><i class="fa fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

<?php if($can_edit): ?>
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fa fa-user-plus me-2"></i>Tambah Akun Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <?php if($is_superadmin): ?>
                        <div class="mb-3">
                            <label class="fw-bold text-dark">Pilih Jabatan (Level) <span class="text-danger">*</span></label>
                            <select name="level_baru" class="form-select bg-light border-primary fw-bold text-danger" required>
                                <option value="karyawan">Jamaah Biasa (Karyawan)</option>
                                <option value="admin_desa">Admin Desa</option>
                                <option value="admin">Admin Kelompok</option>
                                <option value="keimaman_desa">Keimaman Desa</option>
                                <option value="keimaman">Keimaman Kelompok</option>
                                <option value="ketua_mudai_desa">Ketua Muda/i Desa</option>
                                <option value="admin_mudai_desa">Admin Muda/i Desa</option>
                                <option value="ketua_mudai">Ketua Muda/i Kelompok</option>
                                <option value="admin_mudai">Admin Muda/i Kelompok</option>
                                <option value="admin_remaja">Admin Remaja</option>
                                <option value="admin_praremaja">Admin Pra Remaja</option>
                                <option value="admin_caberawit">Admin Caberawit</option>
                                <option value="tim_dhuafa_desa">Tim Dhuafa Desa</option>
                                <option value="tim_dhuafa">Tim Dhuafa Kelompok</option>
                                <option value="tim_pnkb_desa">Tim PNKB Desa</option>
                                <option value="tim_pnkb">Tim PNKB Kelompok</option>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="level_baru" value="karyawan">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="fw-bold text-dark">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control bg-light" placeholder="Buat username tanpa spasi" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-dark">Password <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control bg-light" placeholder="Buat password default" required>
                    </div>

                    <?php if($is_superadmin || $is_admin_desa): ?>
                        <div class="mb-3">
                            <label class="fw-bold text-dark">Alokasi Kelompok <span class="text-danger">*</span></label>
                            <select name="kelompok_baru" class="form-select bg-light border-primary" required>
                                <option value="">-- Pilih Kelompok --</option>
                                <option value="Semampir">Semampir</option>
                                <option value="Keputih">Keputih</option>
                                <option value="Praja">Praja</option>
                                <?php if($is_superadmin): ?><option value="Semua">Semua (Khusus Admin Desa / Pusat)</option><?php endif; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="fw-bold text-dark">Alokasi Kelompok (Terkunci)</label>
                            <input type="text" class="form-control bg-secondary text-white fw-bold" value="<?= htmlspecialchars($kelompok); ?>" disabled>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info py-2 px-3 small border-0 mt-3 mb-0">
                        <i class="fa fa-info-circle me-1"></i> Arahkan pemilik akun untuk login dan melengkapi <b>Biodata</b>.
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" name="tambah_user" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm py-2">SIMPAN AKUN</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalLihatQR" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden; background: #fff;">
            <div class="bg-dark text-white text-center p-3">
                <h5 class="fw-bold mb-0"><i class="fa fa-id-badge me-2 text-warning"></i>KARTU QR</h5>
            </div>
            <div class="modal-body text-center p-4">
                <div class="bg-light p-2 rounded-3 mb-4 border shadow-sm mx-auto" style="width: 220px; height: 220px;">
                    <img id="imgQR" src="" alt="QR Code" class="img-fluid border border-3 border-dark rounded">
                </div>
                <div class="border border-primary border-2 border-dashed rounded p-3 bg-light text-start">
                    <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.7rem;">NAMA</small>
                    <h6 id="textNamaQR" class="fw-bold text-dark mb-3"></h6>
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted fw-bold d-block" style="font-size: 0.7rem;">JENJANG</small>
                            <span id="textJenjangQR" class="fw-bold text-primary"></span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted fw-bold d-block" style="font-size: 0.7rem;">KELOMPOK</small>
                            <span id="textKelompokQR" class="fw-bold text-success"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4 pb-4 flex-column">
                <button type="button" class="btn btn-outline-dark w-100 fw-bold rounded-pill mb-2" onclick="window.print()"><i class="fa fa-print me-1"></i> CETAK KARTU</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
// Filter Khusus DataTables Tetap Dipertahankan
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
    var row = $(settings.aoData[dataIndex].nTr); 
    var kel = row.attr('data-kel'); 
    var jen = row.attr('data-jen'); 
    var jk = row.attr('data-jk');
    var mub = row.attr('data-mub'); 
    var umur = parseFloat(row.attr('data-umur')) || 0;
    
    var f_kel = $('#filter_kelompok').length ? $('#filter_kelompok').val() : '';
    var f_jen = $('#filter_jenjang').length ? $('#filter_jenjang').val() : '';
    var f_jk = $('#filter_jk').val(); 
    var f_mub = $('#filter_mubaligh').val();
    var f_min = parseInt($('#min_umur').val(), 10); 
    var f_max = parseInt($('#max_umur').val(), 10);

    if (f_kel && f_kel !== kel) return false;
    if (f_jen && f_jen !== jen) return false;
    if (f_jk && f_jk !== jk) return false;
    if (f_mub && f_mub !== mub) return false;
    if (!isNaN(f_min) && umur < f_min) return false;
    if (!isNaN(f_max) && umur > f_max) return false;
    return true; 
});

$(document).ready(function() {
    var table = $('#tabelJamaah').DataTable({ 
        "language": { "search": "", "searchPlaceholder": "Cari data cepat..." },
        "ordering": false,
        "pageLength": 15,
        "dom": '<"row"<"col-sm-12"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });
    $('#filter_kelompok, #filter_jenjang, #filter_jk, #filter_mubaligh, #min_umur, #max_umur').on('change keyup', function() { 
        table.draw(); 
    });
});

function lihatQR(kode, namaB64, jenjang, kelB64) {
    document.getElementById('imgQR').src = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" + kode;
    document.getElementById('textNamaQR').innerText = atob(namaB64).toUpperCase();
    document.getElementById('textJenjangQR').innerText = jenjang;
    document.getElementById('textKelompokQR').innerText = atob(kelB64).toUpperCase();
    new bootstrap.Modal(document.getElementById('modalLihatQR')).show();
}
</script>
</body>
</html>