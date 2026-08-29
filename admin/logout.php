<?php
session_start();
include('../include/connected.php');
include('../include/audit.php');

$user = $_SESSION['admin_name'] ?? 'Admin';

/* تسجيل خروج */
if (isset($_SESSION['admin_id'])) {

    addAuditLog(
        $con,
        $user,
        "تسجيل خروج",
        "تم تسجيل الخروج من النظام"
    );

    $stmt = $con->prepare("
        UPDATE admin 
        SET status = 'Inactive'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
}

/* حذف الجلسة */
session_unset();
session_destroy();

/* منع الكاش */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/* تحويل مباشر */
header("Location: welcome.php");
exit();
?>