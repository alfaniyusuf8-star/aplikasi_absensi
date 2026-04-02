<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// KETUA & KEIMAMAN DILARANG MASUK, TAMBAHAN ADMIN MUDAI DESA
$allowed_levels = ['superadmin', 'admin_desa', 'admin', 'admin_mudai', 'admin_mudai_desa', 'admin_remaja', 'admin_praremaja', 'admin_caberawit'];
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['level'], $allowed_levels)) {
    echo "<script>alert('Akses Ditolak! Hanya Admin/Operator yang bisa membuka pengajian.'); window.location='dashboard_keimaman.php';</script>";
    exit;
}

$level = $_SESSION['level'];
$kelompok_admin = $_SESSION['kelompok'];

// Deteksi hak istimewa (Bisa mengatur gabungan kelompok & beda lokasi)
$is_pusat = in_array($level, ['superadmin', 'admin_desa', 'admin_mudai_desa']);

// Deteksi jenjang spesifik untuk dikunci otomatis
$locked_jenjang = null;
if (in_array($level, ['admin_mudai', 'admin_mudai_desa'])) $locked_jenjang = 'Muda/i';
elseif ($level == 'admin_remaja') $locked_jenjang = 'Remaja';
elseif ($level == 'admin_praremaja') $locked_jenjang = 'Pra Remaja';
elseif ($level == 'admin_caberawit') $locked_jenjang = 'Caberawit';

