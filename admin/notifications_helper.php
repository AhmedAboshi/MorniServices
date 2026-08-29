<?php

function addNotification($con, $title, $message, $type, $user_id, $ref_id = null)
{
    $stmt = $con->prepare("
        INSERT INTO notifications (user_id, ref_id, title, message, type)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("iisss", $user_id, $ref_id, $title, $message, $type);
    return $stmt->execute();
}

?>