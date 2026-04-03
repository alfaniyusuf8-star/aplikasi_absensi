<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// =========================================================================
// 1. PROSES TAMBAH USAHA & MULTIPLE FOTO (MAKS 3)
// =========================================================================
if (isset($_POST['tambah_usaha'])) {
    $kategori   = mysqli_real_escape_string($conn, $_POST['kategori']);
    $nama_usaha = mysqli_real_escape_string($conn, $_POST['nama_usaha']);
    $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $no_wa      = mysqli_real_escape_string($conn, $_POST['no_wa']);

    // Simpan data usaha utama
    $insert = mysqli_query($conn, "INSERT INTO usaha_jamaah (id_user, kategori, nama_usaha, deskripsi, no_wa) VALUES ('$id_user', '$kategori', '$nama_usaha', '$deskripsi', '$no_wa')");
    $id_usaha_baru = mysqli_insert_id($conn);

    if ($insert && isset($_FILES['foto_usaha'])) {
        $files = $_FILES['foto_usaha'];
        $jumlah_foto = count($files['name']);
        $limit = ($jumlah_foto > 3) ? 3 : $jumlah_foto; // Batasi maksimal 3 foto

        for ($i = 0; $i < $limit; $i++) {
            if ($files['error'][$i] === 0) {
                $namaFile = $files['name'][$i];
                $tmpName  = $files['tmp_name'][$i];
                $ekstensi = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
                
                // Validasi ekstensi & ukuran (2MB)
                if (in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp']) && $files['size'][$i] <= 2000000) {
                    $namaFileBaru = "produk_" . time() . "_" . uniqid() . "_$i." . $ekstensi;
                    if (move_uploaded_file($tmpName, 'uploads/' . $namaFileBaru)) {
                        mysqli_query($conn, "INSERT INTO galeri_usaha (id_usaha, nama_foto) VALUES ('$id_usaha_baru', '$namaFileBaru')");
                    }
                }
            }
        }
    }
    echo "<script>alert('Usaha berhasil dipromosikan!'); window.location='bursa_usaha.php';</script>";
}

// =========================================================================
// 2. PROSES HAPUS USAHA (SEKALIGUS HAPUS SEMUA FOTO DI FOLDER)
// =========================================================================
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Ambil semua foto terkait usaha ini
    $q_foto = mysqli_query($conn, "SELECT nama_foto FROM galeri_usaha WHERE id_usaha = '$id_hapus'");
    while($f = mysqli_fetch_assoc($q_foto)) {
        if (file_exists('uploads/' . $f['nama_foto'])) {
            unlink('uploads/' . $f['nama_foto']);
        }
    }
    
    mysqli_query($conn, "DELETE FROM galeri_usaha WHERE id_usaha = '$id_hapus'");
    mysqli_query($conn, "DELETE FROM usaha_jamaah WHERE id_usaha = '$id_hapus' AND id_user = '$id_user'");
    echo "<script>alert('Usaha dan foto terkait telah dihapus.'); window.location='bursa_usaha.php';</script>";
}

// =========================================================================
// 3. LOGIKA PENCARIAN & DATA
// =========================================================================
$f_kategori = $_GET['kategori'] ?? '';
$f_keyword  = $_GET['keyword'] ?? '';
$sql_filter = "1=1";
if (!empty($f_kategori)) $sql_filter .= " AND uj.kategori = '$f_kategori'";
if (!empty($f_keyword))  $sql_filter .= " AND (uj.nama_usaha LIKE '%$f_keyword%' OR uj.deskripsi LIKE '%$f_keyword%')";

