<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 🌍 تغيير اللغة */
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];

    // يرجع بدون باراميتر
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

/* 🔥 اللغة الحالية */
$langCode = $_SESSION['lang'] ?? 'ar';

/* 📦 تحميل ملف اللغة */
$langFile = __DIR__ . "/../lang/$langCode.php";

$lang = file_exists($langFile)
    ? include($langFile)
    : include(__DIR__ . "/../lang/ar.php");

/* ⚡ دالة الترجمة العالمية */
if (!function_exists('__')) {
    function __($key) {
        global $lang;
        return $lang[$key] ?? $key;
    }
}