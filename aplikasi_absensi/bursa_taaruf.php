<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$level = $_SESSION['level'];

// =========================================================================
// =========================================================================
// 1. CEK HAK AKSES (MODE PENGAWAS VS MODE JAMAAH)
// =========================================================================
$is_pengawas = in_array($level, ['superadmin', 'keimaman_desa', 'keimaman', 'tim_pnkb_desa', 'tim_pnkb']);

// =========================================================================
// 2. PROSES AJUKAN TA'ARUF & KIRIM NOTIFIKASI
// =========================================================================
if (isset($_POST['ajukan_taaruf'])) {
    if ($is_pengawas) {
        echo "<script>alert('Fasilitator/Pengurus tidak diperkenankan ikut mengajukan ta\'aruf melalui sistem!'); window.location='bursa_taaruf.php';</script>"; exit;
    }
    
    $id_kandidat = $_POST['id_kandidat'];
    $tgl_sekarang = date('Y-m-d');
    
    // Cek apakah sudah pernah mengajukan ke orang ini dan statusnya masih gantung
    $cek_ngajuin = mysqli_query($conn, "SELECT id_pengajuan FROM pengajuan_taaruf WHERE id_pengaju='$id_user' AND id_kandidat='$id_kandidat' AND status NOT IN ('Dibatalkan', 'Ditolak', 'Batal')");
    
    if (mysqli_num_rows($cek_ngajuin) > 0) {
        echo "<script>alert('Anda sudah pernah mengajukan ta\'aruf ke kandidat ini. Tim PNKB sedang memprosesnya.');</script>";
    } else {
        // Simpan ke database pengajuan
        mysqli_query($conn, "INSERT INTO pengajuan_taaruf (id_pengaju, id_kandidat, tanggal, status) VALUES ('$id_user', '$id_kandidat', '$tgl_sekarang', 'Menunggu')");
        
        // --- KIRIM NOTIFIKASI KE TIM PNKB / PENGURUS ---
        $q_nama = mysqli_query($conn, "SELECT nama_lengkap, kelompok FROM biodata_jamaah WHERE id_user = '$id_user'");
        $d_nama = mysqli_fetch_assoc($q_nama);
        $nama_pengaju = $d_nama['nama_lengkap'] ?? 'Seorang Jamaah';
        $kel_pengaju = $d_nama['kelompok'] ?? '';
        
        $pesan = "Ada pengajuan Ta'aruf baru masuk dari Sdr/i " . $nama_pengaju . " (Kel. " . $kel_pengaju . "). Harap segera dicek di menu Pantau Pengajuan.";
        
        // CARI PENGURUS DI TABEL UTAMA SEKALIGUS TABEL JABATAN RANGKAP (UNION)
        $q_admin = mysqli_query($conn, "
            SELECT id_user FROM users WHERE level IN ('tim_pnkb', 'tim_pnkb_desa', 'keimaman_desa', 'superadmin', 'ketua_mudai')
            UNION
            SELECT id_user FROM jabatan_rangkap WHERE level IN ('tim_pnkb', 'tim_pnkb_desa', 'keimaman_desa', 'superadmin', 'ketua_mudai')
        ");
        
        // JEBAKAN ERROR 2: Jika query pencarian pengurus gagal (misal tabel jabatan_rangkap tidak ada)
        if (!$q_admin) {
            die("ERROR CARI PENGURUS: " . mysqli_error($conn));
        }
        
        // Menghitung apakah ada pengurus yang ditemukan
        $jumlah_pengurus = mysqli_num_rows($q_admin);
        if ($jumlah_pengurus == 0) {
            die("ERROR KOSONG: Sistem tidak menemukan satupun akun Pengurus/PNKB di database untuk dikirimi notifikasi!");
        }
        
        while($adm = mysqli_fetch_assoc($q_admin)) {
            kirim_notif($conn, $adm['id_user'], "Pengajuan Ta'aruf Baru 💍", $pesan, "pantau_taaruf.php");
        }

        echo "<script>alert('Alhamdulillah! Pengajuan Ta\'aruf Anda telah dikirim ke Tim PNKB untuk diproses.'); window.location='pantau_taaruf.php';</script>"; exit;
    }
}

// =========================================================================
// 3. PROSES UPDATE KRITERIA (HANYA UNTUK JAMAAH BIASA)
// =========================================================================
if (isset($_POST['update_kriteria'])) {
    $is_siap = isset($_POST['is_siap_nikah']) ? 1 : 0;
    $target = mysqli_real_escape_string($conn, $_POST['target_menikah']);
    $kriteria = mysqli_real_escape_string($conn, $_POST['kriteria_idaman']);

    mysqli_query($conn, "UPDATE biodata_jamaah SET is_siap_nikah = '$is_siap', target_menikah = '$target', kriteria_idaman = '$kriteria' WHERE id_user = '$id_user'");
    echo "<script>alert('Alhamdulillah, Kriteria Ta\'aruf berhasil diperbarui!'); window.location='bursa_taaruf.php';</script>"; exit;
}

// =========================================================================
// 4. ATUR GEMBOK SYARIAT BERDASARKAN PERAN
// =========================================================================
$query_me = mysqli_query($conn, "SELECT * FROM biodata_jamaah WHERE id_user = '$id_user'");
$me = mysqli_fetch_assoc($query_me);

if (!$is_pengawas) {
    if (!$me) {
        echo "<script>alert('Harap lengkapi biodata Anda terlebih dahulu!'); window.location='isi_biodata.php';</script>"; exit;
    }
    
    // PROTEKSI MAKSIMAL: KHUSUS MUDA/I YANG BELUM MENIKAH
    if ($me['jenjang'] != 'Muda/i' || $me['status_pernikahan'] == 'Menikah') {
        echo "<script>alert('Akses Ditolak! Bursa Ta\'aruf ini EKSKLUSIF hanya untuk jamaah jenjang Muda/i yang belum menikah.'); window.location='dashboard.php';</script>"; exit;
    }
    
    if (empty($me['jenis_kelamin'])) {
        echo "<script>alert('Jenis kelamin Anda belum diisi di biodata!'); window.location='isi_biodata.php';</script>"; exit;
    }
    
    $my_gender = $me['jenis_kelamin'];
    $target_gender = ($my_gender == 'L') ? 'P' : 'L';
    $teks_target = ($target_gender == 'P') ? 'Mudi / Akhwat' : 'Muda / Ikhwan';
    
    $filter_gender_sql = "AND b.jenis_kelamin = '$target_gender'";
    $filter_siap_sql = ""; 

} else {
    $teks_target = 'Seluruh Muda-Mudi (Ikhwan & Akhwat)';
    $filter_gender_sql = ""; 
    $filter_siap_sql = ""; 
}

// =========================================================================
// 5. VARIABEL FILTER DARI URL (GET) (Filter Status Dihapus)
// =========================================================================
$f_umur   = $_GET['f_umur'] ?? '';
$f_target = $_GET['f_target'] ?? '';
$f_gender = $_GET['f_gender'] ?? '';

// =========================================================================
// 6. TARIK DATA KANDIDAT DARI DATABASE (HANYA MUDA/I)
// =========================================================================
$query_kandidat = mysqli_query($conn, "
    SELECT b.*, u.kelompok 
    FROM biodata_jamaah b 
    JOIN users u ON b.id_user = u.id_user 
    WHERE (b.status_pernikahan = 'Belum Menikah' OR b.status_pernikahan = 'Pernah Menikah')
    AND b.jenjang = 'Muda/i'
    $filter_siap_sql 
    $filter_gender_sql
    ORDER BY b.is_siap_nikah DESC, b.nama_lengkap ASC
");

$kandidat_list = [];
while($row = mysqli_fetch_assoc($query_kandidat)) {
    if($row['id_user'] == $id_user) continue; // Jangan tampilkan diri sendiri

    $umur = 0;
    if(!empty($row['tanggal_lahir']) && $row['tanggal_lahir'] != '0000-00-00') {
        $tgl_lahir = new DateTime($row['tanggal_lahir']);
        $sekarang = new DateTime('today');
        $umur = $tgl_lahir->diff($sekarang)->y;
    }
    $row['umur'] = ($umur > 0) ? $umur : '-';

    // APLIKASIKAN FILTER
    if($is_pengawas && !empty($f_gender) && $row['jenis_kelamin'] != $f_gender) continue;
    if(!empty($f_target) && $row['target_menikah'] != $f_target) continue;
    if(!empty($f_umur) && $umur > 0) {
        if($f_umur == '20_kebawah' && $umur > 20) continue;
        if($f_umur == '21_25' && ($umur < 21 || $umur > 25)) continue;
        if($f_umur == '26_30' && ($umur < 26 || $umur > 30)) continue;
        if($f_umur == '31_keatas' && $umur < 31) continue;
    }

    $kandidat_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bursa Ta'aruf | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fcf0f4; font-family: 'Inter', sans-serif; }
        .main-content { padding: 0 !important; padding-bottom: 90px !important; }
        
        /* HEADER KHUSUS TA'ARUF */
        body .main-content .header-taaruf { 
            background: linear-gradient(135deg, #d63384, #fd7e14) !important; 
            padding: 30px 20px 95px 20px !important; 
            color: white !important; 
            border-bottom-left-radius: 25px !important; 
            border-bottom-right-radius: 25px !important;
            box-shadow: 0 4px 15px rgba(214, 51, 132, 0.2) !important;
            position: relative !important; 
            overflow: hidden !important;
        }
        
        /* FILTER MENGAMBANG */
        body .main-content .filter-floating {
            margin: -50px 15px 20px 15px !important; 
            background: #fff !important; 
            padding: 12px !important; 
            border-radius: 15px !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08) !important; 
            border: 1px solid #ffb3c6 !important;
            position: relative !important; 
            z-index: 10 !important;
        }

        /* KARTU KANDIDAT */
        .kandidat-card {
            background: #fff; border-radius: 18px; overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;
            transition: 0.3s; position: relative; height: 100%; display: flex; flex-direction: column;
        }
        .kandidat-card:active { transform: scale(0.98); }
        .k-cover { height: 70px; background: #e2e8f0; }
        .k-cover.bg-ikhwan { background: linear-gradient(to right, #0dcaf0, #0d6efd); }
        .k-cover.bg-akhwat { background: linear-gradient(to right, #d63384, #fd7e14); }
        
        .k-avatar {
            width: 75px !important; height: 75px !important; border-radius: 50%; object-fit: cover;
            border: 3px solid #fff; margin-top: -37px; margin-left: auto; margin-right: auto;
            display: block; background: #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .badge-umur { position: absolute; top: 10px; right: 10px; font-size: 0.75rem; font-weight: bold; z-index: 2; border: 2px solid #fff; }
        .badge-siap { position: absolute; top: 10px; left: 10px; font-size: 0.7rem; font-weight: bold; z-index: 2; background: rgba(255,255,255,0.9); }
        
        .k-info { padding: 10px 12px 15px 12px; text-align: center; flex-grow: 1; }
        .k-name { font-weight: 800; color: #1e293b; font-size: 0.95rem; margin-bottom: 2px; line-height: 1.2; }
        .k-meta { font-size: 0.75rem; color: #64748b; margin-bottom: 10px; }
        
        .k-stats { font-size: 0.7rem; color: #475569; text-align: left; background: #f8fafc; padding: 10px; border-radius: 10px; margin-bottom: 10px; border: 1px solid #f1f5f9; }
        .k-stats div { margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .k-stats i { width: 15px; text-align: center; margin-right: 5px; }

        .btn-lihat { border-radius: 12px; font-weight: 700; font-size: 0.8rem !important; padding: 8px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-taaruf">
        <div class="position-relative mb-2" style="z-index: 2;">
            <?php if($is_pengawas): ?>
                <span class="badge bg-warning text-dark mb-2 rounded-pill shadow-sm" style="font-size: 0.65rem;"><i class="fa fa-eye me-1"></i> Mode Pengawas</span>
            <?php endif; ?>
            <h4 class="fw-bold mb-1"><i class="fa fa-heart me-2 text-warning"></i> Bursa Ta'aruf</h4>
            <p class="mb-3 opacity-75" style="font-size: 0.8rem;">Daftar profil <b><?= $teks_target; ?></b> Desa Semampir.</p>
            
            <div class="d-flex gap-2 flex-wrap mt-1">
                <?php if(!$is_pengawas || ($me && $me['status_pernikahan'] != 'Menikah')): ?>
                    <button class="btn btn-light btn-sm fw-bold shadow-sm rounded-pill px-3 text-danger" data-bs-toggle="modal" data-bs-target="#modalEditKriteria">
                        <i class="fa fa-pencil-alt me-1"></i> Update Status Saya
                    </button>
                <?php endif; ?>
                
                <a href="pantau_taaruf.php" class="btn btn-warning btn-sm fw-bold shadow-sm rounded-pill px-3 text-dark">
                    <i class="fa fa-envelope-open-text me-1"></i> Pantau Pengajuan
                </a>
            </div>
        </div>
        <i class="fa fa-ring position-absolute text-white opacity-10" style="font-size: 10rem; right: -20px; top: -10px; transform: rotate(-20deg); z-index: 1;"></i>
    </div>

    <div class="filter-floating">
        <button class="btn btn-sm btn-outline-danger w-100 fw-bold rounded-pill mb-1 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSaring">
            <span><i class="fa fa-filter me-2"></i>Saring Kandidat</span>
            <i class="fa fa-chevron-down"></i>
        </button>
        
        <div class="collapse <?= (!empty($f_umur) || !empty($f_target) || !empty($f_gender)) ? 'show' : ''; ?>" id="collapseSaring">
            <form action="" method="GET" class="row g-2 mt-1">
                <?php if($is_pengawas): ?>
                <div class="col-6">
                    <select name="f_gender" class="form-select form-select-sm border-danger text-secondary">
                        <option value="">L / P (Semua)</option>
                        <option value="L" <?= ($f_gender == 'L') ? 'selected' : ''; ?>>Ikhwan (L)</option>
                        <option value="P" <?= ($f_gender == 'P') ? 'selected' : ''; ?>>Akhwat (P)</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="<?= $is_pengawas ? 'col-6' : 'col-12'; ?>">
                    <select name="f_umur" class="form-select form-select-sm border-danger text-secondary">
                        <option value="">Semua Umur</option>
                        <option value="20_kebawah" <?= ($f_umur == '20_kebawah') ? 'selected' : ''; ?>>20 Thn ke Bawah</option>
                        <option value="21_25" <?= ($f_umur == '21_25') ? 'selected' : ''; ?>>21 - 25 Thn</option>
                        <option value="26_30" <?= ($f_umur == '26_30') ? 'selected' : ''; ?>>26 - 30 Thn</option>
                        <option value="31_keatas" <?= ($f_umur == '31_keatas') ? 'selected' : ''; ?>>31 Thn ke Atas</option>
                    </select>
                </div>

                <div class="col-12">
                    <select name="f_target" class="form-select form-select-sm border-danger text-secondary">
                        <option value="">Semua Target Menikah</option>
                        <option value="Tahun Ini" <?= ($f_target == 'Tahun Ini') ? 'selected' : ''; ?>>Tahun Ini</option>
                        <option value="1 Tahun Kedepan" <?= ($f_target == '1 Tahun Kedepan') ? 'selected' : ''; ?>>1 Thn Kedepan</option>
                        <option value="2 Tahun Kedepan" <?= ($f_target == '2 Tahun Kedepan') ? 'selected' : ''; ?>>2 Thn Kedepan</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-danger btn-sm fw-bold w-100 rounded-pill shadow-sm"><i class="fa fa-search me-1"></i> Cari</button>
                    <?php if(!empty($f_umur) || !empty($f_target) || !empty($f_gender)): ?>
                        <a href="bursa_taaruf.php" class="btn btn-light btn-sm fw-bold px-3 border rounded-pill"><i class="fa fa-times text-danger"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="px-3">
        <h6 class="text-muted fw-bold border-bottom pb-2 mb-3" style="font-size: 0.8rem;">Menampilkan <?= count($kandidat_list); ?> Kandidat</h6>

        <?php if(count($kandidat_list) == 0): ?>
            <div class="text-center py-5">
                <i class="fa fa-folder-open fa-3x text-muted opacity-25 mb-3"></i>
                <h6 class="text-muted fw-bold">Tidak ada kandidat yang sesuai filter.</h6>
            </div>
        <?php else: ?>
            <div class="row g-2">
                <?php 
                foreach($kandidat_list as $k): 
                    $is_ikhwan = ($k['jenis_kelamin'] == 'L');
                    $tema_bg = $is_ikhwan ? 'bg-ikhwan' : 'bg-akhwat';
                    $bg_badge = $is_ikhwan ? 'info text-dark' : 'danger';
                    
                    $inisial = strtoupper(substr($k['nama_lengkap'], 0, 1));
                    $foto = (!empty($k['foto']) && file_exists('uploads/'.$k['foto'])) ? 'uploads/'.$k['foto'] : "https://placehold.co/200x200?text=".$inisial."&font=Inter";
                ?>
                    <div class="col-6">
                        <div class="kandidat-card">
                            <div class="k-cover <?= $tema_bg; ?>"></div>
                            
                            <?php if($k['is_siap_nikah'] == 1): ?>
                                <span class="badge-siap text-success shadow-sm rounded-pill"><i class="fa fa-check-circle me-1"></i>Siap</span>
                            <?php else: ?>
                                <span class="badge-siap text-secondary shadow-sm rounded-pill border"><i class="fa fa-clock me-1"></i>Belum</span>
                            <?php endif; ?>
                            
                            <span class="badge bg-<?= $bg_badge; ?> badge-umur shadow-sm"><?= $k['umur']; ?> Th</span>
                            
                            <img src="<?= $foto; ?>" class="k-avatar" alt="Foto">
                            
                            <div class="k-info">
                                <div class="k-name text-truncate"><?= strtoupper($k['nama_lengkap']); ?></div>
                                <div class="k-meta"><i class="fa fa-map-marker-alt text-danger me-1"></i>Kel. <?= $k['kelompok']; ?></div>
                                
                                <div class="k-stats">
                                    <div><i class="fa fa-briefcase text-primary"></i> <?= $k['kegiatan_surabaya'] ?: '-'; ?></div>
                                    <div><i class="fa fa-bullseye text-warning"></i> <?= $k['target_menikah'] ?: '-'; ?></div>
                                </div>
                                
                                <?php if(!$is_pengawas): ?>
                                    <button class="btn btn-<?= $is_ikhwan ? 'info' : 'danger'; ?> btn-lihat w-100 shadow-sm" onclick="kirimPengajuan('<?= $k['id_user']; ?>', '<?= addslashes(strtoupper($k['nama_lengkap'])); ?>')">
                                        <i class="fa fa-envelope me-1"></i> Ajukan
                                    </button>
                                <?php else: ?>
                                    <a href="detail_jamaah.php?id=<?= $k['id_user']; ?>" class="btn btn-outline-dark btn-lihat w-100">
                                        <i class="fa fa-user me-1"></i> Detail
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if($me): ?>
<div class="modal fade" id="modalEditKriteria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-danger text-white border-0 p-3">
                <h6 class="modal-title fw-bold"><i class="fa fa-edit me-2"></i>Update Status Ta'aruf</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4 bg-light">
                    
                    <div class="form-check mb-4 p-3 bg-white rounded border border-danger shadow-sm">
                        <input class="form-check-input ms-1 me-3 border-danger" type="checkbox" name="is_siap_nikah" id="checkSiap" value="1" <?= ($me['is_siap_nikah'] == 1) ? 'checked' : ''; ?> style="transform: scale(1.6); cursor: pointer;">
                        <label class="form-check-label fw-bold text-dark mt-1 ms-2" for="checkSiap" style="font-size: 0.95rem; cursor: pointer;">
                            Bismillah, Saya Siap Menikah
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-danger fw-bold small">Target Menikah</label>
                        <select name="target_menikah" class="form-select border-danger fw-bold text-secondary">
                            <option value="" <?= empty($me['target_menikah']) ? 'selected' : ''; ?>>-- Pilih Target --</option>
                            <option value="Tahun Ini" <?= ($me['target_menikah'] == 'Tahun Ini') ? 'selected' : ''; ?>>Tahun Ini (Segera)</option>
                            <option value="1 Tahun Kedepan" <?= ($me['target_menikah'] == '1 Tahun Kedepan') ? 'selected' : ''; ?>>1 Tahun Kedepan</option>
                            <option value="2 Tahun Kedepan" <?= ($me['target_menikah'] == '2 Tahun Kedepan') ? 'selected' : ''; ?>>2 Tahun Kedepan</option>
                            <option value="Menunggu Lulus/Kerja" <?= ($me['target_menikah'] == 'Menunggu Lulus/Kerja') ? 'selected' : ''; ?>>Menunggu Lulus/Kerja</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-danger fw-bold small">Kriteria Pasangan Idaman</label>
                        <textarea name="kriteria_idaman" class="form-control border-danger" rows="4" placeholder="Contoh: Penyabar, paham agama..."><?= htmlspecialchars($me['kriteria_idaman'] ?? ''); ?></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-white border-0">
                    <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_kriteria" class="btn btn-danger rounded-pill fw-bold px-4 shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if(!$is_pengawas): ?>
<div class="modal fade" id="modalBantuanTaaruf" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-danger text-white border-0 p-3">
                <h6 class="modal-title fw-bold"><i class="fa fa-heart me-2"></i>Konfirmasi Pengajuan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4 text-center">
                    <input type="hidden" name="id_kandidat" id="input_id_kandidat_hidden">
                    <i class="fa fa-envelope-open-text fa-4x text-warning mb-3"></i>
                    <h6 class="fw-bold text-dark mb-1">Ajukan Ta'aruf ke:</h6>
                    <h5 id="teks_nama_kandidat" class="text-danger fw-bold mb-3"></h5>
                    
                    <div class="alert alert-danger text-start fw-bold mt-3 shadow-sm border-danger" style="font-size:0.75rem;">
                        Dengan menekan tombol Kirim, profil Anda akan diteruskan ke Tim PNKB. Tim akan menghubungi Anda untuk tahap pendampingan selanjutnya.
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ajukan_taaruf" class="btn btn-danger rounded-pill fw-bold px-4 shadow-sm">KIRIM</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function kirimPengajuan(id, nama) {
    document.getElementById('input_id_kandidat_hidden').value = id;
    document.getElementById('teks_nama_kandidat').innerText = nama;
    var myModal = new bootstrap.Modal(document.getElementById('modalBantuanTaaruf'));
    myModal.show();
}
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>