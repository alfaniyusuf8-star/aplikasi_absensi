<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Jamaah Baru</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#1a535c">
<link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/3652/3652191.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .signup-box { background: white; padding: 30px; border-radius: 15px; shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .btn-primary { background: #1a535c; border: none; }
        .btn-primary:hover { background: #144047; }
    </style>
</head>
<body>

<div class="signup-box shadow">
    <h3 class="text-center fw-bold mb-4" style="color: #1a535c;">Daftar Jamaah</h3>
    <form action="proses_signup.php" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">NAMA LENGKAP</label>
            <input type="text" name="nama" class="form-control" placeholder="Nama sesuai KTP" required>
        </div>
        
        <div class="mb-3">
    <label class="form-label small fw-bold">WILAYAH / KELOMPOK</label>
    <select name="kelompok" class="form-select" required>
        <option value="">-- Pilih Wilayah --</option>
        <option value="Semampir">Semampir</option>
        <option value="Keputih">Keputih</option>
        <option value="Praja">Praja</option>
    </select>
</div>

        <div class="mb-3">
            <label class="form-label small fw-bold">USERNAME</label>
            <input type="text" name="username" class="form-control" placeholder="Untuk login nanti" required>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">PASSWORD</label>
            <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
        </div>

        <button type="submit" name="signup" class="btn btn-primary w-100 fw-bold py-2">DAFTAR SEKARANG</button>
    </form>
    
    <div class="text-center mt-4">
        <p class="small text-muted">Sudah punya akun? <a href="login.php" class="text-decoration-none">Login di sini</a></p>
    </div>
</div>

</body>
</html>