<?php
session_start();
include 'koneksi.php';

// 1. Paksa tabel password jadi teks biasa (bukan hash)
mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN password VARCHAR(255)");

// 2. Hapus user admin lama dan buat yang baru dengan password teks biasa
mysqli_query($conn, "DELETE FROM users WHERE username='admin'");
mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, jabatan, level) 
                    VALUES ('admin', 'admin123', 'Administrator', 'IT Support', 'admin')");

// 3. Langsung buatkan Session (Login Otomatis)
$query = mysqli_query($conn, "SELECT * FROM users WHERE username='admin'");
$data = mysqli_fetch_assoc($query);

$_SESSION['id_user'] = $data['id_user'];
$_SESSION['nama']    = $data['nama_lengkap'];
$_SESSION['level']   = $data['level'];

echo "<h3>Sistem Di-reset!</h3>";
echo "Password admin sekarang adalah: <b>admin123</b><br>";
echo "<a href='dashboard.php'>Klik di sini untuk langsung masuk ke Dashboard</a>";
?>