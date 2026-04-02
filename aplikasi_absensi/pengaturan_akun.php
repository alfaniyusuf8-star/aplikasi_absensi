<?php
session_start();
include 'koneksi.php';

// Proteksi agar hanya user yang sudah login yang bisa masuk
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$pesan = '';
$status_pesan = '';

// Ambil data akun jamaah saat ini
$q_user = mysqli_query($conn, "SELECT username, password FROM users WHERE id_user = '$id_user'");
$d_user = mysqli_fetch_assoc($q_user);

// Proses saat tombol simpan ditekan
if (isset($_POST['simpan_akun'])) {
    $user_baru = mysqli_real_escape_string($conn, trim($_POST['username']));
    $pass_lama = mysqli_real_escape_string($conn, $_POST['pass_lama']);
    $pass_baru = mysqli_real_escape_string($conn, $_POST['pass_baru']);
    $pass_konf = mysqli_real_escape_string($conn, $_POST['pass_konf']);

    // 1. KECERDASAN PENDETEKSI FORMAT PASSWORD
    $pass_valid = false;
    $tipe_pass = 'plain'; // Default: teks biasa
    
    if ($pass_lama == $d_user['password']) {
        $pass_valid = true;
        $tipe_pass = 'plain';
    } elseif (md5($pass_lama) == $d_user['password']) {
        $pass_valid = true;
        $tipe_pass = 'md5';
    } elseif (password_verify($pass_lama, $d_user['password'])) {
        $pass_valid = true;
        $tipe_pass = 'bcrypt';
    }

    // 2. VALIDASI DAN UPDATE
    if (!$pass_valid) {
        $pesan = "Gagal! Password Lama yang Anda masukkan salah.";
        $status_pesan = "danger";
    } else {
        // Cek apakah username baru sudah dipakai orang lain
        $cek_user = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$user_baru' AND id_user != '$id_user'");
        if (mysqli_num_rows($cek_user) > 0) {
            $pesan = "Username '$user_baru' sudah digunakan. Silakan gunakan kombinasi lain.";
            $status_pesan = "warning";
        } else {
            $update_pass = $d_user['password'];
            $lanjut_update = true;

            // Jika kolom password baru diisi (artinya ingin ganti password)
            if (!empty($pass_baru)) {
                if ($pass_baru != $pass_konf) {
                    $pesan = "Gagal! Konfirmasi password baru tidak cocok.";
                    $status_pesan = "danger";
                    $lanjut_update = false;
                } else {
                    // Enkripsi password baru sesuai format database Anda sebelumnya
                    if ($tipe_pass == 'md5') {
                        $update_pass = md5($pass_baru);
                    } elseif ($tipe_pass == 'bcrypt') {
                        $update_pass = password_hash($pass_baru, PASSWORD_DEFAULT);
                    } else {
                        $update_pass = $pass_baru; 
                    }
                }
            }

            if ($lanjut_update) {
                mysqli_query($conn, "UPDATE users SET username = '$user_baru', password = '$update_pass' WHERE id_user = '$id_user'");
                
                // Update session jika username diganti
                if(isset($_SESSION['username'])) { $_SESSION['username'] = $user_baru; }
                $d_user['username'] = $user_baru; // Update UI langsung
                
                $pesan = "Alhamdulillah! Data akun Anda berhasil diperbarui.";
                $status_pesan = "success";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengaturan Akun | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-control { border-radius: 10px; border: 2px solid #e9ecef; padding: 12px 15px; }
        .form-control:focus { border-color: #1a535c; box-shadow: none; }
        .input-group-text { background: transparent; border: 2px solid #e9ecef; border-left: none; cursor: pointer; border-radius: 0 10px 10px 0; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold text-dark mb-0"><i class="fa fa-user-lock text-primary me-2"></i>Pengaturan Akun</h2>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            
            <?php if(!empty($pesan)): ?>
                <div class="alert alert-<?= $status_pesan; ?> alert-dismissible fade show fw-bold shadow-sm" role="alert">
                    <i class="fa <?= ($status_pesan == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i> <?= $pesan; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom bg-white p-4 shadow-sm border-top border-primary border-4">
                <div class="text-center mb-4 pb-3 border-bottom">
                    <i class="fa fa-user-shield fa-4x text-secondary mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">Ubah Username & Password</h5>
                    <p class="text-muted small">Demi keamanan, pastikan Anda menggunakan password yang tidak mudah ditebak.</p>
                </div>

                <form action="" method="POST">
                    
                    <div class="mb-4">
                        <label class="fw-bold text-dark mb-2">Username Anda (Untuk Login)</label>
                        <input type="text" name="username" class="form-control fw-bold text-primary" value="<?= $d_user['username']; ?>" required>
                    </div>

                    <div class="p-4 bg-light rounded border border-secondary shadow-sm mb-4">
                        <div class="mb-3">
                            <label class="fw-bold text-danger mb-2">Password Saat Ini <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="pass_lama" class="form-control border-danger" placeholder="Masukkan password yang sekarang..." required id="passLama">
                                <span class="input-group-text border-danger" onclick="togglePass('passLama', this)"><i class="fa fa-eye text-muted"></i></span>
                            </div>
                            <small class="text-muted">Wajib diisi sebagai bukti bahwa ini adalah Anda.</small>
                        </div>
                        
                        <hr class="opacity-25 my-4">

                        <div class="mb-3">
                            <label class="fw-bold text-dark mb-2">Password Baru <span class="text-muted fw-normal">(Opsional)</span></label>
                            <div class="input-group">
                                <input type="password" name="pass_baru" class="form-control" placeholder="Biarkan kosong jika tidak ingin ganti password" id="passBaru">
                                <span class="input-group-text" onclick="togglePass('passBaru', this)"><i class="fa fa-eye text-muted"></i></span>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="fw-bold text-dark mb-2">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="pass_konf" class="form-control" placeholder="Ketik ulang password baru..." id="passKonf">
                                <span class="input-group-text" onclick="togglePass('passKonf', this)"><i class="fa fa-eye text-muted"></i></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="simpan_akun" class="btn btn-primary w-100 fw-bold py-3 fs-5 shadow-sm mt-2 rounded-pill">
                        <i class="fa fa-save me-2"></i> SIMPAN PERUBAHAN
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fungsi untuk menampilkan/menyembunyikan password (Mata)
    function togglePass(inputId, iconSpan) {
        var input = document.getElementById(inputId);
        var icon = iconSpan.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            icon.classList.replace('text-muted', 'text-primary');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            icon.classList.replace('text-primary', 'text-muted');
        }
    }
</script>
</body>
</html>