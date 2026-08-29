<?php

function addNotification($con, $user_id, $title, $message, $type='general', $related_id=null){

    $title = mysqli_real_escape_string($con, $title);
    $message = mysqli_real_escape_string($con, $message);

    mysqli_query($con,"
        INSERT INTO notifications
        (user_id,title,message,type,related_id)
        VALUES
        ('$user_id','$title','$message','$type','$related_id')
    ");
}
?>