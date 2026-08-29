<?php

require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$text = $_GET['text'] ?? '';

if (empty($text)) {
    die('QR Text Empty');
}

$qrCode = new QrCode($text);

$writer = new PngWriter();
$result = $writer->write($qrCode);

header('Content-Type: ' . $result->getMimeType());
echo $result->getString();