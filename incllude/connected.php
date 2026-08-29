<?php
$host = "localhost";
$user = "root";
$password = "";
$dbNAME = "alsharq_platform";

$con = @mysqli_connect($host, $user, $password, $dbNAME);

if (!$con) {
    error_log("DB connection failed: " . mysqli_connect_error());
    exit;
}
?>