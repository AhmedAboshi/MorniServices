<?php

require_once 'google-config.php';

$login_url = $google_client->createAuthUrl();

header('Location: ' . $login_url);

exit;

?>