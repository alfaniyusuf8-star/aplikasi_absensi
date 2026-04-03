<?php
session_start();
include 'koneksi.php';

// 1. CEK JIKA SUDAH LOGIN, ARAHKAN KE DASHBOARD YANG BENAR
if (isset($_SESSION['id_user'])) {
    $level = $_SESSION['level'];
    if (in_array($level, ['superadmin', 'admin_desa', 'admin', 'keimaman_desa', 'keimaman', 'ketua_mudai_desa', 'admin_mudai_desa', 'ketua_mudai', 'admin_mudai', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'])) {
        header("Location: dashboard_keimaman.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

// 2. PROSES LOGIN
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); // Menggunakan escape string untuk keamanan dasar teks biasa

    // QUERY DENGAN LEFT JOIN UNTUK MENGAMBIL NAMA DARI TABEL BIODATA_JAMAAH
    $query_text = "SELECT u.*, b.nama_lengkap 
                   FROM users u 
                   LEFT JOIN biodata_jamaah b ON u.id_user = b.id_user 
                   WHERE u.username = '$username'";
    
    $query = mysqli_query($conn, $query_text);

    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // PENGECEKAN PASSWORD TEKS BIASA (PLAIN TEXT)
        if ($password == $data['password']) {
            
            $_SESSION['id_user']   = $data['id_user'];
            $_SESSION['username']  = $data['username'];
            $_SESSION['level']     = $data['level'];
            $_SESSION['kelompok']  = $data['kelompok'];

            // LOGIKA PENGAMBILAN NAMA:
            // Jika nama_lengkap di biodata_jamaah ada isinya, pakai itu.
            $_SESSION['nama'] = !empty($data['nama_lengkap']) ? $data['nama_lengkap'] : $data['username'];

            // REDIRECT BERDASARKAN LEVEL PENGURUS
            $akses_pengurus = ['superadmin', 'admin_desa', 'admin', 'keimaman_desa', 'keimaman', 'ketua_mudai_desa', 'admin_mudai_desa', 'ketua_mudai', 'admin_mudai', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
            
            if (in_array($data['level'], $akses_pengurus)) {
                header("Location: dashboard_keimaman.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            echo "<script>alert('Password yang Anda masukkan salah!');</script>";
        }
    } else {
        echo "<script>alert('Username tidak terdaftar! Silakan hubungi Admin.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login | GemaSemampir</title>
    <link rel="manifest" href="manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #1a535c; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: #ffffff; border-radius: 20px; padding: 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); width: 100%; max-width: 400px; border: none; }
        .btn-custom { background: #4ecdc4; color: #1a535c; font-weight: bold; border: none; border-radius: 12px; transition: 0.3s; }
        .btn-custom:hover { background: #3dbab1; transform: translateY(-2px); }
        .form-control { border-radius: 12px; padding: 12px; background: #f8fafc; }
        .input-group-text { border-radius: 12px 0 0 12px; background: #f8fafc; border-right: none; }
        .form-control { border-radius: 0 12px 12px 0; border-left: none; }
        .logo-img { width: 70px; height: 70px; border-radius: 15px; margin-bottom: 15px; object-fit: cover; }
    </style>
</head>
<body>

<div class="login-card text-center">
    <img src="icon-192.png" alt="Logo GemaSemampir" class="logo-img shadow-sm">
    <h3 class="fw-bold text-dark mb-1">GemaSemampir</h3>
    <p class="text-muted small mb-4">Masuk dengan akun pemberian Admin</p>

    <form action="" method="POST" class="text-start">
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-user text-muted"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold text-dark">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
            </div>
        </div>
        <button type="submit" name="login" class="btn btn-custom w-100 py-3 fs-6 shadow-sm">MASUK SEKARANG</button>
    </form>
    
    <div class="mt-4 small text-muted">
        Belum punya akun? <br> 
        <span class="text-dark fw-bold">Hubungi Admin Kelompok Anda</span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
