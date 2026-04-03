<?php
session_start();
session_unset();
session_destroy();
echo "<h1>Session Berhasil Dihapus!</h1>";
echo "<p>Silakan <a href='login.php'>Login Kembali</a> menggunakan akun admin_semampir.</p>";
?>