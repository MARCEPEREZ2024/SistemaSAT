<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/audit_helper.php';

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $conn = getConnection();
    logLogout($conn, $user_id);
}

session_unset();
$_SESSION = array();

session_destroy();

session_start();
session_regenerate_id(true);

setcookie(session_name(), '', time() - 42000, '/');

$redirectUrl = BASE_URL . 'autenticacion/login.php';

if (headers_sent()) {
    echo "<script>window.location.href='$redirectUrl';</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=$redirectUrl'></noscript>";
} else {
    header("Location: $redirectUrl");
}
exit();