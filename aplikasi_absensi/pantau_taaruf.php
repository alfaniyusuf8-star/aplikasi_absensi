<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$id_user = $_SESSION['id_user'];
$level_aktif = $_SESSION['level']; 
$kel_user = $_SESSION['kelompok'] ?? '';

// 1. DAFTAR LEVEL YANG DIIZINKAN MASUK (Admin Dihapus)
$allowed_levels = ['superadmin', 'keimaman_desa', 'keimaman', 'tim_pnkb_desa', 'tim_pnkb', 'karyawan'];

if (!isset($_SESSION['id_user']) || !in_array($level_aktif, $allowed_levels)) {
    echo "<script>alert('Akses Ditolak! Hanya Tim PNKB dan Keimaman yang berhak memantau data ini.'); window.location='dashboard.php';</script>"; exit;
}

// 2. IDENTIFIKASI PERAN AKSES
$is_pusat = in_array($level_aktif, ['superadmin', 'keimaman_desa', 'tim_pnkb_desa']);
$is_pengurus_kelompok = in_array($level_aktif, ['keimaman', 'tim_pnkb']);
$is_jamaah_biasa = ($level_aktif == 'karyawan');

// 3. PROSES UPDATE STATUS (HANYA UNTUK PENGURUS)
if (isset($_POST['update_status']) && ($is_pusat || $is_pengurus_kelompok)) {
    // Escape string untuk mencegah error jika catatan mengandung tanda petik (')
    $id_pengajuan = mysqli_real_escape_string($conn, $_POST['id_pengajuan']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan_pnkb']);
    
    $query_update = "UPDATE pengajuan_taaruf SET status='$status_baru', catatan_pnkb='$catatan' WHERE id_pengajuan='$id_pengajuan'";
    $update = mysqli_query($conn, $query_update);
    
    if($update) {
        // Kirim Notifikasi ke Pengaju & Kandidat
        $q_orang = mysqli_query($conn, "SELECT id_pengaju, id_kandidat FROM pengajuan_taaruf WHERE id_pengajuan='$id_pengajuan'");
        $d_orang = mysqli_fetch_assoc($q_orang);
        
        // Notif ke Pengaju
        kirim_notif($conn, $d_orang['id_pengaju'], "Update Ta'aruf 💍", "Status pengajuan Anda telah diperbarui menjadi: $status_baru", "pantau_taaruf.php");
        
        // JIKA STATUS SUDAH SL ATAU ND, Beri Notif juga ke Kandidat!
        if(in_array($status_baru, ['Proses SL', 'Proses ND'])) {
            kirim_notif($conn, $d_orang['id_kandidat'], "Kabar Gembira 💍", "Ada proses ta'aruf untuk Anda yang telah memasuki tahap $status_baru. Cek sekarang!", "pantau_taaruf.php");
        }
        
        echo "<script>alert('Status Berhasil Diperbarui!'); window.location='pantau_taaruf.php';</script>";
    } else {
        // Tampilkan error jika gagal
        echo "<script>alert('GAGAL UPDATE KE DATABASE! Error: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
    exit;
}

// 4. LOGIKA FILTER DATA SYARIAT & WEWENANG (KUNCI UTAMA)
if ($is_pusat) {
    // Level Desa: Lihat semua data se-Desa
    $filter_sql = "1=1"; 
} elseif ($is_pengurus_kelompok) {
    // Level Kelompok: Lihat jika Pengaju ATAU Kandidat dari kelompoknya
    $filter_sql = "(u1.kelompok = '$kel_user' OR u2.kelompok = '$kel_user')";
} else {
    // Level Jamaah Biasa:
    // Bisa melihat jika DIA yang mengajukan (Pengaju)
    // ATAU bisa melihat jika dia adalah Kandidat, TAPI syaratnya status harus sudah 'Proses SL' atau 'Proses ND'
    $filter_sql = "(
        p.id_pengaju = '$id_user' 
        OR 
        (p.id_kandidat = '$id_user' AND p.status IN ('Proses SL', 'Proses ND'))
    )";
}

// 5. TARIK DATA PENGAJUAN (Diurutkan dari ID paling baru)
$query_pengajuan = mysqli_query($conn, "
    SELECT p.*, 
           b1.nama_lengkap AS nama_pengaju, u1.kelompok AS kel_pengaju, b1.jenis_kelamin AS jk_pengaju,
           b2.nama_lengkap AS nama_kandidat, u2.kelompok AS kel_kandidat, b2.jenis_kelamin AS jk_kandidat
    FROM pengajuan_taaruf p
    JOIN biodata_jamaah b1 ON p.id_pengaju = b1.id_user
    JOIN users u1 ON p.id_pengaju = u1.id_user
    JOIN biodata_jamaah b2 ON p.id_kandidat = b2.id_user
    JOIN users u2 ON p.id_kandidat = u2.id_user
    WHERE $filter_sql
    ORDER BY p.id_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pantau Ta'aruf | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_mobile.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; font-size: 0.85rem; }
        .main-content { padding: 0 !important; padding-bottom: 90px !important; }
        .header-title { background: #fff; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; z-index: 1020; }
        
        .taaruf-card { background: #fff; border-radius: 15px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.03); margin-bottom: 15px; overflow: hidden; }
        .tc-header { padding: 12px 15px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .tc-body { padding: 15px; position: relative; }
        
        .person-box { display: flex; align-items: center; gap: 12px; background: #fff; position: relative; z-index: 2; }
        .p-avatar { width: 45px; height: 45px; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 1.2rem; }
        .bg-ikhwan { background: linear-gradient(135deg, #0dcaf0, #0d6efd); }
        .bg-akhwat { background: linear-gradient(135deg, #d63384, #fd7e14); }
        
        .p-info { flex-grow: 1; overflow: hidden; }
        .p-name { font-weight: 800; color: #1e293b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .p-role { font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;}
        
        .tc-connector { position: absolute; left: 36px; top: 40px; bottom: 40px; width: 2px; border-left: 2px dashed #cbd5e1; z-index: 1; }
        .tc-footer { padding: 12px 15px; border-top: 1px dashed #e2e8f0; background: #f8fafc; }
        .catatan-box { font-size: 0.75rem; background: #fff; border-left: 3px solid #ffc107; padding: 10px; border-radius: 8px; border: 1px solid #ffeeba;}
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header-title shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0"><i class="fa fa-envelope-open-text text-danger me-2"></i>Pantau Pengajuan</h6>
                <small class="text-muted" style="font-size:0.7rem;">
                    <?php 
                        if($is_pusat) echo "Mode Desa (Seluruh Data)";
                        elseif($is_pengurus_kelompok) echo "Mode Kelompok: ".$kel_user;
                        else echo "Riwayat Pengajuan";
                    ?>
                </small>
            </div>
            <?php if(!$is_pengurus_kelompok && !$is_pusat): ?>
                <a href="bursa_taaruf.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">Cari</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="px-3 pt-3">
        <?php if(mysqli_num_rows($query_pengajuan) == 0): ?>
            <div class="text-center py-5 mt-4">
                <i class="fa fa-folder-open fa-3x text-muted opacity-25 mb-3"></i>
                <h6 class="fw-bold text-muted">Belum ada data pengajuan.</h6>
            </div>
        <?php else: ?>
            <?php while($row = mysqli_fetch_assoc($query_pengajuan)): 
                $st = $row['status'];
                $bg_status = 'bg-warning text-dark';
                
                // LOGIKA PEWARNAAN STATUS BARU (SL & ND)
                if($st == 'Diproses PNKB') $bg_status = 'bg-primary text-white';
                if($st == 'Proses SL') $bg_status = 'bg-info text-dark';
                if($st == 'Proses ND') $bg_status = 'bg-success text-white';
                if($st == 'Dibatalkan' || $st == 'Ditolak' || $st == 'Batal') $bg_status = 'bg-danger text-white';
                
                // Penanda Dinamis untuk Jamaah
                $lbl_pengaju = "PIHAK PENGAJU";
                $lbl_kandidat = "KANDIDAT TUJUAN";
                if($is_jamaah_biasa) {
                    if($row['id_pengaju'] == $id_user) $lbl_pengaju = "SAYA (PENGAJU)";
                    if($row['id_kandidat'] == $id_user) $lbl_kandidat = "SAYA (KANDIDAT)";
                }
            ?>
                <div class="taaruf-card">
                    <div class="tc-header">
                        <span class="fw-bold text-muted" style="font-size:0.75rem;"><i class="fa fa-calendar me-1"></i> <?= date('d M Y', strtotime($row['tanggal'])); ?></span>
                        <span class="badge <?= $bg_status; ?> rounded-pill shadow-sm px-2 py-1" style="font-size: 0.7rem;"><?= $st; ?></span>
                    </div>

                    <div class="tc-body">
                        <div class="tc-connector"></div>
                        
                        <div class="person-box mb-4">
                            <div class="p-avatar <?= ($row['jk_pengaju']=='P')?'bg-akhwat':'bg-ikhwan'; ?> shadow-sm">
                                <i class="fa <?= ($row['jk_pengaju']=='P')?'fa-female':'fa-male'; ?>"></i>
                            </div>
                            <div class="p-info">
                                <div class="p-role text-primary"><?= $lbl_pengaju; ?></div>
                                <div class="p-name"><?= strtoupper($row['nama_pengaju']); ?></div>
                                <span class="badge bg-light text-dark border" style="font-size:0.6rem;">Kel. <?= $row['kel_pengaju']; ?></span>
                            </div>
                        </div>

                        <div class="person-box">
                            <div class="p-avatar <?= ($row['jk_kandidat']=='P')?'bg-akhwat':'bg-ikhwan'; ?> shadow-sm">
                                <i class="fa <?= ($row['jk_kandidat']=='P')?'fa-female':'fa-male'; ?>"></i>
                            </div>
                            <div class="p-info">
                                <div class="p-role text-danger"><?= $lbl_kandidat; ?></div>
                                <div class="p-name"><?= strtoupper($row['nama_kandidat']); ?></div>
                                <span class="badge bg-light text-dark border" style="font-size:0.6rem;">Kel. <?= $row['kel_kandidat']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="tc-footer">
                        <?php if($is_pusat || $is_pengurus_kelompok): ?>
                            <button class="btn btn-dark w-100 btn-sm rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id_pengajuan']; ?>">
                                <i class="fa fa-edit me-1 text-warning"></i> Tindak Lanjut PNKB
                            </button>
                        <?php else: ?>
                            <div class="catatan-box shadow-sm">
                                <strong class="text-dark"><i class="fa fa-info-circle me-1 text-warning"></i> Pesan PNKB:</strong><br>
                                <span class="text-muted"><?= $row['catatan_pnkb'] ?: 'Menunggu verifikasi tim PNKB...'; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(!$is_jamaah_biasa): ?>
                <div class="modal fade" id="modalEdit<?= $row['id_pengajuan']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
                            <form method="POST" action="">
                                <div class="modal-header bg-danger text-white border-0 p-3">
                                    <h6 class="modal-title fw-bold"><i class="fa fa-tasks me-2"></i>Update Progress Ta'aruf</h6>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4 bg-light">
                                    <input type="hidden" name="id_pengajuan" value="<?= $row['id_pengajuan']; ?>">
                                    
                                    <div class="alert alert-warning small p-3 border-warning shadow-sm fw-bold">
                                        <i class="fa fa-info-circle me-1"></i> Jika status diubah menjadi Proses SL/ND, pihak kandidat akan otomatis bisa melihat pengajuan ini.
                                    </div>
                                    
                                    <label class="form-label fw-bold small text-dark">Status Pengajuan:</label>
                                    <select name="status_baru" class="form-select mb-3 border-danger shadow-sm fw-bold text-secondary">
                                        <option value="Menunggu" <?= ($st=='Menunggu')?'selected':''; ?>>Menunggu Respon</option>
                                        <option value="Diproses PNKB" <?= ($st=='Diproses PNKB')?'selected':''; ?>>Sedang Diproses PNKB</option>
                                        <option value="Proses SL" <?= ($st=='Proses SL')?'selected':''; ?>>Proses SL (Sambung Lamaran)</option>
                                        <option value="Proses ND" <?= ($st=='Proses ND')?'selected':''; ?>>Proses ND (Nikah Dalam)</option>
                                        <option value="Dibatalkan" <?= ($st=='Dibatalkan' || $st=='Batal' || $st=='Ditolak')?'selected':''; ?>>Dibatalkan / Tidak Cocok</option>
                                    </select>

                                    <label class="form-label fw-bold small text-dark">Catatan untuk Jamaah:</label>
                                    <textarea name="catatan_pnkb" class="form-control border-danger shadow-sm" rows="3" placeholder="Contoh: Alhamdulillah proses SL berjalan lancar, persiapan menuju ND..."><?= htmlspecialchars($row['catatan_pnkb']); ?></textarea>
                                </div>
                                <div class="modal-footer bg-white border-0 p-3">
                                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border shadow-sm" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="update_status" class="btn btn-danger flex-grow-1 rounded-pill fw-bold shadow-sm">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>