$query_bursa = mysqli_query($conn, "
    SELECT uj.*, b.nama_lengkap, u.kelompok 
    FROM usaha_jamaah uj 
    JOIN biodata_jamaah b ON uj.id_user = b.id_user 
    JOIN users u ON uj.id_user = u.id_user 
    WHERE $sql_filter 
    ORDER BY uj.id_usaha DESC
");

$q_usaha_saya = mysqli_query($conn, "SELECT * FROM usaha_jamaah WHERE id_user = '$id_user'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bursa Usaha | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        .card-usaha { border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; cursor: pointer; }
        .card-usaha:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .img-produk { width: 100%; height: 180px; object-fit: cover; background: #eee; }
        .header-bursa { background: linear-gradient(135deg, #1a535c, #4ecdc4); color: white; border-radius: 15px; padding: 30px; }
        .thumb-detail { width: 100%; height: auto; border-radius: 10px; margin-bottom: 10px; border: 2px solid #eee; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header-bursa shadow-sm mb-4">
        <h2 class="fw-bold mb-2"> Bursa Usaha & Profesi</h2>
        <p class="mb-3 opacity-75">Klik kartu usaha untuk melihat detail informasi.</p>
        <div class="d-flex gap-2">
            <button class="btn btn-warning fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUsaha"><i class="fa fa-plus-circle me-1"></i> Tambah Usaha</button>
            <button class="btn btn-light fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDaftarSaya"><i class="fa fa-list me-1"></i> Usaha Saya</button>
        </div>
    </div>

    <div class="card card-usaha mb-4 p-3 shadow-none border" style="cursor: default;">
        <form action="" method="GET" class="row g-2">
            <div class="col-md-4">
                <select name="kategori" class="form-select border-info fw-bold">
                    <option value="">-- Semua Kategori --</option>
                    <option value="Jasa / Teknisi" <?= ($f_kategori == 'Jasa / Teknisi') ? 'selected' : ''; ?>> Jasa / Teknisi</option>
                    <option value="Kuliner / Makanan" <?= ($f_kategori == 'Kuliner / Makanan') ? 'selected' : ''; ?>> Kuliner / Makanan</option>
                    <option value="Barang / Produk" <?= ($f_kategori == 'Barang / Produk') ? 'selected' : ''; ?>> Barang / Produk Dagang</option>
                </select>
            </div>
            <div class="col-md-6"><input type="text" name="keyword" class="form-control border-info" placeholder="Cari layanan atau produk..." value="<?= htmlspecialchars($f_keyword); ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-info w-100 fw-bold shadow-sm text-dark"><i class="fa fa-search"></i></button></div>
        </form>
    </div>

    <div class="row g-4">
        <?php if(mysqli_num_rows($query_bursa) == 0): ?>
            <div class="col-12 text-center py-5 text-muted"><h5>Belum ada data.</h5></div>
        <?php else: ?>
            <?php while($row = mysqli_fetch_assoc($query_bursa)): 
                $wa = preg_replace('/[^0-9]/', '', $row['no_wa']);
                if (substr($wa, 0, 1) === '0') { $wa = '62' . substr($wa, 1); }
                $warna = ($row['kategori'] == 'Jasa / Teknisi') ? 'primary' : (($row['kategori'] == 'Kuliner / Makanan') ? 'danger' : 'success');
                
                // Ambil 1 foto cover dari galeri
                $q_cover = mysqli_query($conn, "SELECT nama_foto FROM galeri_usaha WHERE id_usaha = '".$row['id_usaha']."' LIMIT 1");
                $d_cover = mysqli_fetch_assoc($q_cover);
                $foto_tampil = $d_cover ? 'uploads/'.$d_cover['nama_foto'] : 'https://placehold.co/400x300?text=No+Image';
            ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card card-usaha h-100 bg-white" onclick="bukaDetail('<?= $row['id_usaha']; ?>', '<?= htmlspecialchars($row['nama_usaha']); ?>', '<?= $row['kategori']; ?>', '<?= addslashes(nl2br($row['deskripsi'])); ?>', '<?= $row['nama_lengkap']; ?>', '<?= $row['kelompok']; ?>', '<?= $wa; ?>')">
                        <img src="<?= $foto_tampil; ?>" class="img-produk" alt="Foto Usaha">
                        <div class="card-body">
                            <span class="badge bg-<?= $warna; ?> mb-2"><?= $row['kategori']; ?></span>
                            <h5 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($row['nama_usaha']); ?></h5>
                            <small class="text-muted d-block mb-2"><i class="fa fa-user me-1"></i><?= $row['nama_lengkap']; ?></small>
                            <p class="text-muted small text-truncate"><?= strip_tags($row['deskripsi']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-5 bg-dark p-3 d-flex flex-column align-items-center" id="galeriArea" style="max-height: 80vh; overflow-y: auto;">
                        </div>
                    <div class="col-md-7 p-4 bg-white">
                        <button type="button" class="btn-close float-end" data-bs-dismiss="modal"></button>
                        <span id="detKategori" class="badge bg-primary mb-2"></span>
                        <h2 id="detNama" class="fw-bold text-dark mb-1"></h2>
                        <p id="detOwner" class="text-muted small mb-3"></p>
                        <hr>
                        <h6 class="fw-bold"><i class="fa fa-info-circle me-1"></i> Deskripsi Usaha:</h6>
                        <p id="detDeskripsi" class="text-secondary" style="font-size: 0.9rem; line-height: 1.6;"></p>
                        <div class="mt-4">
                            <a id="detWA" href="#" target="_blank" class="btn btn-success w-100 fw-bold rounded-pill py-2 shadow-sm">
                                <i class="fab fa-whatsapp me-2"></i>HUBUNGI VIA WHATSAPP
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahUsaha" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Tambah Usaha / Profesi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kategori</label>
                        <select name="kategori" class="form-select border-primary" required>
                            <option value="Jasa / Teknisi"> Jasa / Teknisi</option>
                            <option value="Kuliner / Makanan"> Kuliner / Makanan / Minuman</option>
                            <option value="Barang / Produk"> Barang / Produk Dagang</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Usaha</label>
                        <input type="text" name="nama_usaha" class="form-control" placeholder="Cth: Warung Barokah,Jasa Pijat" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Upload Foto (Maks 3 Foto)</label>
                        <input type="file" name="foto_usaha[]" class="form-control" accept="image/*" multiple>
                        <small class="text-muted">Pilih hingga 3 foto sekaligus. Ukuran per foto maks 2MB.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">No WhatsApp</label>
                        <input type="number" name="no_wa" class="form-control" placeholder="08..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Deskripsi Singkat</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan layanan/produk Anda..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="tambah_usaha" class="btn btn-primary w-100 fw-bold rounded-pill">SIMPAN PROMOSI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDaftarSaya" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Kelola Usaha Saya</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if(mysqli_num_rows($q_usaha_saya) == 0): ?>
                    <p class="text-center text-muted">Belum ada usaha yang didaftarkan.</p>
                <?php else: ?>
                    <?php mysqli_data_seek($q_usaha_saya, 0); 
                    while($s = mysqli_fetch_assoc($q_usaha_saya)): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= $s['nama_usaha']; ?></h6>
                                <small class="text-muted"><?= $s['kategori']; ?></small>
                            </div>
                            <a href="?hapus=<?= $s['id_usaha']; ?>" class="btn btn-sm btn-danger rounded-pill" onclick="return confirm('Hapus promosi ini beserta semua fotonya?')"><i class="fa fa-trash"></i></a>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function bukaDetail(id, nama, kategori, deskripsi, owner, kelompok, wa) {
    $('#detNama').text(nama);
    $('#detKategori').text(kategori);
    $('#detDeskripsi').html(deskripsi);
    $('#detOwner').html('<i class="fa fa-user me-1"></i> ' + owner + ' (Kel. ' + kelompok + ')');
    $('#detWA').attr('href', 'https://wa.me/' + wa + '?text=Assalamu\'alaikum, saya melihat usaha *' + encodeURIComponent(nama) + '* di Aplikasi AbsenNgaji. Saya tertarik untuk memesan/bertanya.');
    
    // Ambil galeri foto via AJAX
    $.ajax({
        url: 'get_galeri.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            $('#galeriArea').html(response);
            $('#modalDetail').modal('show');
        }
    });
}
</script>
</body>
</html>