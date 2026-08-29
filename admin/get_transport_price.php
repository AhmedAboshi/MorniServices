<?php
include('../include/connected.php');

header('Content-Type: application/json; charset=utf-8');

$from  = $_GET['from_city'] ?? '';
$to    = $_GET['to_city'] ?? '';
$type  = $_GET['truck_type'] ?? '';

if($from=='' || $to=='' || $type==''){
    echo json_encode([
        "status"=>0
    ]);
    exit;
}

$sql=mysqli_query($con,"
SELECT *
FROM transport_pricing
WHERE from_city='$from'
AND to_city='$to'
LIMIT 1
");

if(mysqli_num_rows($sql)==0){

    echo json_encode([
        "status"=>0
    ]);

    exit;
}

$row=mysqli_fetch_assoc($sql);

switch($type){

case "regular":

$customer=$row['regular_customer'];
$driver=$row['regular_driver'];

break;

case "hydraulic":

$customer=$row['hydraulic_customer'];
$driver=$row['hydraulic_driver'];

break;

case "covered":

$customer=$row['covered_customer'];
$driver=$row['covered_driver'];

break;

default:

$customer=0;
$driver=0;

}

echo json_encode([

"status"=>1,

"customer"=>$customer,

"driver"=>$driver,

"profit"=>$customer-$driver

]);