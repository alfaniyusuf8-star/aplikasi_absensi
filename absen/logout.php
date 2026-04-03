<?php
session_start();

// 1. Kosongkan semua data array session
$_SESSION = [];
session_unset();

// 2. Hapus cookie session di browser (Sapu bersih ingatan HP)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session di server
session_destroy();

// 4. Lempar kembali ke halaman Login
header("Location: login.php");
exit;
?>