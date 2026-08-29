<?php

session_start();

require_once 'google-config.php';

/*
|--------------------------------------------------------------------------
| إنشاء رابط تسجيل الدخول بواسطة Google
|--------------------------------------------------------------------------
*/

$authUrl = $google_client->createAuthUrl();

header('Location: ' . $authUrl);
exit;