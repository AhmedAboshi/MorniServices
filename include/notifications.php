<?php

function addNotification(
    $con,
    $user_id,
    $title,
    $message,
    $type,
    $ref_id = null
){

    $stmt = $con->prepare("
        INSERT INTO notifications
        (title,message,type,ref_id,is_read,user_id)
        VALUES (?,?,?,?,0,?)
    ");

    $stmt->bind_param(
        "sssii",
        $title,
        $message,
        $type,
        $ref_id,
        $user_id
    );

    return $stmt->execute();
}