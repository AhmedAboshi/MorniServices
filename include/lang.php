<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 🌍 تغيير اللغة */
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

/* 📚 تحميل الملفات */
$translations = [
    'ar' => include __DIR__ . '/lang/ar.php',
    'en' => include __DIR__ . '/lang/en.php'
];

/* 🔤 دالة الترجمة */
function __($key){
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}