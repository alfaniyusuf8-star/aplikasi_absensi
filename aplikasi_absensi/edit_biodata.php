<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$cek_bio = mysqli_query($conn, "SELECT * FROM biodata_jamaah WHERE id_user = '$id_user'");
$d_bio = mysqli_fetch_assoc($cek_bio);

if (!$d_bio) {
    echo "<script>alert('Anda belum mengisi biodata. Silakan isi terlebih dahulu!'); window.location='isi_biodata.php';</script>";
    exit;
}

$foto_lama = $d_bio['foto'];

// AMBIL KELOMPOK USER SAAT INI UNTUK FILTER DAFTAR KEPALA KELUARGA
$q_kelompok = mysqli_query($conn, "SELECT kelompok FROM users WHERE id_user = '$id_user'");
$kel_user = mysqli_fetch_assoc($q_kelompok)['kelompok'];

// AMBIL DAFTAR "KEPALA KELUARGA" DI KELOMPOK YANG SAMA
$q_kk = mysqli_query($conn, "
    SELECT b.id_user, b.nama_lengkap 
    FROM biodata_jamaah b 
    JOIN users u ON b.id_user = u.id_user 
    WHERE b.status_keluarga = 'Kepala Keluarga' AND u.kelompok = '$kel_user'
    ORDER BY b.nama_lengkap ASC
");
$list_kk = [];
while($kk = mysqli_fetch_assoc($q_kk)){
    $list_kk[] = $kk;
}

function uploadFotoEdit($fotoLama) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    if ($_FILES['foto']['error'] === 4) {
        return $fotoLama; // Jika tidak upload foto baru, pakai foto lama
    }

    $namaFile   = $_FILES['foto']['name'];
    $ukuranFile = $_FILES['foto']['size'];
    $error      = $_FILES['foto']['error'];
    $tmpName    = $_FILES['foto']['tmp_name'];

    if ($error === 1 || $error === 2) {
        echo "<script>alert('Gagal: Ukuran foto Anda terlalu besar! Server PHP membatasi maksimal 2MB.');</script>";
        return false;
    }

    $ekstensiGambarValid = ['jpg', 'jpeg', 'png', 'webp'];
    $ekstensiGambar = explode('.', $namaFile);
    $ekstensiGambar = strtolower(end($ekstensiGambar));
    
    if (!in_array($ekstensiGambar, $ekstensiGambarValid)) {
        echo "<script>alert('Gagal: File bukan gambar! (Gunakan JPG, PNG, atau WEBP)');</script>";
        return false;
    }

    if ($ukuranFile > 5000000) {
        echo "<script>alert('Gagal: Ukuran foto maksimal 5MB!');</script>";
        return false;
    }

    $namaFileBaru = "foto_" . time() . "_" . uniqid() . '.' . $ekstensiGambar;
    if (move_uploaded_file($tmpName, $target_dir . $namaFileBaru)) {
        // Hapus foto lama jika ada dan bukan null
        if (!empty($fotoLama) && file_exists($target_dir . $fotoLama)) {
            unlink($target_dir . $fotoLama);
        }
        return $namaFileBaru;
    }
    return false;
}

