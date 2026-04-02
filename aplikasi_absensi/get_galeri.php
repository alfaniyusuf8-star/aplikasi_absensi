<?php
include 'koneksi.php';
$id = $_GET['id'];
$q = mysqli_query($conn, "SELECT nama_foto FROM galeri_usaha WHERE id_usaha = '$id'");

if (mysqli_num_rows($q) > 0) {
    while($f = mysqli_fetch_assoc($q)) {
        echo '<img src="uploads/'.$f['nama_foto'].'" class="thumb-detail shadow-sm" alt="Foto Produk">';
    }
} else {
    echo '<img src="https://placehold.co/400x300?text=No+Image" class="thumb-detail">';
}
?>