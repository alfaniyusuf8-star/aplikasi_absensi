<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; 

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    
    if (mysqli_num_rows($query) === 1) {
        $data = mysqli_fetch_assoc($query);

        if ($password === $data['password']) {
            // Kita gunakan trim() untuk memastikan tidak ada spasi yang ikut tersimpan
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['nama']     = $data['nama_lengkap'];
            $_SESSION['level']    = trim($data['level']);    
            $_SESSION['kelompok'] = trim($data['kelompok']); 
            
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Password Salah!'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('Username Tidak Ditemukan!'); window.location='login.php';</script>";
    }
}
?>