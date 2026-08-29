<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new Google\Client();

// اسم التطبيق
$client->setApplicationName("My App");

// وضع الصلاحيات (Scopes)
$client->setScopes([
    Google\Service\Drive::DRIVE_READONLY
]);

// ملف JSON الخاص بـ Google Console
$client->setAuthConfig('credentials.json');

// نوع الوصول (مهم جداً)
$client->setAccessType('offline');

echo "Google Client Ready 👍";