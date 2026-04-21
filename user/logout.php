<?php
session_start();
include('../include/connected.php');

if (isset($_SESSION['user_id'])) {

    session_unset();   // حذف المتغيرات
    session_destroy(); // تدمير الجلسة

    header('Location: ../user/login.php');
    exit();
}
?>