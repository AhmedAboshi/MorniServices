<?php
session_start();
include('../include/connected.php');

if(!isset($_SESSION['admin_id'])){
    exit("غير مصرح");
}

$admin_id = $_SESSION['admin_id'];

$credential_id = $_POST['credential_id'];

$stmt = $con->prepare("
INSERT INTO webauthn_credentials
(admin_id, credential_id, public_key)
VALUES (?, ?, ?)
");

$empty_key = 'local_key';

$stmt->bind_param(
    "iss",
    $admin_id,
    $credential_id,
    $empty_key
);

if($stmt->execute()){
    echo "تم تفعيل البصمة بنجاح";
}else{
    echo "فشل الحفظ";
}
?>