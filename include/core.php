<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 🌍 اللغة */
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

/* 📚 تحميل الترجمة */
$translations = [
    'ar' => include __DIR__ . '/lang/ar.php',
    'en' => include __DIR__ . '/lang/en.php'
];

/* 🔤 دالة */
function __($key){
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}

function t($key){
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}

/* =========================================================
   🔔 إضافة إشعار للمستخدم
========================================================= */

function addNotification(
    $con,
    $title,
    $message,
    $type = 'general',
    $user_id = null,
    $ref_id = null
) {

    $stmt = $con->prepare("
        INSERT INTO notifications
        (
            title,
            message,
            type,
            ref_id,
            is_read,
            user_id
        )
        VALUES
        (
            ?, ?, ?, ?, 0, ?
        )
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "sssii",
        $title,
        $message,
        $type,
        $ref_id,
        $user_id
    );

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}