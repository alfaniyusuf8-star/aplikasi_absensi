<?php
include 'koneksi.php';

if (isset($_POST['signup'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $kelompok = mysqli_real_escape_string($conn, $_POST['kelompok']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // 1. Cek apakah username sudah dipakai orang lain
    $cek_user = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    
    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username sudah terpakai, silakan cari nama lain.'); window.location='signup.php';</script>";
    } else {
        // 2. Simpan ke database (Level otomatis 'karyawan' atau 'jamaah')
        // Pastikan kolom 'kelompok' sudah ada di tabel users Anda
        $query = "INSERT INTO users (username, password, nama_lengkap, level, kelompok) 
                  VALUES ('$username', '$password', '$nama', 'karyawan', '$kelompok')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Pendaftaran Berhasil! Silakan Login.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Gagal mendaftar. Silakan coba lagi.'); window.location='signup.php';</script>";
        }
    }
}
?>