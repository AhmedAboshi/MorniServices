<?php
session_start();

header('Content-Type: application/json');

include("../include/connected.php");
include("../system/pricing.php");

$from_city = trim($_POST['from_city'] ?? '');
$to_city   = trim($_POST['to_city'] ?? '');
$car_type  = trim($_POST['car_type'] ?? '');

if(
    empty($from_city) ||
    empty($to_city) ||
    empty($car_type)
){
    echo json_encode([
        'status'=>false,
        'message'=>'Missing Data'
    ]);
    exit;
}

$price = getTransportPrice(
    $con,
    $from_city,
    $to_city,
    $car_type
);

echo json_encode($price);
exit;