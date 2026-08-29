<?php

/* =========================================================
   Google Configuration - Customer Login
   AlSharqPlatform
========================================================= */

require_once __DIR__ . '/../../vendor/autoload.php';


/* =========================================================
   Google Client
========================================================= */

$google_client = new Google\Client();


/* =========================================================
   Credentials
========================================================= */

$credentialsFile = __DIR__ . '/credentials.json';

if (!file_exists($credentialsFile)) {

    die('Google credentials file not found.');

}

$google_client->setAuthConfig($credentialsFile);


/* =========================================================
   Redirect URI
========================================================= */

$google_client->setRedirectUri(
    'http://localhost/AlSharqPlatform/user/auth/google-callback.php'
);


/* =========================================================
   Scopes
========================================================= */

$google_client->addScope('email');
$google_client->addScope('profile');


/* =========================================================
   Access Type
========================================================= */

$google_client->setAccessType('online');


/* =========================================================
   Prompt
========================================================= */

$google_client->setPrompt('select_account');