// =========================================================================
// LOGIKA SIMPAN PENGAJIAN BARU (Pemisahan Lokasi & Target Kelompok)
// =========================================================================
if (isset($_POST['simpan_pengajian'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul_pengajian']);
    $materi = mysqli_real_escape_string($conn, $_POST['materi'] ?? '');
    $link_zoom = mysqli_real_escape_string($conn, $_POST['link_zoom']);
    $target_jenjang = $locked_jenjang ? $locked_jenjang : mysqli_real_escape_string($conn, $_POST['target_jenjang']);
    $tgl_buat = date('Y-m-d H:i:s');
    
    // Kamus Database Koordinat Masjid Pusat
    $db_lokasi = [
        'Semampir' => ['tempat' => 'Masjid Kelompok Semampir', 'lat' => '-7.308634088092277', 'lng' => '112.78273792488537'],
        'Keputih' => ['tempat' => 'Masjid Kelompok Keputih', 'lat' => '-7.308176411908018', 'lng' => '112.78544500002104'],
        'Praja' => ['tempat' => 'Masjid Kelompok Praja', 'lat' => '-7.2958318254978485', 'lng' => '112.80212511725126'],
        'Desa' => ['tempat' => 'Masjid Utama Tingkat Desa', 'lat' => '-7.308634088092277', 'lng' => '112.78273792488537']
    ];

    $kelompok_array = [];
    if ($is_pusat) {
        // Ambil data kelompok yang dicentang
        $kelompok_array = $_POST['target_kelompok'] ?? [];
        $tempat_pilihan = $_POST['tempat_pilihan'] ?? 'Desa';
        
        if(empty($kelompok_array)) {
            echo "<script>alert('Gagal! Anda harus mencentang minimal 1 target kelompok jamaah.'); window.history.back();</script>";
            exit;
        }
    } else {
        // Jika Admin Kelompok biasa, target & lokasi dipaksa ke kelompoknya sendiri
        $kelompok_array = [$kelompok_admin];
        $tempat_pilihan = $kelompok_admin;
    }

    // Deteksi apakah menggunakan Titik GPS Manual (Outdoor/Gedung Sewaan)
    $is_manual = (!empty($_POST['is_manual']) && $_POST['is_manual'] == '1');
    $lat_manual = mysqli_real_escape_string($conn, $_POST['lat_pusat']);
    $lng_manual = mysqli_real_escape_string($conn, $_POST['lng_pusat']);
    $tempat_manual = mysqli_real_escape_string($conn, $_POST['tempat_pengajian']);

    // Tentukan Lokasi Final
    if ($is_manual && $lat_manual != '') {
        $tempat_final = $tempat_manual;
        $lat_final = $lat_manual;
        $lng_final = $lng_manual;
    } else {
        $tempat_final = $db_lokasi[$tempat_pilihan]['tempat'];
        $lat_final = $db_lokasi[$tempat_pilihan]['lat'];
        $lng_final = $db_lokasi[$tempat_pilihan]['lng'];
    }

    // Proses Looping untuk membuat kegiatan majemuk
    $insert_berhasil = true;
    foreach($kelompok_array as $kel) {
        // Perhatikan: Target kelompoknya beda-beda (sesuai centang), tapi TEMPAT-nya SAMA (sesuai Dropdown/Manual)
        $query_insert = "INSERT INTO kegiatan (judul_pengajian, materi, tempat_pengajian, link_zoom, lat_pusat, lng_pusat, target_kelompok, target_jenjang, status_buka, status_izin, tgl_buat, is_selesai) 
                         VALUES ('$judul', '$materi', '$tempat_final', '$link_zoom', '$lat_final', '$lng_final', '$kel', '$target_jenjang', 0, 0, '$tgl_buat', 0)";
        
        if(!mysqli_query($conn, $query_insert)) {
            $insert_berhasil = false;
        }
    }

    if($insert_berhasil) {
        // =========================================================================
        // SUNTIKAN NOTIFIKASI BROADCAST (KE SELURUH JAMAAH)
        // =========================================================================
        kirim_notif_semua($conn, "Pengajian Baru: $judul 🕌", "Jadwal pengajian baru untuk jenjang $target_jenjang telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.", "dashboard.php");
        
        echo "<script>alert('Alhamdulillah! Jadwal berhasil dibuat dan disebar ke kelompok yang dituju.'); window.location='riwayat_pengajian.php';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan database!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Buka Pengajian Baru | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-check-custom { transition: 0.2s; cursor: pointer; }
        .form-check-custom:hover { background: #e9ecef !important; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold text-dark"><i class="fa fa-plus-circle text-primary me-2"></i>Buat Pengajian Baru</h2>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-custom bg-white p-4 shadow-sm border-top border-primary border-4">
                <form action="" method="POST">
                    
                    <div class="mb-3">
                        <label class="fw-bold text-dark mb-1">Judul Pengajian <span class="text-danger">*</span></label>
                        <input type="text" name="judul_pengajian" class="form-control border-primary" placeholder="Contoh: Pengajian Gabungan Muda/i" required>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-dark mb-1">Materi / Bab yang Dikaji <span class="text-muted fw-normal">(Opsional)</span></label>
                        <textarea name="materi" class="form-control border-primary bg-light" rows="3" placeholder="Contoh: Makna Hadits Kitabul Adab bab 1-5..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold text-dark mb-1">Link Zoom <span class="text-muted fw-normal">(Opsional)</span></label>
                        <input type="url" name="link_zoom" class="form-control border-primary" placeholder="Masukkan URL Zoom / Meet jika ada...">
                    </div>

                    <?php if($is_pusat): ?>
                        <div class="mb-4 p-4 bg-light rounded border border-secondary shadow-sm">
                            
                            <div class="mb-4">
                                <label class="fw-bold text-dark mb-2"><i class="fa fa-map-marker-alt text-danger me-2"></i>Lokasi / Tempat Acara <span class="text-danger">*</span></label>
                                <select name="tempat_pilihan" class="form-select border-danger fw-bold shadow-sm" required>
                                    <option value="" disabled selected>-- Pilih Masjid Tempat Pengajian --</option>
                                    <option value="Semampir">Masjid Kelompok Semampir</option>
                                    <option value="Keputih">Masjid Kelompok Keputih</option>
                                    <option value="Praja">Masjid Kelompok Praja</option>
                                    <option value="Desa">Masjid Utama Tingkat Desa</option>
                                </select>
                            </div>

                            <div>
                                <label class="fw-bold text-dark mb-2"><i class="fa fa-users text-primary me-2"></i>Target Kelompok Jamaah <span class="text-danger">*</span><br><small class="text-muted fw-normal">Centang kelompok mana saja yang harus hadir di acara ini.</small></label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check border p-2 rounded bg-white form-check-custom">
                                        <input class="form-check-input ms-1" type="checkbox" name="target_kelompok[]" value="Semampir" id="kel_semampir">
                                        <label class="form-check-label fw-bold ms-2 w-100" for="kel_semampir" style="cursor: pointer;">Kelompok Semampir</label>
                                    </div>
                                    <div class="form-check border p-2 rounded bg-white form-check-custom">
                                        <input class="form-check-input ms-1" type="checkbox" name="target_kelompok[]" value="Keputih" id="kel_keputih">
                                        <label class="form-check-label fw-bold ms-2 w-100" for="kel_keputih" style="cursor: pointer;">Kelompok Keputih</label>
                                    </div>
                                    <div class="form-check border p-2 rounded bg-white form-check-custom">
                                        <input class="form-check-input ms-1" type="checkbox" name="target_kelompok[]" value="Praja" id="kel_praja">
                                        <label class="form-check-label fw-bold ms-2 w-100" for="kel_praja" style="cursor: pointer;">Kelompok Praja</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-1">Lokasi & Target Kelompok (Terkunci)</label>
                            <input type="text" class="form-control bg-secondary text-white fw-bold" value="Khusus Kelompok <?= strtoupper($kelompok_admin); ?>" disabled>
                        </div>
                    <?php endif; ?>

                    <?php if($locked_jenjang): ?>
                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-1">Target Jenjang (Terkunci Sesuai Jabatan)</label>
                            <input type="text" class="form-control bg-secondary text-white fw-bold" value="<?= $locked_jenjang; ?>" disabled>
                            <input type="hidden" name="target_jenjang" value="<?= $locked_jenjang; ?>">
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-1">Pilih Jenjang Pengajian <span class="text-danger">*</span></label>
                            <select name="target_jenjang" class="form-select border-primary fw-bold text-primary shadow-sm" required>
                                <option value="" disabled selected>-- Pilih Jenjang --</option>
                                <option value="Semua">Semua Jenjang Umur (Gabungan)</option>
                                <option value="Umum">Umum (Bapak & Ibu)</option>
                                <option value="Muda/i">Muda/i</option>
                                <option value="Remaja">Remaja</option>
                                <option value="Pra Remaja">Pra Remaja</option>
                                <option value="Caberawit">Caberawit</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4 text-center border-top pt-4 mt-4 bg-light p-3 rounded">
                        <small class="text-muted d-block mb-2 fw-bold">Hanya gunakan tombol di bawah jika lokasi pengajian berada di LUAR MASJID (Misal: Sewa Gedung/Outdoor):</small>
                        <button type="button" class="btn btn-outline-danger btn-sm fw-bold shadow-sm rounded-pill px-4" onclick="dapatkanKoordinat()" id="btnLokasi">
                            <i class="fa fa-map-pin me-1"></i> TIMPA DENGAN TITIK GPS SAYA SAAT INI
                        </button>
                        
                        <input type="hidden" name="is_manual" id="is_manual" value="0">
                        <input type="hidden" name="tempat_pengajian" id="inputTempat">
                        <input type="hidden" name="lat_pusat" id="inputLat">
                        <input type="hidden" name="lng_pusat" id="inputLng">
                        
                        <div id="textManualGPS" class="mt-2 text-danger fw-bold small d-none">
                            <i class="fa fa-exclamation-triangle me-1"></i> Titik koordinat Masjid bawaan akan DIABAIKAN.
                        </div>
                    </div>

                    <button type="submit" name="simpan_pengajian" class="btn btn-primary w-100 fw-bold py-3 fs-5 shadow-sm mt-3">
                        <i class="fa fa-save me-2"></i> BUAT JADWAL PENGAJIAN
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function dapatkanKoordinat() {
    var btn = document.getElementById('btnLokasi');
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Mengunci Lokasi...';
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('inputTempat').value = "Lokasi Outdoor/Luar Ruangan";
                document.getElementById('inputLat').value = position.coords.latitude;
                document.getElementById('inputLng').value = position.coords.longitude;
                document.getElementById('is_manual').value = "1";
                
                btn.innerHTML = '<i class="fa fa-check-circle me-1"></i> TITIK GPS MANUAL TERKUNCI';
                btn.classList.replace('btn-outline-danger', 'btn-success');
                document.getElementById('textManualGPS').classList.remove('d-none');
            }, 
            function(error) { 
                alert("Gagal mengambil lokasi! Pastikan fitur GPS/Location aktif di HP Anda."); 
                btn.innerHTML = '<i class="fa fa-map-pin me-1"></i> TIMPA DENGAN TITIK GPS SAYA SAAT INI';
            },
            { enableHighAccuracy: true }
        );
    } else { 
        alert("Browser Anda tidak mendukung fitur lokasi."); 
    }
}
</script>
</body>
</html>