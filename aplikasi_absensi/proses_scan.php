<?php
// MATIKAN SEMUA ERROR PHP AGAR TIDAK MERUSAK FORMAT JSON
error_reporting(0);
ini_set('display_errors', 0);
while (ob_get_level()) { ob_end_clean(); } // Bersihkan spasi kosong yang tidak sengaja terketik
header('Content-Type: application/json');

try {
    session_start();
    require 'koneksi.php'; 
    date_default_timezone_set('Asia/Jakarta');

    // Pastikan request adalah POST dan membawa data qr_code
    if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['qr_code'])) {
        echo json_encode(['status' => 'error', 'pesan' => 'Akses ditolak atau data QR kosong.']);
        exit;
    }

    $qr_code = trim($_POST['qr_code']);
    $parts = explode('-', $qr_code);

    // 1. Validasi Format QR Code (Harus ada 3 bagian: ABSENGAJI - ID - HASH)
    if (count($parts) != 3 || $parts[0] !== 'ABSENGAJI') {
        echo json_encode(['status' => 'error', 'pesan' => 'QR Code Tidak Valid atau Bukan Milik Sistem Ini!']);
        exit;
    }

    $id_user_scan = mysqli_real_escape_string($conn, $parts[1]);

    // 2. Ambil Data Jamaah (Mencari Nama Lengkap, Kelompok, Jenjang)
    $query_user = "SELECT u.username, u.kelompok, b.jenjang, b.nama_lengkap 
                   FROM users u 
                   LEFT JOIN biodata_jamaah b ON u.id_user = b.id_user 
                   WHERE u.id_user = '$id_user_scan' AND u.level = 'karyawan'";
    $cek_user = mysqli_query($conn, $query_user);
    
    if (!$cek_user || mysqli_num_rows($cek_user) == 0) {
        echo json_encode(['status' => 'error', 'pesan' => 'Data Jamaah tidak ditemukan di database!']);
        exit;
    }

    $d_user = mysqli_fetch_assoc($cek_user);
    
    // Prioritaskan Nama Lengkap, jika belum diisi maka pakai Username
    $nama_jamaah = !empty(trim($d_user['nama_lengkap'])) ? strtoupper(trim($d_user['nama_lengkap'])) : strtoupper(trim($d_user['username']));
    $kel_jamaah  = $d_user['kelompok'];
    $jenjang_jamaah = !empty($d_user['jenjang']) ? $d_user['jenjang'] : 'Umum';

    // 3. Cari Jadwal Pengajian yang Sedang Buka (Sesuai Kelompok & Jenjang)
    $query_kegiatan = "SELECT id_kegiatan, judul_pengajian FROM kegiatan
                       WHERE status_buka = 1 AND is_selesai = 0
                       AND (target_kelompok = 'Semua' OR target_kelompok = '$kel_jamaah')
                       AND (target_jenjang = 'Semua' OR target_jenjang = '$jenjang_jamaah')
                       ORDER BY id_kegiatan DESC LIMIT 1";
    $cek_kegiatan = mysqli_query($conn, $query_kegiatan);

    if (!$cek_kegiatan || mysqli_num_rows($cek_kegiatan) == 0) {
        echo json_encode(['status' => 'error', 'pesan' => "TIDAK ADA JADWAL PENGAJIAN aktif untuk $nama_jamaah (Kelompok $kel_jamaah)."]);
        exit;
    }

    $kegiatan = mysqli_fetch_assoc($cek_kegiatan);
    $id_kegiatan = $kegiatan['id_kegiatan'];

    // 4. Cek Apakah Sudah Absen Sebelumnya (Mencegah Double Absen)
    $query_absen = "SELECT id_presensi FROM presensi WHERE id_user = '$id_user_scan' AND id_kegiatan = '$id_kegiatan'";
    $cek_absen = mysqli_query($conn, $query_absen);
    
    if (mysqli_num_rows($cek_absen) > 0) {
        echo json_encode(['status' => 'warning', 'pesan' => "$nama_jamaah SUDAH TERCATAT HADIR sebelumnya!"]);
        exit;
    }

    // 5. Simpan Data Kehadiran
    $tgl_sekarang = date('Y-m-d H:i:s');
    $insert = mysqli_query($conn, "INSERT INTO presensi (id_user, id_kegiatan, tgl_presensi, status_absen) 
                                   VALUES ('$id_user_scan', '$id_kegiatan', '$tgl_sekarang', 'tepat waktu')");

    if ($insert) {
        echo json_encode(['status' => 'success', 'pesan' => "$nama_jamaah berhasil tercatat HADIR."]);
    } else {
        echo json_encode(['status' => 'error', 'pesan' => 'Terjadi kesalahan sistem database saat menyimpan absen.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'pesan' => 'Sistem Crash: ' . $e->getMessage()]);
}
?>