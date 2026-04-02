<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$level = $_SESSION['level'];

// Tangkap kata kunci pencarian
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : '';

// Query Pencarian (Hanya menampilkan level karyawan/jamaah biasa)
$where_sql = "u.level = 'karyawan'";
if ($keyword != '') {
    $where_sql .= " AND (b.nama_lengkap LIKE '%$keyword%' OR u.username LIKE '%$keyword%')";
}

// Tambahkan u.kelompok pada query
$query = "SELECT u.username, u.kelompok, b.* FROM users u 
          JOIN biodata_jamaah b ON u.id_user = b.id_user 
          WHERE $where_sql 
          ORDER BY b.nama_lengkap ASC LIMIT 50";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Jamaah | AbsenNgaji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 250px; padding: 30px; }
        /* Style Kartu di Grid */
        .card-profile { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; background: #fff; overflow: hidden; text-align: center; cursor: pointer; }
        .card-profile:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); border-color: #4ecdc4 !important; }
        .grid-profile-img { width: 90px; height: 90px; object-fit: cover; border-radius: 50%; border: 3px solid #4ecdc4; margin: 15px auto 10px; padding: 2px; }
        
        .search-box { background: white; border-radius: 50px; padding: 5px 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .search-box input { border: none; outline: none; box-shadow: none; padding-left: 15px; }

        /* ======================================================== */
        /* STYLE KHUSUS FOTO KOTAK BESAR DI POPUP (MODAL)         */
        /* ======================================================== */
        .modal-body-img {
            width: 100%;
            max-width: 320px; /* Batas lebar maksimal di desktop */
            height: 320px;    /* Samakan dengan width agar kotak presisi */
            object-fit: cover; /* Foto tetap proporsional di dalam kotak */
            border-radius: 12px; /* Slight rounding agar estetik, tetap kotak */
            border: 4px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: block;
            margin: 0 auto 20px; /* Centered, margin bottom */
        }

        @media (max-width: 768px) { 
            .main-content { margin-left: 0; padding: 15px; } 
            .modal-body-img { max-width: 250px; height: 250px; } /* Sedikit lebih kecil di HP */
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 flex-wrap gap-2">
        <h2 class="fw-bold text-dark mb-0"><i class="fa fa-search text-primary me-2"></i>Direktori Jamaah</h2>
        <?php if($level != 'karyawan'): ?>
            <a href="dashboard_keimaman.php" class="btn btn-outline-dark fw-bold shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-outline-dark fw-bold shadow-sm"><i class="fa fa-arrow-left me-1"></i> Kembali</a>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-8 col-lg-6">
            <form action="" method="GET">
                <div class="search-box d-flex align-items-center border border-primary border-2 shadow">
                    <i class="fa fa-search text-primary ms-3"></i>
                    <input type="text" name="keyword" class="form-control form-control-lg" placeholder="Ketik nama jamaah yang dicari..." value="<?= htmlspecialchars($keyword); ?>">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4 ms-2">CARI</button>
                </div>
            </form>
            <?php if($keyword != ''): ?>
                <div class="text-center mt-3">
                    <small class="text-muted fw-bold">Menampilkan hasil pencarian untuk: <span class="text-primary">"<?= htmlspecialchars($keyword); ?>"</span></small>
                    <br><a href="cari_jamaah.php" class="text-danger small text-decoration-none"><i class="fa fa-times me-1"></i> Reset Pencarian</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <?php 
        if (mysqli_num_rows($result) > 0): 
            while ($row = mysqli_fetch_assoc($result)): 
                $nama_tampil = !empty($row['nama_lengkap']) ? $row['nama_lengkap'] : $row['username'];
                $jenjang_tampil = !empty($row['jenjang']) ? $row['jenjang'] : '-';
                
                if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) {
                    $url_foto = 'uploads/' . $row['foto'];
                } else {
                    $url_foto = 'https://ui-avatars.com/api/?name=' . urlencode($nama_tampil) . '&background=random&color=fff&size=200&bold=true';
                }

                $nama_b64 = base64_encode($nama_tampil);
                $kelompok_b64 = base64_encode($row['kelompok']);
                $jenjang_b64 = base64_encode($jenjang_tampil);
        ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card-profile h-100 border border-light" onclick="lihatDetail('<?= $url_foto; ?>', '<?= $nama_b64; ?>', '<?= $kelompok_b64; ?>', '<?= $jenjang_b64; ?>')">
                    <img src="<?= $url_foto; ?>" alt="Foto Profil" class="grid-profile-img shadow-sm">
                    <div class="p-3 pt-2">
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem; line-height: 1.3; height: 2.6em; overflow: hidden;"><?= strtoupper($nama_tampil); ?></h6>
                        <small class="text-muted" style="font-size: 0.65rem;"><i class="fa fa-hand-pointer me-1"></i>Ketuk</small>
                    </div>
                </div>
            </div>
        <?php 
            endwhile; 
        else: 
        ?>
            <div class="col-12 text-center py-5">
                <i class="fa fa-user-slash fa-4x text-muted mb-3 opacity-25"></i>
                <h4 class="fw-bold text-secondary">Jamaah Tidak Ditemukan</h4>
                <p class="text-muted">Coba gunakan kata kunci atau ejaan nama yang lain.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="bg-primary text-white text-center p-3">
                <h5 class="fw-bold mb-0"><i class="fa fa-id-card me-2 text-warning"></i>Profil Lengkap Jamaah</h5>
            </div>
            <div class="modal-body text-center p-4 bg-light">
                
                <img id="detailFoto" src="" alt="Foto Profil" class="modal-body-img shadow">
                
                <h4 id="detailNama" class="fw-bold text-dark mb-4"></h4>
                
                <div class="row g-2 text-start">
                    <div class="col-6">
                        <div class="bg-white p-2 rounded border border-primary shadow-sm h-100 text-center">
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem;">KELOMPOK</small>
                            <span id="detailKelompok" class="fw-bold text-primary" style="font-size: 0.9rem;"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white p-2 rounded border border-success shadow-sm h-100 text-center">
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem;">JENJANG</small>
                            <span id="detailJenjang" class="fw-bold text-success" style="font-size: 0.9rem;"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light p-3 pt-0">
                <button type="button" class="btn btn-secondary w-100 fw-bold rounded-pill shadow-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Fungsi untuk merender isi Popup berdasarkan kartu yang diklik
function lihatDetail(foto, namaB64, kelompokB64, jenjangB64) {
    let nama = atob(namaB64).toUpperCase();
    let kelompok = atob(kelompokB64).toUpperCase();
    let jenjang = atob(jenjangB64);

    // Masukkan data ke dalam Modal
    document.getElementById('detailFoto').src = foto;
    document.getElementById('detailNama').innerText = nama;
    document.getElementById('detailKelompok').innerText = kelompok;
    document.getElementById('detailJenjang').innerText = jenjang;
    
    // Tampilkan Modal
    var myModal = new bootstrap.Modal(document.getElementById('modalDetail'));
    myModal.show();
}
</script>
</body>
</html>