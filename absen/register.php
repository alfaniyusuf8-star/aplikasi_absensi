<?php
session_start();
include 'koneksi.php';

// Jika sudah login, langsung lempar ke dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $kelompok = $_POST['kelompok'];
    
    // LEVEL OTOMATIS SEBAGAI KARYAWAN (JAMAAH)
    $level = 'karyawan'; 

    // 1. Cek apakah username sudah dipakai orang lain
    $cek_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($cek_username) > 0) {
        $error = "Username sudah terdaftar! Silakan gunakan username lain.";
    } else {
        // 2. Enkripsi Password biar aman di database
        $password_aman = password_hash($password, PASSWORD_DEFAULT);

        // 3. Simpan ke database
        $query_insert = "INSERT INTO users (username, password, level, kelompok) 
                         VALUES ('$username', '$password_aman', '$level', '$kelompok')";

        if (mysqli_query($conn, $query_insert)) {
            echo "<script>
                    alert('Pendaftaran Jamaah Berhasil! Silakan login.');
                    window.location='login.php';
                  </script>";
        } else {
            $error = "Gagal mendaftar: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Jamaah | AbsenNgaji</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#1a535c">
<link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/3652/3652191.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #1a535c 0%, #4ecdc4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px 0;
        }
        .register-card {
            width: 100%;
            max-width: 400px; /* Dipersempit sedikit agar lebih proporsional */
            padding: 30px;
            border: none;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .btn-primary { background: #1a535c; border: none; padding: 12px; border-radius: 10px; }
        .btn-primary:hover { background: #144148; }
        .form-control, .form-select { padding: 12px; border-radius: 10px; }
    </style>
</head>
<body>

<div class="register-card">
    <div class="text-center mb-4">
        <h3 class="fw-bold" style="color: #1a535c;">Pendaftaran Jamaah</h3>
        <p class="text-muted small">Buat akun untuk masuk ke sistem</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger p-2 small text-center fw-bold"><?= $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Buat username (tanpa spasi)" required>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Buat password" required>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">Pilih Kelompok</label>
            <select name="kelompok" class="form-select" required>
                <option value="" disabled selected>-- Pilih Kelompok Anda --</option>
                <option value="Semampir">Semampir</option>
                <option value="Keputih">Keputih</option>
                <option value="Praja">Praja</option>
            </select>
        </div>

        <button type="submit" name="register" class="btn btn-primary w-100 fw-bold">DAFTAR SEKARANG</button>
    </form>
    
    <div class="text-center mt-4">
        <small class="text-muted">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold" style="color: #4ecdc4;">Masuk di sini</a></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>