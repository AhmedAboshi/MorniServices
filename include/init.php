<?php
session_start();

/* 🔥 إصلاح تغيير اللغة */
if(isset($_GET['lang'])){

    $_SESSION['lang'] = $_GET['lang'];

    // إعادة تحميل الصفحة بدون باراميتر
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

/* 🌍 اللغة الحالية */
$langCode = $_SESSION['lang'] ?? 'ar';

/* 📦 تحميل ملف اللغة */
$langFile = __DIR__ . "/../lang/$langCode.php";

if(!file_exists($langFile)){
    $langFile = __DIR__ . "/../lang/ar.php";
}

$lang = include($langFile);

/* ⚡ دالة الترجمة */
function __($key){
    global $lang;
    return $lang[$key] ?? $key;
}