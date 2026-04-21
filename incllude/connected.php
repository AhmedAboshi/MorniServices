<?php
$host = "localhost";
$user = "root";
$password = "";
$dbNAME = "morniservices";

$con = @mysqli_connect($host, $user, $password, $dbNAME);

if (!$con) {
    error_log("DB connection failed: " . mysqli_connect_error());
    exit;
}
?>