<?php
session_start();

if(isset($_POST['new_level']) && isset($_POST['new_kelompok'])) {
    // Update session dengan peran dan kelompok yang baru
    $_SESSION['level'] = $_POST['new_level'];
    $_SESSION['kelompok'] = $_POST['new_kelompok'];
    
    $lvl = $_SESSION['level'];
    
    // LOGIKA REDIRECT PINTAR
    if($lvl == 'karyawan') {
        header("Location: dashboard.php"); // Ke dashboard jamaah biasa
    } 
    else if($lvl == 'tim_dhuafa' || $lvl == 'tim_dhuafa_desa') {
        header("Location: dashboard_dhuafa.php"); // Ke dashboard dhuafa
    } 
    else if($lvl == 'tim_pnkb' || $lvl == 'tim_pnkb_desa') {
        header("Location: data_pnkb.php"); // LANGSUNG ke halaman Ta'aruf & PNKB
    } 
    else {
        header("Location: dashboard_keimaman.php"); // Sisanya ke dashboard pengurus
    }
    exit;
} else {
    // Jika tidak ada data yang dikirim, kembalikan ke halaman sebelumnya
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>