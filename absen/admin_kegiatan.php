<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$kelompok_admin = $_SESSION['kelompok'];

// --- PROSES TAMBAH KEGIATAN ---
if (isset($_POST['tambah_kegiatan'])) {
    $judul_pengajian = mysqli_real_escape_string($conn, $_POST['judul_pengajian']);
    $tempat_pengajian= mysqli_real_escape_string($conn, $_POST['tempat_pengajian']);
    $link_zoom       = mysqli_real_escape_string($conn, $_POST['link_zoom']);
    $lat_pusat       = $_POST['lat_pusat'];
    $lng_pusat       = $_POST['lng_pusat'];
    $target_kelompok = $_POST['target_kelompok'];
    $target_jenjang  = $_POST['target_jenjang']; 
    $tgl_buat        = date('Y-m-d H:i:s');

    $query = "INSERT INTO kegiatan (judul_pengajian, tempat_pengajian, link_zoom, lat_pusat, lng_pusat, target_kelompok, target_jenjang, status_buka, status_izin, tgl_buat, is_selesai) 
              VALUES ('$judul_pengajian', '$tempat_pengajian', '$link_zoom', '$lat_pusat', '$lng_pusat', '$target_kelompok', '$target_jenjang', 0, 0, '$tgl_buat', 0)";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Jadwal Pengajian Berhasil Dibuat!'); window.location='admin_kegiatan.php';</script>";
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($conn) . "');</script>";
    }
}

