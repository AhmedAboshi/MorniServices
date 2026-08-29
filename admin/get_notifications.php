<?php
include('../include/connected.php');

$res = mysqli_query($con,"
SELECT * FROM notifications
ORDER BY id DESC
LIMIT 10
");

$data = [];

while($row = mysqli_fetch_assoc($res)){
    $data[] = $row;
}

echo json_encode($data);
?>