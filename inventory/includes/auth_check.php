<?php
/** Page guard: redirect to login if no session. */
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$currentUser = $_SESSION['user'];
$isAdmin = ($currentUser['role'] === 'admin');