// --- PROSES BUKA/TUTUP ABSEN & IZIN (DENGAN PENCATATAN WAKTU) ---
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_keg = $_GET['id'];
    $aksi   = $_GET['aksi'];
    $sekarang = date('Y-m-d H:i:s');

    if ($aksi == 'buka_absen') {
        mysqli_query($conn, "UPDATE kegiatan SET status_buka = 1, waktu_buka_absen = IFNULL(waktu_buka_absen, '$sekarang') WHERE id_kegiatan = '$id_keg' AND is_selesai = 0");
    }
    if ($aksi == 'tutup_absen') {
        // Mengakhiri pengajian secara permanen
        mysqli_query($conn, "UPDATE kegiatan SET status_buka = 0, status_izin = 0, is_selesai = 1, waktu_tutup_absen = '$sekarang', waktu_tutup_izin = IFNULL(waktu_tutup_izin, '$sekarang') WHERE id_kegiatan = '$id_keg'");
    }
    if ($aksi == 'buka_izin') {
        mysqli_query($conn, "UPDATE kegiatan SET status_izin = 1, waktu_buka_izin = IFNULL(waktu_buka_izin, '$sekarang') WHERE id_kegiatan = '$id_keg' AND is_selesai = 0");
    }
    if ($aksi == 'tutup_izin') {
        mysqli_query($conn, "UPDATE kegiatan SET status_izin = 0, waktu_tutup_izin = '$sekarang' WHERE id_kegiatan = '$id_keg'");
    }
    
    header("Location: admin_kegiatan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengajian | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-4">

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h2 class="fw-bold text-dark"><i class="fa fa-calendar-alt me-2 text-primary"></i>Kelola Pengajian</h2>
        </div>
        <div>
            <a href="dashboard_keimaman.php" class="btn btn-outline-dark fw-bold me-2"><i class="fa fa-arrow-left me-1"></i> Dashboard</a>
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="fa fa-plus-circle me-1"></i> Buat Jadwal</button>
        </div>
    </div>

    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>NO</th>
                        <th>TANGGAL BUAT</th>
                        <th>JUDUL PENGAJIAN</th>
                        <th>TARGET KELOMPOK</th>
                        <th>TARGET JENJANG</th>
                        <th>KONTROL ABSEN (GPS)</th>
                        <th>KONTROL IZIN (& ONLINE)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $filter = ($kelompok_admin == 'Semua') ? "1" : "target_kelompok IN ('$kelompok_admin', 'Semua')";
                    $q_keg = mysqli_query($conn, "SELECT * FROM kegiatan WHERE $filter ORDER BY id_kegiatan DESC LIMIT 20");
                    
                    if (mysqli_num_rows($q_keg) == 0) echo "<tr><td colspan='7' class='text-muted py-4'>Belum ada jadwal pengajian yang dibuat.</td></tr>";

                    while ($k = mysqli_fetch_assoc($q_keg)):
                    ?>
                    <tr>
                        <td class="fw-bold"><?= $no++; ?></td>
                        <td><?= date('d M Y, H:i', strtotime($k['tgl_buat'])); ?></td>
                        <td class="fw-bold text-start text-primary">
                            <?= $k['judul_pengajian']; ?><br>
                            <small class="text-muted fw-normal"><i class="fa fa-map-marker-alt text-danger me-1"></i><?= $k['tempat_pengajian']; ?></small><br>
                            <?php if(!empty($k['link_zoom'])): ?>
                                <small class="text-info fw-bold"><i class="fa fa-video me-1"></i>Zoom Tersedia</small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-dark"><?= $k['target_kelompok']; ?></span></td>
                        <td><span class="badge bg-info text-dark"><?= $k['target_jenjang']; ?></span></td>
                        
                        <td>
                            <?php if($k['is_selesai'] == 1): ?>
                                <span class="badge bg-secondary p-2"><i class="fa fa-lock me-1"></i> SELESAI & DITUTUP PERMANEN</span>
                            <?php elseif($k['status_buka'] == 0): ?>
                                <a href="?aksi=buka_absen&id=<?= $k['id_kegiatan']; ?>" class="btn btn-success btn-sm fw-bold"><i class="fa fa-play me-1"></i> BUKA ABSEN</a>
                            <?php else: ?>
                                <a href="?aksi=tutup_absen&id=<?= $k['id_kegiatan']; ?>" class="btn btn-danger btn-sm fw-bold shadow-sm" onclick="return confirm('PERINGATAN!\n\nApakah Anda yakin ingin MENGAKHIRI PENGAJIAN ini?\nAbsen yang sudah ditutup tidak akan bisa dibuka kembali untuk selamanya.')">
                                    <i class="fa fa-stop me-1"></i> AKHIRI PENGAJIAN
                                </a>
                                <div class="small text-success fw-bold mt-1 heartbeat">Sedang Berlangsung...</div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if($k['is_selesai'] == 1): ?>
                                <span class="badge bg-light text-muted border">-</span>
                            <?php elseif($k['status_izin'] == 0): ?>
                                <a href="?aksi=buka_izin&id=<?= $k['id_kegiatan']; ?>" class="btn btn-outline-warning btn-sm fw-bold"><i class="fa fa-envelope-open me-1"></i> BUKA IZIN</a>
                            <?php else: ?>
                                <a href="?aksi=tutup_izin&id=<?= $k['id_kegiatan']; ?>" class="btn btn-warning btn-sm fw-bold"><i class="fa fa-times me-1"></i> TUTUP IZIN</a>
                                <div class="small text-warning fw-bold mt-1">Terbuka...</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="" method="POST">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fa fa-plus-circle me-2"></i>Buat Jadwal Pengajian</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Target Kelompok</label>
                            <select name="target_kelompok" id="target_kelompok" class="form-select border-primary border-2" onchange="setLokasiOtomatis()" required>
                                <?php if($kelompok_admin == 'Semua'): ?>
                                    <option value="Semua">Semua Desa (Gabungan)</option>
                                    <option value="Semampir">Kelompok Semampir</option>
                                    <option value="Keputih">Kelompok Keputih</option>
                                    <option value="Praja">Kelompok Praja</option>
                                <?php else: ?>
                                    <option value="<?= $kelompok_admin; ?>">Kelompok <?= $kelompok_admin; ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Target Jenjang</label>
                            <select name="target_jenjang" class="form-select border-primary border-2" required>
                                <option value="Semua">Semua Jenjang</option>
                                <option value="Umum">Umum (Bapak/Ibu)</option>
                                <option value="Muda/i">Muda/i</option>
                                <option value="Remaja">Remaja</option>
                                <option value="Pra Remaja">Pra Remaja</option>
                                <option value="Caberawit">Caberawit</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Judul Pengajian</label>
                        <input type="text" name="judul_pengajian" class="form-control" placeholder="Contoh: Pengajian Rutin" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-success">Lokasi (Konstan & Terkunci)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white border-success"><i class="fa fa-map-marker-alt"></i></span>
                            <input type="text" name="tempat_pengajian" id="inputTempat" class="form-control fw-bold text-success border-success" readonly required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-info"><i class="fa fa-video me-1"></i>Link Zoom (Opsional)</label>
                        <input type="url" name="link_zoom" class="form-control border-info" placeholder="https://zoom.us/j/xxxxx (Kosongkan jika tidak ada online)">
                    </div>
                    
                    <input type="hidden" name="lat_pusat" id="inputLat" required>
                    <input type="hidden" name="lng_pusat" id="inputLng" required>

                    <div class="mb-1 mt-4 text-center border-top pt-3">
                        <small class="text-muted d-block mb-2">Jika lokasi ngaji berada di luar masjid (Outdoor):</small>
                        <button type="button" class="btn btn-outline-danger btn-sm fw-bold shadow-sm rounded-pill px-4" onclick="dapatkanKoordinat()" id="btnLokasi">
                            <i class="fa fa-map-pin me-1"></i> AMBIL TITIK KOORDINAT SAAT INI (GPS)
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kegiatan" class="btn btn-primary fw-bold px-4">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const databaseLokasi = {
    'Semampir': { tempat: 'Masjid Kelompok Semampir', lat: '-7.308543354342562', lng: '112.78272324131338' },
    'Keputih': { tempat: 'Masjid Kelompok Keputih', lat: '-7.294400', lng: '112.800300' },
    'Praja': { tempat: 'Masjid Kelompok Praja', lat: '-7.310500', lng: '112.760100' },
    'Semua': { tempat: 'Masjid Utama Tingkat Desa', lat: '-7.292671', lng: '112.777931' }
};

