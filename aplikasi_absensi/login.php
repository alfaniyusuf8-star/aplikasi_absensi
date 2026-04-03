<?php
session_start();
include 'koneksi.php';

// 1. CEK JIKA SUDAH LOGIN, ARAHKAN KE DASHBOARD YANG BENAR
if (isset($_SESSION['id_user'])) {
    $level = $_SESSION['level'];
    
    if ($level == 'karyawan') {
        header("Location: dashboard.php");
    } else if ($level == 'tim_dhuafa_desa' || $level == 'tim_dhuafa') {
        header("Location: dashboard_dhuafa.php");
    } else {
        header("Location: dashboard_keimaman.php");
    }
    exit;
}

// 2. PROSES LOGIN
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; 

    $query_text = "SELECT u.*, b.nama_lengkap 
                   FROM users u 
                   LEFT JOIN biodata_jamaah b ON u.id_user = b.id_user 
                   WHERE u.username = '$username'";
    
    $query = mysqli_query($conn, $query_text);

    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        if (password_verify($password, $data['password']) || $password == $data['password'] || md5($password) == $data['password']) {
            
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['level']    = $data['level'];
            $_SESSION['kelompok'] = $data['kelompok'];
            $_SESSION['nama'] = !empty($data['nama_lengkap']) ? $data['nama_lengkap'] : $data['username'];

            if ($data['level'] == 'karyawan') {
                header("Location: dashboard.php");
            } else if ($data['level'] == 'tim_dhuafa_desa' || $data['level'] == 'tim_dhuafa') {
                header("Location: dashboard_dhuafa.php");
            } else if ($data['level'] == 'tim_pnkb_desa' || $data['level'] == 'tim_pnkb') {
                header("Location: data_pnkb.php");
            } else {
                header("Location: dashboard_keimaman.php");
            }
            exit;
            
        } else {
            echo "<script>alert('Password yang Anda masukkan salah!');</script>";
        }
    } else {
        echo "<script>alert('Username tidak ditemukan di sistem!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AbsenNgaji Semampir</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a535c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="AbsenNgaji">
    <link rel="apple-touch-icon" href="/icon-192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #1a535c; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: #ffffff; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        .btn-custom { background: #4ecdc4; color: #1a535c; font-weight: bold; border: none; }
        .btn-custom:hover { background: #3dbab1; color: #1a535c; }
    </style>

    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('PWA BERHASIL TERDAFTAR!', reg.scope))
            .catch(err => console.error('PWA GAGAL TERDAFTAR:', err));
        });
      }
    </script>
</head>
<body>

<div class="login-card text-center">
    <img src="icon-192.png" alt="Logo" style="width: 75px; height: 75px; border-radius: 8px; object-fit: cover;" class="me-2 shadow-sm">
    <h3 class="fw-bold text-dark mb-1">Gemasemampir</h3>
    <p class="text-muted small mb-4">Pusat Aktivitas Jamaah Desa Semampir</p>

    <form action="" method="POST" class="text-start">
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa fa-user text-muted"></i></span>
                <input type="text" name="username" class="form-control border-start-0 bg-light" placeholder="Masukkan username" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold text-dark">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control border-start-0 bg-light" placeholder="Masukkan password" required>
            </div>
        </div>
        <button type="submit" name="login" class="btn btn-custom w-100 py-2 fs-5 shadow-sm">MASUK</button>
    </form>
    
    <div class="mt-4 small text-muted">
        Belum punya akun jamaah? <a href="register.php" class="text-primary fw-bold text-decoration-none">Daftar di sini</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
