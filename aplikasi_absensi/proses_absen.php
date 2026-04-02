<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user']) || !isset($_POST['absen_masuk'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id_kegiatan = mysqli_real_escape_string($conn, $_POST['id_kegiatan']);
$user_lat = floatval($_POST['lat']);
$user_long = floatval($_POST['long']);

// 1. AMBIL DATA PENGAJIAN & KOORDINAT PUSATNYA DARI DATABASE
$q_kegiatan = mysqli_query($conn, "SELECT tempat_pengajian, status_buka, tgl_buat, lat_pusat, lng_pusat FROM kegiatan WHERE id_kegiatan = '$id_kegiatan'");
$kegiatan = mysqli_fetch_assoc($q_kegiatan);

if (!$kegiatan || $kegiatan['status_buka'] == 0) {
    echo "<script>alert('Gagal! Sesi absensi pengajian ini sudah ditutup atau tidak ditemukan.'); window.history.back();</script>";
    exit;
}

// 2. CEGAH ABSEN GPS JIKA KOORDINAT 0 ATAU ONLINE
if ($kegiatan['lat_pusat'] == '0' || $kegiatan['lng_pusat'] == '0' || strpos(strtolower($kegiatan['tempat_pengajian']), 'online') !== false) {
    echo "<script>alert('Ini adalah pengajian Online/Zoom! Anda tidak perlu absen GPS. Silakan gunakan menu Izin lalu tunggu pengajian selesai untuk Konfirmasi Hadir.'); window.history.back();</script>";
    exit;
}

$target_lat = floatval($kegiatan['lat_pusat']);
$target_long = floatval($kegiatan['lng_pusat']);

// =========================================================================
// 3. FUNGSI HITUNG JARAK (HAVERSINE FORMULA - DALAM METER)
// =========================================================================
function hitungJarakMeter($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // Radius bumi dalam meter
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    
    return $earth_radius * $c;
}

// Hitung jarak antara jamaah dan titik pusat kegiatan
$jarak = hitungJarakMeter($user_lat, $user_long, $target_lat, $target_long);
$jarak_bulat = round($jarak); // Bulatkan jarak di sini agar bisa dipakai di semua notifikasi
$batas_toleransi = 20; // MAKSIMAL 70 METER DARI TITIK MASJID

// Jika jarak melebihi batas (GAGAL)
if ($jarak > $batas_toleransi) {
    echo "<script>alert('Gagal Absen!\\n\\nPosisi Anda terdeteksi berada $jarak_bulat meter dari lokasi.\\nBatas maksimal adalah $batas_toleransi meter dari pusat kegiatan.\\n\\nSilakan mendekat ke lokasi dan coba lagi.'); window.history.back();</script>";
    exit;
}

// =========================================================================
// 4. PROSES SIMPAN ABSEN JIKA JARAK SESUAI
// =========================================================================
$cek_absen = mysqli_query($conn, "SELECT id_presensi FROM presensi WHERE id_user = '$id_user' AND id_kegiatan = '$id_kegiatan'");

if (mysqli_num_rows($cek_absen) > 0) {
    echo "<script>alert('Anda sudah melakukan absensi sebelumnya!'); window.history.back();</script>";
    exit;
}

// Logika Tepat Waktu vs Terlambat (Batas 10 Menit sejak Absen DIBUKA)
$waktu_sekarang = time();
// Mengambil waktu_buka_absen jika ada, jika tidak pakai tgl_buat
$waktu_db = $kegiatan['waktu_buka_absen'] ?? '';
if(empty($waktu_db) || $waktu_db == '0000-00-00 00:00:00') {
    $waktu_db = $kegiatan['tgl_buat'];
}
$waktu_mulai = strtotime($waktu_db);
$selisih_menit = floor(($waktu_sekarang - $waktu_mulai) / 60);

// Ubah batas menjadi 10 Menit
$status_absen = ($selisih_menit <= 10) ? 'tepat waktu' : 'terlambat';
$tgl_presensi = date('Y-m-d H:i:s');

$simpan = mysqli_query($conn, "INSERT INTO presensi (id_user, id_kegiatan, tgl_presensi, status_absen) VALUES ('$id_user', '$id_kegiatan', '$tgl_presensi', '$status_absen')");

// Jika berhasil tersimpan, tampilkan notifikasi jaraknya (BERHASIL)
if ($simpan) {
    echo "<script>alert('Alhamdulillah! Absensi GPS Berhasil.\\n\\nStatus: " . strtoupper($status_absen) . "\\nJarak Akurasi: $jarak_bulat meter dari titik pusat masjid.'); window.history.back();</script>";
} else {
    echo "<script>alert('Terjadi kesalahan pada database saat menyimpan absen.'); window.history.back();</script>";
}
?>