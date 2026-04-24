<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* تغيير اللغة */
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];

    // يرجع لنفس الصفحة بدون باراميتر
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

/* اللغة الحالية */
$lang = $_SESSION['lang'] ?? 'ar';