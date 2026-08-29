<?php
session_start();
include('../include/connected.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0){

    $stmt = $conn->prepare("
        DELETE FROM transport_pricing
        WHERE id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();
}

header("Location: pricing.php?deleted=1");
exit;
?>