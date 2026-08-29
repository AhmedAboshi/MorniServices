<?php

if (!function_exists('adminImage')) {

    function adminImage($image)
    {
        // صورة المدير
        if (!empty($image)) {

            $path = "../uploads/admin/" . trim($image);

            if (file_exists($path)) {
                return $path;
            }
        }

        // شعار الشركة
        if (function_exists('setting')) {

            $logo = "../uploads/logo/" . setting('company_logo');

            if (file_exists($logo)) {
                return $logo;
            }
        }

        // الصورة الافتراضية
        return "../images/user.png";
    }

}