if (isset($_POST['update_biodata'])) {
    $jenjang = $_POST['jenjang'];
    $foto = uploadFotoEdit($foto_lama);

    if ($foto === false) {
        // Error foto
    } elseif ($jenjang != 'Umum' && empty($foto)) {
         echo "<script>alert('Gagal: Untuk jenjang selain Umum, WAJIB memiliki foto profil!');</script>";
    } else {
        
        // TANGKAP DATA KELUARGA
        $status_keluarga = mysqli_real_escape_string($conn, $_POST['status_keluarga']);
        $id_kepala_keluarga = NULL;
        // Hanya wajibkan Kepala Keluarga jika statusnya BUKAN Kepala Keluarga dan BUKAN Perantau
        if($status_keluarga != 'Kepala Keluarga' && $status_keluarga != 'Tidak ada keluarga di Semampir (Perantau)' && !empty($_POST['id_kepala_keluarga'])) {
            $id_kepala_keluarga = mysqli_real_escape_string($conn, $_POST['id_kepala_keluarga']);
        }

        $nama_lengkap   = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
        $nama_panggilan = mysqli_real_escape_string($conn, $_POST['nama_panggilan']);
        $jenis_kelamin  = $_POST['jenis_kelamin'];
        $no_hp          = mysqli_real_escape_string($conn, $_POST['no_hp']);
        $alamat_asal    = mysqli_real_escape_string($conn, $_POST['alamat_asal']);
        
        $tempat_lahir   = !empty($_POST['tempat_lahir']) ? mysqli_real_escape_string($conn, $_POST['tempat_lahir']) : NULL;
        $tanggal_lahir  = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : NULL;
        $alamat_surabaya= !empty($_POST['alamat_surabaya']) ? mysqli_real_escape_string($conn, $_POST['alamat_surabaya']) : NULL;
        $nama_ortu      = !empty($_POST['nama_ortu']) ? mysqli_real_escape_string($conn, $_POST['nama_ortu']) : NULL;
        $hp_ortu        = !empty($_POST['hp_ortu']) ? mysqli_real_escape_string($conn, $_POST['hp_ortu']) : NULL;
        $status_ortu    = !empty($_POST['status_ortu']) ? $_POST['status_ortu'] : NULL;
        $sambung_ortu   = !empty($_POST['tempat_sambung_ortu']) ? mysqli_real_escape_string($conn, $_POST['tempat_sambung_ortu']) : NULL;
        $darurat_nama   = !empty($_POST['darurat_nama']) ? mysqli_real_escape_string($conn, $_POST['darurat_nama']) : NULL;
        $darurat_hub    = !empty($_POST['darurat_hubungan']) ? mysqli_real_escape_string($conn, $_POST['darurat_hubungan']) : NULL;
        $darurat_hp     = !empty($_POST['darurat_hp']) ? mysqli_real_escape_string($conn, $_POST['darurat_hp']) : NULL;
        
        $status_mubaligh= !empty($_POST['status_mubaligh']) ? $_POST['status_mubaligh'] : NULL;
        
        $kegiatan       = !empty($_POST['kegiatan_surabaya']) ? $_POST['kegiatan_surabaya'] : NULL;
        $tempat_kerja   = !empty($_POST['tempat_kerja']) ? mysqli_real_escape_string($conn, $_POST['tempat_kerja']) : NULL;
        $alamat_kerja   = !empty($_POST['alamat_kerja']) ? mysqli_real_escape_string($conn, $_POST['alamat_kerja']) : NULL;
        $universitas    = !empty($_POST['universitas']) ? mysqli_real_escape_string($conn, $_POST['universitas']) : NULL;
        $jurusan        = !empty($_POST['jurusan']) ? mysqli_real_escape_string($conn, $_POST['jurusan']) : NULL;
        $angkatan       = !empty($_POST['angkatan']) ? mysqli_real_escape_string($conn, $_POST['angkatan']) : NULL;
        $hobi           = !empty($_POST['hobi']) ? mysqli_real_escape_string($conn, $_POST['hobi']) : NULL;
    
        $foto_sql = ($foto === NULL) ? "foto = NULL" : "foto = '$foto'";
        $tgl_lahir_sql = ($tanggal_lahir === NULL) ? "tanggal_lahir = NULL" : "tanggal_lahir = '$tanggal_lahir'";
        $kk_sql = ($id_kepala_keluarga === NULL) ? "id_kepala_keluarga = NULL" : "id_kepala_keluarga = '$id_kepala_keluarga'";

        $query = "UPDATE biodata_jamaah SET 
                    jenjang = '$jenjang', 
                    nama_lengkap = '$nama_lengkap', 
                    nama_panggilan = '$nama_panggilan', 
                    jenis_kelamin = '$jenis_kelamin', 
                    no_hp = '$no_hp', 
                    alamat_asal = '$alamat_asal', 
                    $foto_sql, 
                    tempat_lahir = '$tempat_lahir', 
                    $tgl_lahir_sql, 
                    alamat_surabaya = '$alamat_surabaya', 
                    nama_ortu = '$nama_ortu', 
                    hp_ortu = '$hp_ortu', 
                    status_ortu = '$status_ortu', 
                    tempat_sambung_ortu = '$sambung_ortu', 
                    darurat_nama = '$darurat_nama', 
                    darurat_hubungan = '$darurat_hub', 
                    darurat_hp = '$darurat_hp', 
                    status_mubaligh = '$status_mubaligh', 
                    kegiatan_surabaya = '$kegiatan', 
                    tempat_kerja = '$tempat_kerja', 
                    alamat_kerja = '$alamat_kerja', 
                    universitas = '$universitas', 
                    jurusan = '$jurusan', 
                    angkatan = '$angkatan', 
                    hobi = '$hobi',
                    status_keluarga = '$status_keluarga',
                    $kk_sql
                  WHERE id_user = '$id_user'";
    
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Biodata berhasil diperbarui!'); window.location='isi_biodata.php';</script>";
        } else {
            echo "<script>alert('Gagal update database: " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Biodata | AbsenNgaji</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; padding-bottom: 50px; }
        .header-bg { background: #1a535c; padding: 30px 20px 70px 20px; color: white; border-radius: 0 0 30px 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-form { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 30px; margin-top: -40px; margin-bottom: 2rem;}
        
        .form-section-title { font-weight: 800; color: #1a535c; font-size: 1.1rem; border-bottom: 2px dashed #4ecdc4; padding-bottom: 8px; margin-bottom: 20px; margin-top: 35px; }
        .form-label { font-weight: 700; color: #333; font-size: 0.85rem; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 10px; padding: 12px 15px; border: 2px solid #e9ecef; font-size: 0.95rem; }
        .form-control:focus, .form-select:focus { border-color: #4ecdc4; box-shadow: none; }
        .label-wajib { color: #dc3545; font-weight: bold; display: none; }
        
        #blokKelahiran, #blokOrtu, #blokMudai, #blokHobi, #formKampus, #formKerja, #blokMubaligh, #blokKepalaKeluarga { transition: all 0.3s ease-in-out; }
        .img-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid #1a535c; margin-bottom: 10px; }

        @media (max-width: 576px) {
            .header-bg { padding: 30px 15px 60px 15px; border-radius: 0 0 20px 20px; }
            .card-form { padding: 25px 20px; margin-top: -30px; border-radius: 15px;}
        }
    </style>
</head>
<body>

<div class="header-bg relative">
    <div class="container p-0">
        <div class="d-flex align-items-center mb-2">
            <a href="isi_biodata.php" class="text-white text-decoration-none me-3"><i class="fa fa-arrow-left fa-lg"></i></a>
            <h4 class="fw-bold mb-0">Edit Biodata</h4>
        </div>
        <p class="mb-0 opacity-75 small ms-4 ps-2">Perbarui data diri Anda jika ada perubahan.</p>
    </div>
</div>

<div class="container px-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-form">
                <form action="" method="POST" enctype="multipart/form-data">
                    
                    <h5 class="form-section-title mt-0"><i class="fa fa-user me-2 text-primary"></i>Informasi Dasar</h5>
                    <div class="row g-3">
                        
                        <div class="col-md-6">
                            <label class="form-label text-primary">Jenjang Usia *</label>
                            <select name="jenjang" id="jenjang" class="form-select border-primary" style="background-color: #f0f8ff;" onchange="cekJenjang()" required>
                                <option value="Umum" <?= ($d_bio['jenjang'] == 'Umum') ? 'selected' : ''; ?>>Umum (Bapak/Ibu)</option>
                                <option value="Muda/i" <?= ($d_bio['jenjang'] == 'Muda/i') ? 'selected' : ''; ?>>Muda/i</option>
                                <option value="Remaja" <?= ($d_bio['jenjang'] == 'Remaja') ? 'selected' : ''; ?>>Remaja</option>
                                <option value="Pra Remaja" <?= ($d_bio['jenjang'] == 'Pra Remaja') ? 'selected' : ''; ?>>Pra Remaja</option>
                                <option value="Caberawit" <?= ($d_bio['jenjang'] == 'Caberawit') ? 'selected' : ''; ?>>Caberawit</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-info"><i class="fa fa-sitemap me-1"></i>Status dalam Keluarga *</label>
                            <select name="status_keluarga" id="status_keluarga" class="form-select border-info" onchange="cekKeluarga()" required>
                                <option value="" disabled <?= empty($d_bio['status_keluarga']) ? 'selected' : ''; ?>>-- Pilih Status --</option>
                                <option value="Kepala Keluarga" <?= ($d_bio['status_keluarga'] == 'Kepala Keluarga') ? 'selected' : ''; ?>>Kepala Keluarga (Bapak/Suami)</option>
                                <option value="Istri" <?= ($d_bio['status_keluarga'] == 'Istri') ? 'selected' : ''; ?>>Istri</option>
                                <option value="Anak" <?= ($d_bio['status_keluarga'] == 'Anak') ? 'selected' : ''; ?>>Anak</option>
                                <option value="Lainnya" <?= ($d_bio['status_keluarga'] == 'Lainnya') ? 'selected' : ''; ?>>Famili Lainnya</option>
                                <option value="Tidak ada keluarga di Semampir (Perantau)" <?= ($d_bio['status_keluarga'] == 'Tidak ada keluarga di Semampir (Perantau)') ? 'selected' : ''; ?>>Tidak ada keluarga di Semampir (Perantau)</option>
                            </select>
                        </div>

                        <div class="col-12" id="blokKepalaKeluarga" style="display: none;">
                            <div class="bg-info bg-opacity-10 p-3 rounded-4 border border-info shadow-sm">
                                <label class="form-label text-dark"><i class="fa fa-link me-1"></i>Tautkan ke Kepala Keluarga (Bapak) *</label>
                                <select name="id_kepala_keluarga" id="id_kepala_keluarga" class="form-select border-info fw-bold text-dark">
                                    <option value="" disabled <?= empty($d_bio['id_kepala_keluarga']) ? 'selected' : ''; ?>>-- Cari Nama Kepala Keluarga Anda --</option>
                                    <?php foreach($list_kk as $kk): ?>
                                        <option value="<?= $kk['id_user']; ?>" <?= ($d_bio['id_kepala_keluarga'] == $kk['id_user']) ? 'selected' : ''; ?>><?= $kk['nama_lengkap']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">
                                    <i class="fa fa-info-circle text-info me-1"></i> Jika nama Bapak/Suami Anda belum muncul, minta beliau mengisi biodatanya terlebih dahulu dengan status "Kepala Keluarga".
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6" id="blokMubaligh" style="display: none;">
                            <label class="form-label text-success"><i class="fa fa-star me-1"></i>Status Kemubalighan *</label>
                            <select name="status_mubaligh" id="status_mubaligh" class="form-select border-success fw-bold text-success" style="background-color: #f8fff9;">
                                <option value="">-- Pilih --</option>
                                <option value="MT" <?= ($d_bio['status_mubaligh'] == 'MT') ? 'selected' : ''; ?>>MT (Mubaligh / Mubalighot Tugas)</option>
                                <option value="Non MT" <?= ($d_bio['status_mubaligh'] == 'Non MT') ? 'selected' : ''; ?>>Non MT (Jamaah Biasa)</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4 bg-light p-3 rounded-4 border border-light shadow-sm text-center">
                            <label class="form-label d-block text-start">Ubah Foto Profil <span id="labelFotoWajib" class="label-wajib">* (Wajib)</span></label>
                            
                            <?php if(!empty($d_bio['foto']) && file_exists('uploads/'.$d_bio['foto'])): ?>
                                <img src="uploads/<?= $d_bio['foto']; ?>" class="img-preview mb-2" alt="Foto Lama">
                            <?php else: ?>
                                <div class="mb-2 text-muted small"><i class="fa fa-user-circle fa-4x opacity-50"></i><br>Belum ada foto</div>
                            <?php endif; ?>
                            
                            <input type="file" name="foto" id="inputFoto" class="form-control bg-white" accept="image/png, image/jpeg, image/jpg, image/webp">
                            <div class="form-text small mt-2 text-muted text-start" id="keteranganFoto"><i class="fa fa-info-circle me-1"></i>Format: JPG/PNG/WEBP. Max 2MB. (Abaikan jika tidak ingin mengubah foto)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($d_bio['nama_lengkap']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Panggilan *</label>
                            <input type="text" name="nama_panggilan" class="form-control" value="<?= htmlspecialchars($d_bio['nama_panggilan']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP / WhatsApp *</label>
                            <input type="number" name="no_hp" class="form-control" value="<?= htmlspecialchars($d_bio['no_hp']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin *</label>
                            <div class="d-flex gap-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jenis_kelamin" value="L" id="jkL" <?= ($d_bio['jenis_kelamin'] == 'L') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="jkL">Laki-laki</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jenis_kelamin" value="P" id="jkP" <?= ($d_bio['jenis_kelamin'] == 'P') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="jkP">Perempuan</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Asal / Tempat Tinggal *</label>
                            <textarea name="alamat_asal" class="form-control" rows="2" required><?= htmlspecialchars($d_bio['alamat_asal']); ?></textarea>
                        </div>
                    </div>

                    <div id="blokKelahiran" style="display: none;">
                        <h5 class="form-section-title"><i class="fa fa-birthday-cake me-2 text-warning"></i>Data Kelahiran</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" class="form-control" value="<?= htmlspecialchars($d_bio['tempat_lahir'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" class="form-control" value="<?= $d_bio['tanggal_lahir']; ?>">
                            </div>
                        </div>
                    </div>

                    <div id="blokOrtu" style="display: none;">
                        <h5 class="form-section-title"><i class="fa fa-users me-2 text-info"></i>Data Orang Tua</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" id="labelOrtu">Nama Orang Tua (Ayah)</label>
                                <input type="text" name="nama_ortu" class="form-control" value="<?= htmlspecialchars($d_bio['nama_ortu'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. HP Orang Tua</label>
                                <input type="number" name="hp_ortu" class="form-control" value="<?= htmlspecialchars($d_bio['hp_ortu'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Orang Tua</label>
                                <select name="status_ortu" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <option value="Jamaah" <?= ($d_bio['status_ortu'] == 'Jamaah') ? 'selected' : ''; ?>>Jamaah</option>
                                    <option value="Belum" <?= ($d_bio['status_ortu'] == 'Belum') ? 'selected' : ''; ?>>Belum Jamaah</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="blokSambungOrtu">
                                <label class="form-label">Tempat Sambung Orang Tua</label>
                                <input type="text" name="tempat_sambung_ortu" class="form-control" value="<?= htmlspecialchars($d_bio['tempat_sambung_ortu'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div id="blokMudai" style="display: none;">
                        <h5 class="form-section-title"><i class="fa fa-graduation-cap me-2 text-success"></i>Data Tambahan (Muda/i)</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Alamat di Surabaya (Kost/Kontrakan)</label>
                                <textarea name="alamat_surabaya" class="form-control" rows="2"><?= htmlspecialchars($d_bio['alamat_surabaya'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3 text-danger"><i class="fa fa-phone-alt me-2"></i>Kontak Darurat di Surabaya</h6>
                        <div class="row g-3 bg-light p-3 rounded-4 border border-light">
                            <div class="col-md-4"><label class="form-label">Nama Kontak</label><input type="text" name="darurat_nama" class="form-control" value="<?= htmlspecialchars($d_bio['darurat_nama'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Hubungan</label><input type="text" name="darurat_hubungan" class="form-control" value="<?= htmlspecialchars($d_bio['darurat_hubungan'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">No. HP Darurat</label><input type="number" name="darurat_hp" class="form-control" value="<?= htmlspecialchars($d_bio['darurat_hp'] ?? ''); ?>"></div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3 text-dark"><i class="fa fa-briefcase me-2"></i>Pendidikan & Pekerjaan</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Kegiatan Saat Ini</label>
                                <select name="kegiatan_surabaya" id="kegiatan_surabaya" class="form-select" onchange="cekKegiatan()">
                                    <option value="">-- Pilih --</option>
                                    <option value="Bekerja" <?= ($d_bio['kegiatan_surabaya'] == 'Bekerja') ? 'selected' : ''; ?>>Bekerja</option>
                                    <option value="Kuliah" <?= ($d_bio['kegiatan_surabaya'] == 'Kuliah') ? 'selected' : ''; ?>>Kuliah</option>
                                    <option value="Lainnya" <?= ($d_bio['kegiatan_surabaya'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-3 bg-light p-3 rounded-4 border border-info" id="formKampus" style="display: none;">
                            <div class="col-md-4"><label class="form-label">Universitas</label><input type="text" name="universitas" class="form-control" value="<?= htmlspecialchars($d_bio['universitas'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Jurusan</label><input type="text" name="jurusan" class="form-control" value="<?= htmlspecialchars($d_bio['jurusan'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Angkatan (Tahun)</label><input type="number" name="angkatan" class="form-control" value="<?= htmlspecialchars($d_bio['angkatan'] ?? ''); ?>"></div>
                        </div>

                        <div class="row g-3 mt-3 bg-light p-3 rounded-4 border border-warning" id="formKerja" style="display: none;">
                            <div class="col-md-6"><label class="form-label">Tempat Kerja</label><input type="text" name="tempat_kerja" class="form-control" value="<?= htmlspecialchars($d_bio['tempat_kerja'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Alamat Kerja</label><textarea name="alamat_kerja" class="form-control" rows="1"><?= htmlspecialchars($d_bio['alamat_kerja'] ?? ''); ?></textarea></div>
                        </div>
                    </div>

                    <div id="blokHobi" style="display: none;" class="mt-4">
                        <label class="form-label">Hobi / Minat</label>
                        <input type="text" name="hobi" class="form-control" value="<?= htmlspecialchars($d_bio['hobi'] ?? ''); ?>">
                    </div>

                    <hr class="mt-5 mb-4 border-secondary">
                    <button type="submit" name="update_biodata" class="btn btn-warning w-100 py-3 fw-bold fs-5 rounded-pill shadow-sm" style="border:none;">
                        <i class="fa fa-save me-2"></i> UPDATE BIODATA
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function cekKeluarga() {
        var status = document.getElementById('status_keluarga').value;
        var blokKK = document.getElementById('blokKepalaKeluarga');
        var inputKK = document.getElementById('id_kepala_keluarga');

        // Jika statusnya bukan Kepala Keluarga DAN bukan Perantau, TAMPILKAN
        if (status !== '' && status !== 'Kepala Keluarga' && status !== 'Tidak ada keluarga di Semampir (Perantau)') {
            blokKK.style.display = 'block';
            inputKK.required = true;
        } else {
            // Jika Perantau atau Kepala Keluarga, SEMBUNYIKAN
            blokKK.style.display = 'none';
            inputKK.required = false;
            inputKK.value = "";
        }
    }

    function cekJenjang() {
        var j = document.getElementById('jenjang').value;
        var blokKelahiran = document.getElementById('blokKelahiran');
        var blokOrtu = document.getElementById('blokOrtu');
        var blokMudai = document.getElementById('blokMudai');
        var blokHobi = document.getElementById('blokHobi');
        var lblOrtu = document.getElementById('labelOrtu');
        var blokSambungOrtu = document.getElementById('blokSambungOrtu');
        
        var blokMubaligh = document.getElementById('blokMubaligh');
        var statusMubaligh = document.getElementById('status_mubaligh');
        
        var punyaFotoLama = <?= !empty($d_bio['foto']) ? 'true' : 'false' ?>;
        var inputFoto = document.getElementById('inputFoto');
        var labelFotoWajib = document.getElementById('labelFotoWajib');
        var keteranganFoto = document.getElementById('keteranganFoto');

        var statusKel = document.getElementById('status_keluarga');
        // Jika belum ada isian status keluarga sama sekali, lalu ia pilih anak-anak, force jadi Anak
        if ((j === 'Caberawit' || j === 'Pra Remaja' || j === 'Remaja') && statusKel.value === '') {
            statusKel.value = 'Anak';
        }
        cekKeluarga(); // Selalu panggil cek keluarga untuk update tampilan dropdown KK

        if (j === 'Muda/i') {
            blokKelahiran.style.display = 'block';
            blokOrtu.style.display = 'block';
            blokMudai.style.display = 'block';
            blokHobi.style.display = 'block';
            blokSambungOrtu.style.display = 'block';
            lblOrtu.innerHTML = "Nama Orang Tua";
            
            blokMubaligh.style.display = 'block'; 
            statusMubaligh.required = true;
            
            inputFoto.required = !punyaFotoLama;
            labelFotoWajib.style.display = punyaFotoLama ? 'none' : 'inline';
            keteranganFoto.innerHTML = "<i class='fa fa-info-circle me-1'></i>Abaikan jika tidak ingin mengubah foto profil.";
        } 
        else if (j === 'Umum') {
            blokKelahiran.style.display = 'none';
            blokOrtu.style.display = 'none';
            blokMudai.style.display = 'none';
            blokHobi.style.display = 'none';
            
            blokMubaligh.style.display = 'block'; 
            statusMubaligh.required = true;
            
            inputFoto.required = false;
            labelFotoWajib.style.display = 'none';
            keteranganFoto.innerHTML = "<i class='fa fa-info-circle me-1'></i>Abaikan jika tidak ingin mengubah foto profil.";
        }
        else if (j === 'Remaja' || j === 'Pra Remaja' || j === 'Caberawit') {
            blokKelahiran.style.display = 'block'; 
            blokOrtu.style.display = 'block';
            blokMudai.style.display = 'none';
            blokHobi.style.display = 'block';
            blokSambungOrtu.style.display = 'none'; 
            lblOrtu.innerHTML = "Nama Orang Tua (Ayah) *"; 
            
            blokMubaligh.style.display = 'none'; 
            statusMubaligh.required = false;
            statusMubaligh.value = "";
            
            inputFoto.required = !punyaFotoLama;
            labelFotoWajib.style.display = punyaFotoLama ? 'none' : 'inline';
            keteranganFoto.innerHTML = "<i class='fa fa-info-circle me-1'></i>Abaikan jika tidak ingin mengubah foto profil.";
        } 
    }

    function cekKegiatan() {
        var k = document.getElementById('kegiatan_surabaya').value;
        document.getElementById('formKampus').style.display = (k === 'Kuliah') ? 'flex' : 'none';
        document.getElementById('formKerja').style.display = (k === 'Bekerja') ? 'flex' : 'none';
    }

    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('jenjang').value !== "") {
            cekJenjang();
        }
        if(document.getElementById('kegiatan_surabaya').value !== "") {
            cekKegiatan();
        }
    });
</script>
</body>
</html>