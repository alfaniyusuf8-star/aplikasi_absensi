<?php
session_start();
include 'koneksi.php';

// HANYA SUPERADMIN YANG BOLEH MASUK
$allowed_levels = ['superadmin'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Akses Ditolak! Fitur Import Massal ini khusus untuk Superadmin.'); window.location='dashboard_keimaman.php';</script>"; 
    exit;
}

// ... (lanjutan kode import di bawahnya tetap sama) ...

if (isset($_POST['import'])) {
    $fileName = $_FILES["file_csv"]["tmp_name"];
    
    if ($_FILES["file_csv"]["size"] > 0) {
        $file = fopen($fileName, "r");
        fgetcsv($file, 10000, ","); // Lewati baris pertama (Header)
        
        $sukses = 0; $gagal = 0;

        // BACA BARIS PER BARIS (Ubah "," jadi ";" jika format Excel Anda titik koma)
        while (($col = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (empty(trim($col[0]))) continue; 

            // AMBIL SEMUA 27 KOLOM DARI EXCEL
            $username            = mysqli_real_escape_string($conn, strtolower(trim($col[0])));
            $kelompok            = mysqli_real_escape_string($conn, trim($col[1]));
            $nama_lengkap        = mysqli_real_escape_string($conn, trim($col[2]));
            $nama_panggilan      = mysqli_real_escape_string($conn, trim($col[3]));
            $jk                  = mysqli_real_escape_string($conn, trim($col[4])); // L atau P
            $tempat_lahir        = mysqli_real_escape_string($conn, trim($col[5]));
            $tanggal_lahir       = mysqli_real_escape_string($conn, trim($col[6])); // YYYY-MM-DD
            $no_hp               = mysqli_real_escape_string($conn, trim($col[7]));
            $hobi                = mysqli_real_escape_string($conn, trim($col[8]));
            $alamat_surabaya     = mysqli_real_escape_string($conn, trim($col[9]));
            $alamat_asal         = mysqli_real_escape_string($conn, trim($col[10]));
            $nama_ortu           = mysqli_real_escape_string($conn, trim($col[11]));
            $status_ortu         = mysqli_real_escape_string($conn, trim($col[12]));
            $hp_ortu             = mysqli_real_escape_string($conn, trim($col[13]));
            $tempat_sambung_ortu = mysqli_real_escape_string($conn, trim($col[14]));
            $darurat_nama        = mysqli_real_escape_string($conn, trim($col[15]));
            $darurat_hubungan    = mysqli_real_escape_string($conn, trim($col[16]));
            $darurat_hp          = mysqli_real_escape_string($conn, trim($col[17]));
            $status_mubaligh     = mysqli_real_escape_string($conn, trim($col[18]));
            $kegiatan_surabaya   = mysqli_real_escape_string($conn, trim($col[19]));
            $universitas         = mysqli_real_escape_string($conn, trim($col[20]));
            $jurusan             = mysqli_real_escape_string($conn, trim($col[21]));
            $angkatan            = mysqli_real_escape_string($conn, trim($col[22]));
            $tempat_kerja        = mysqli_real_escape_string($conn, trim($col[23]));
            $alamat_kerja        = mysqli_real_escape_string($conn, trim($col[24]));
            $jenjang             = mysqli_real_escape_string($conn, trim($col[25]));
            $status_pernikahan   = mysqli_real_escape_string($conn, trim($col[26]));
            
            // Perbaikan Tanggal Kosong agar tidak error di MySQL
            if(empty($tanggal_lahir)) $tanggal_lahir = '0000-00-00';
            
            $password = md5('123456'); // Password default
            $level    = 'karyawan';

            // 1. CEK USERNAME KEMBAR
            $cek_user = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$username'");
            if (mysqli_num_rows($cek_user) > 0) { $gagal++; continue; }

            // 2. INSERT KE TABEL USERS
            $sql_user = "INSERT INTO users (username, password, level, kelompok) VALUES ('$username', '$password', '$level', '$kelompok')";
            if (mysqli_query($conn, $sql_user)) {
                $id_user_baru = mysqli_insert_id($conn);
                
                // 3. INSERT FULL KE TABEL BIODATA
                $sql_biodata = "INSERT INTO biodata_jamaah (
                    id_user, nama_lengkap, nama_panggilan, jenis_kelamin, tempat_lahir, tanggal_lahir,
                    no_hp, hobi, alamat_surabaya, alamat_asal, nama_ortu, status_ortu, hp_ortu,
                    tempat_sambung_ortu, darurat_nama, darurat_hubungan, darurat_hp, status_mubaligh,
                    kegiatan_surabaya, universitas, jurusan, angkatan, tempat_kerja, alamat_kerja,
                    jenjang, status_pernikahan
                ) VALUES (
                    '$id_user_baru', '$nama_lengkap', '$nama_panggilan', '$jk', '$tempat_lahir', '$tanggal_lahir',
                    '$no_hp', '$hobi', '$alamat_surabaya', '$alamat_asal', '$nama_ortu', '$status_ortu', '$hp_ortu',
                    '$tempat_sambung_ortu', '$darurat_nama', '$darurat_hubungan', '$darurat_hp', '$status_mubaligh',
                    '$kegiatan_surabaya', '$universitas', '$jurusan', '$angkatan', '$tempat_kerja', '$alamat_kerja',
                    '$jenjang', '$status_pernikahan'
                )";
                
                if(mysqli_query($conn, $sql_biodata)){
                    $sukses++;
                } else {
                    mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_user_baru'");
                    $gagal++;
                }
            } else {
                $gagal++;
            }
        }
        fclose($file);
        echo "<script>alert('Selesai! Berhasil: $sukses akun. Gagal/Lewati: $gagal akun.'); window.location='data_jamaah.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Import Massal Jamaah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h2 class="fw-bold text-dark"><i class="fa fa-file-excel text-success me-2"></i>Import Akun & Biodata Full</h2>
            <p class="text-muted mb-0">Upload file CSV dengan 27 Kolom sesuai standar Database.</p>
        </div>
        <a href="data_jamaah.php" class="btn btn-outline-secondary fw-bold shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-success text-white border-0 p-3" style="border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0 fw-bold"><i class="fa fa-upload me-2"></i>Form Upload Data (27 Kolom)</h5>
                </div>
                <div class="card-body p-4 bg-white">
                    <div class="alert alert-warning border-warning shadow-sm mb-4">
                        <b>PENTING:</b> Pastikan urutan 27 kolom di Excel Anda persis seperti template. Kolom Tanggal Lahir harus berformat <code>YYYY-MM-DD</code>. Password default adalah <b>123456</b>.
                    </div>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Pilih File CSV Anda:</label>
                            <input type="file" name="file_csv" class="form-control form-control-lg border-success" accept=".csv" required>
                        </div>
                        <button type="submit" name="import" class="btn btn-success btn-lg w-100 fw-bold shadow-sm rounded-pill">
                            <i class="fa fa-rocket me-2"></i> Upload & Import Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>