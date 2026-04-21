<?php
include('../include/connected.php');

// اسم الملف
$fileName = "fleet_report_" . date('Y-m-d') . ".csv";

// إعدادات التحميل
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=$fileName");

// فتح مخرج الكتابة
$output = fopen("php://output", "w");

// إضافة BOM لحل مشكلة اللغة العربية في Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// العناوين (Headers)
fputcsv($output, [
    'ID',
    'Driver',
    'Plate',
    'Type',
    'Classify',
    'Model',
    'Color',
    'Work'
]);

// جلب البيانات
$query = "SELECT * FROM fleet";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_assoc($result)) {

    fputcsv($output, [
        $row['id'],
        $row['driver'],
        $row['plate'],
        $row['typefleet'],
        $row['classify'],
        $row['model'],
        $row['colorfleet'],
        $row['work']
    ]);
}

fclose($output);
exit;
?>