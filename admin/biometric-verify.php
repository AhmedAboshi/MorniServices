<?php
session_start();
include('../include/connected.php');

$credential_id = $_POST['credential_id'];

$stmt = $con->prepare("
SELECT admin_id
FROM webauthn_credentials
WHERE credential_id = ?
LIMIT 1
");

$stmt->bind_param("s",$credential_id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows > 0){

    $row = $result->fetch_assoc();

    $_SESSION['admin_id'] = $row['admin_id'];

    echo "success";

}else{

    echo "failed";

}
?>