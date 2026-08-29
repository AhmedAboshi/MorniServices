<?php

/* ===================================
   Settings Helper
=================================== */

if (!isset($con)) {
    include(__DIR__ . '/connected.php');
}

$settings = [];

$sql = mysqli_query($con, "
SELECT setting_key,setting_value
FROM settings
");

while($row = mysqli_fetch_assoc($sql)){

    $settings[$row['setting_key']] = $row['setting_value'];

}

/*==================================
دالة قراءة الإعدادات
==================================*/

function setting($key,$default=''){

    global $settings;

    return $settings[$key] ?? $default;

}

/*==================================
دالة تحديث الإعدادات
==================================*/

function updateSetting($key,$value){

    global $con;

    $key   = mysqli_real_escape_string($con,$key);

    $value = mysqli_real_escape_string($con,$value);

    mysqli_query($con,"
    INSERT INTO settings(setting_key,setting_value)

    VALUES('$key','$value')

    ON DUPLICATE KEY UPDATE

    setting_value='$value'
    ");

}