function setLokasiOtomatis() {
    var kelompokPilihan = document.getElementById('target_kelompok').value;
    var data = databaseLokasi[kelompokPilihan];
    if(data) {
        document.getElementById('inputTempat').value = data.tempat;
        document.getElementById('inputLat').value = data.lat;
        document.getElementById('inputLng').value = data.lng;
    }
}
$(document).ready(function() { setLokasiOtomatis(); });

function dapatkanKoordinat() {
    var btn = document.getElementById('btnLokasi');
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Mencari Lokasi GPS...';
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('inputTempat').value = "Lokasi Outdoor/Luar Ruangan";
                document.getElementById('inputLat').value = position.coords.latitude;
                document.getElementById('inputLng').value = position.coords.longitude;
                btn.innerHTML = '<i class="fa fa-check-circle me-1"></i> TITIK GPS TERKUNCI';
                btn.classList.replace('btn-outline-danger', 'btn-success');
            }, 
            function(error) { alert("Gagal mengambil lokasi! Pastikan fitur GPS aktif."); },
            { enableHighAccuracy: true }
        );
    } else { alert("Browser Anda tidak mendukung fitur lokasi otomatis."); }
}
</script>
<style>
.heartbeat { animation: heartbeat 1.5s ease-in-out infinite both; }
@keyframes heartbeat {
  from { transform: scale(1); }
  10% { transform: scale(0.95); }
  17% { transform: scale(1.05); }
  33% { transform: scale(0.95); }
  45% { transform: scale(1); }
}
</style>
</body>
</html>