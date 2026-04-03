<?php
$host = "localhost";
$user = "admin_absen";
$pass = "PasswordRahasia123!";
$db   = "absenngaji"; // Pastikan nama ini sama dengan di phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
if (!function_exists('kirim_notif_semua')) {
    function kirim_notif_semua($conn, $judul, $pesan, $link) {
        $judul_bersih = mysqli_real_escape_string($conn, $judul);
        $pesan_bersih = mysqli_real_escape_string($conn, $pesan);
        $link_bersih  = mysqli_real_escape_string($conn, $link);

        // Ambil semua id_user yang aktif (level karyawan/jamaah)
        $q_users = mysqli_query($conn, "SELECT id_user FROM users");
        
        while($u = mysqli_fetch_assoc($q_users)) {
            $id_penerima = $u['id_user'];
            mysqli_query($conn, "INSERT INTO notifikasi (id_user, judul, pesan, link, is_read) 
                                 VALUES ('$id_penerima', '$judul_bersih', '$pesan_bersih', '$link_bersih', 0)");
        }
    }
}
?>