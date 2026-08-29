<?php
include('include/connected.php');

$result = mysqli_query($con, "SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
$row = mysqli_fetch_assoc($result);

echo json_encode([
    "count" => (int)$row['total']
]);
?>