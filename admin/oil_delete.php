<?php

session_start();

include(__DIR__ . '/../include/connected.php');

/* =========================
   اللغة
========================= */

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'])) {
    $lang = 'ar';
}

/* =========================
   الوضع الليلي
========================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

$theme = $_SESSION['theme'] ?? 0;

/* =========================
   الحصول على ID
========================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('رقم سجل تغيير الزيت غير صحيح');
}

/* =========================
   التأكد من وجود السجل
========================= */

$stmt = $con->prepare("
    SELECT id
    FROM oil_changes
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die('خطأ في قاعدة البيانات: ' . $con->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result->fetch_assoc()) {

    $stmt->close();

    die('سجل تغيير الزيت غير موجود');
}

$stmt->close();

/* =========================
   حذف السجل
========================= */

$stmt = $con->prepare("
    DELETE FROM oil_changes
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die('خطأ في تجهيز عملية الحذف: ' . $con->error);
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    die('فشل حذف سجل تغيير الزيت: ' . $error);
}

$stmt->close();

/* =========================
   الرجوع إلى صفحة الزيوت
========================= */

header(
    "Location: /AlSharqPlatform/admin/oile.php?lang="
    . urlencode($lang)
    . "&theme="
    . urlencode($theme)
);

exit;