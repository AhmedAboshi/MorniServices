<?php

function addAuditLog($con, $user, $action, $details){

    $stmt = $con->prepare("
        INSERT INTO audit_log (user, action, details)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("sss", $user, $action, $details);
    $stmt->execute();
}