<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (isset($_POST['kirim_izin'])) {
    $id_user = $_SESSION['id_user'];
    $id_kegiatan = $_POST['id_kegiatan'];
    $jenis_izin = $_POST['jenis_izin']; // Menangkap pilihan Online / Tidak Hadir
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);
    $tgl_pengajuan = date('Y-m-d H:i:s');

    // Cek apakah user sudah pernah izin di kegiatan ini (Mencegah double)
    $cek = mysqli_query($conn, "SELECT * FROM perizinan WHERE id_user='$id_user' AND id_kegiatan='$id_kegiatan'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Anda sudah mengajukan izin untuk jadwal ini!'); window.location='dashboard.php';</script>";
    } else {
        $query = "INSERT INTO perizinan (id_user, id_kegiatan, jenis_izin, alasan, tgl_pengajuan, status_izin, status_konfirmasi) 
                  VALUES ('$id_user', '$id_kegiatan', '$jenis_izin', '$alasan', '$tgl_pengajuan', 'pending', 'Belum')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Pengajuan berhasil dikirim! Menunggu ACC Pengurus.'); window.location='dashboard.php';</script>";
        } else {
            echo "<script>alert('Gagal mengirim database: " . mysqli_error($conn) . "'); window.location='dashboard.php';</script>";
        }
    }
} else {
    header("Location: dashboard.php");
}
?>