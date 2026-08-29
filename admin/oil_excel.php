<?php

session_start();

include(__DIR__ . '/../include/connected.php');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'])) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر
   نفس أسماء الفلاتر الموجودة في oile.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$car_id = (int)($_GET['car_id'] ?? 0);

$driver_id = (int)($_GET['driver_id'] ?? 0);

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   الاستعلام
========================================================= */

$sql = "
    SELECT

        o.id,
        o.car_id,
        o.driver_id,
        o.driver,
        o.oil_type,
        o.change_date,
        o.next_change,
        o.km_change,
        o.current_km,
        o.next_km,
        o.cost,
        o.notes,

        f.plate AS vehicle_plate,

        COALESCE(
            NULLIF(TRIM(d.name), ''),
            NULLIF(TRIM(o.driver), ''),
            '-'
        ) AS driver_name

    FROM oil_changes o

    LEFT JOIN fleet f
        ON o.car_id = f.id

    LEFT JOIN drivers d
        ON o.driver_id = d.id

    WHERE 1 = 1
";

$params = [];
$types = "";

/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $sql .= "
        AND (
            f.plate LIKE ?
            OR d.name LIKE ?
            OR o.driver LIKE ?
            OR o.oil_type LIKE ?
            OR o.notes LIKE ?
            OR CAST(o.car_id AS CHAR) LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "ssssss";
}

/* =========================================================
   فلتر المركبة
========================================================= */

if ($car_id > 0) {

    $sql .= "
        AND o.car_id = ?
    ";

    $params[] = $car_id;

    $types .= "i";
}

/* =========================================================
   فلتر السائق
========================================================= */

if ($driver_id > 0) {

    $sql .= "
        AND o.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $sql .= "
        AND o.change_date >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $sql .= "
        AND o.change_date <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الترتيب
========================================================= */

$sql .= "
    ORDER BY
        o.change_date DESC,
        o.id DESC
";

/* =========================================================
   تنفيذ الاستعلام
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        "SQL Error: " .
        htmlspecialchars($con->error)
    );
}

/* =========================================================
   التحقق من bind_param
========================================================= */

if (count($params) > 0) {

    if (strlen($types) !== count($params)) {

        die(
            "Filter error: parameters count does not match types count."
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

/* =========================================================
   تنفيذ
========================================================= */

if (!$stmt->execute()) {

    die(
        "Execute Error: " .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   Excel
========================================================= */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تغييرات الزيت'
        : 'Oil Changes'
);

/* =========================================================
   اتجاه الصفحة
========================================================= */

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   العنوان
========================================================= */

$sheet->mergeCells('A1:L1');

$sheet->setCellValue(
    'A1',
    $lang === 'ar'
        ? 'تقرير تغييرات الزيت'
        : 'Oil Changes Report'
);

$sheet->getStyle('A1:L1')->applyFromArray([

    'font' => [
        'bold' => true,
        'size' => 16,
        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '198754'
        ]
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]

]);

$sheet->getRowDimension(1)->setRowHeight(30);

/* =========================================================
   رؤوس الأعمدة
========================================================= */

$headers = [

    'A2' => '#',
    'B2' => 'رقم المركبة',
    'C2' => 'رقم اللوحة',
    'D2' => 'السائق',
    'E2' => 'نوع الزيت',
    'F2' => 'تاريخ التغيير',
    'G2' => 'التغيير القادم',
    'H2' => 'عداد التغيير',
    'I2' => 'العداد الحالي',
    'J2' => 'العداد القادم',
    'K2' => 'التكلفة',
    'L2' => 'الملاحظات'

];

foreach ($headers as $cell => $value) {

    $sheet->setCellValue(
        $cell,
        $value
    );
}

/* =========================================================
   تنسيق الرؤوس
========================================================= */

$sheet->getStyle('A2:L2')->applyFromArray([

    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '343A40'
        ]
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],

    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => [
                'rgb' => 'CCCCCC'
            ]
        ]
    ]

]);

/* =========================================================
   البيانات
========================================================= */

$rowNumber = 3;

$counter = 1;

$totalCost = 0;

while ($row = $result->fetch_assoc()) {

    /* رقم */

    $sheet->setCellValue(
        "A{$rowNumber}",
        $counter
    );

    /* رقم المركبة */

    $sheet->setCellValue(
        "B{$rowNumber}",
        $row['car_id'] ?? '-'
    );

    /* اللوحة */

    $sheet->setCellValue(
        "C{$rowNumber}",
        $row['vehicle_plate'] ?? '-'
    );

    /* السائق */

    $sheet->setCellValue(
        "D{$rowNumber}",
        $row['driver_name'] ?? '-'
    );

    /* نوع الزيت */

    $sheet->setCellValue(
        "E{$rowNumber}",
        $row['oil_type'] ?? '-'
    );

    /* تاريخ التغيير */

    $sheet->setCellValue(
        "F{$rowNumber}",
        $row['change_date'] ?? ''
    );

    /* التغيير القادم */

    $sheet->setCellValue(
        "G{$rowNumber}",
        $row['next_change'] ?? ''
    );

    /* عداد التغيير */

    $sheet->setCellValue(
        "H{$rowNumber}",
        (int)($row['km_change'] ?? 0)
    );

    /* العداد الحالي */

    $sheet->setCellValue(
        "I{$rowNumber}",
        (int)($row['current_km'] ?? 0)
    );

    /* العداد القادم */

    $sheet->setCellValue(
        "J{$rowNumber}",
        (int)($row['next_km'] ?? 0)
    );

    /* التكلفة */

    $cost = (float)($row['cost'] ?? 0);

    $sheet->setCellValue(
        "K{$rowNumber}",
        $cost
    );

    /* الملاحظات */

    $sheet->setCellValue(
        "L{$rowNumber}",
        $row['notes'] ?? ''
    );

    $totalCost += $cost;

    $rowNumber++;

    $counter++;
}

/* =========================================================
   لا توجد نتائج
========================================================= */

if ($counter === 1) {

    $sheet->mergeCells('A3:L3');

    $sheet->setCellValue(
        'A3',
        $lang === 'ar'
            ? 'لا توجد نتائج مطابقة للفلاتر المحددة'
            : 'No results found'
    );

    $sheet->getStyle('A3:L3')->applyFromArray([

        'font' => [
            'bold' => true
        ],

        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]

    ]);

    $rowNumber = 4;
}

/* =========================================================
   إجمالي التكلفة
========================================================= */

$sheet->setCellValue(
    "J{$rowNumber}",
    $lang === 'ar'
        ? 'إجمالي التكلفة'
        : 'Total Cost'
);

$sheet->setCellValue(
    "K{$rowNumber}",
    $totalCost
);

$sheet->getStyle(
    "J{$rowNumber}:K{$rowNumber}"
)->applyFromArray([

    'font' => [
        'bold' => true
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => 'E9ECEF'
        ]
    ],

    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => [
                'rgb' => 'CCCCCC'
            ]
        ]
    ]

]);

/* =========================================================
   تنسيق البيانات
========================================================= */

if ($counter > 1) {

    $lastDataRow = $rowNumber - 1;

    $sheet->getStyle(
        "A3:L{$lastDataRow}"
    )->applyFromArray([

        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ],

        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => [
                    'rgb' => 'DDDDDD'
                ]
            ]
        ]

    ]);

    /* العدادات */

    $sheet->getStyle(
        "H3:J{$lastDataRow}"
    )->getNumberFormat()
     ->setFormatCode('#,##0');

    /* التكلفة */

    $sheet->getStyle(
        "K3:K{$lastDataRow}"
    )->getNumberFormat()
     ->setFormatCode('#,##0.00');
}

/* =========================================================
   تنسيق إجمالي التكلفة
========================================================= */

$sheet->getStyle(
    "K{$rowNumber}"
)->getNumberFormat()
 ->setFormatCode('#,##0.00');

/* =========================================================
   عرض الأعمدة
========================================================= */

$widths = [

    'A' => 8,
    'B' => 14,
    'C' => 18,
    'D' => 25,
    'E' => 25,
    'F' => 16,
    'G' => 16,
    'H' => 17,
    'I' => 17,
    'J' => 17,
    'K' => 15,
    'L' => 40

];

foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}

/* =========================================================
   تجميد
========================================================= */

$sheet->freezePane('A3');

/* =========================================================
   فلتر Excel الداخلي
========================================================= */

$sheet->setAutoFilter(
    'A2:L' . max(2, $rowNumber - 1)
);

/* =========================================================
   اسم الملف
========================================================= */

$filename =
    'oil_changes_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';

/* =========================================================
   تنظيف Output
========================================================= */

if (ob_get_length()) {
    ob_end_clean();
}

/* =========================================================
   Headers
========================================================= */

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header(
    'Cache-Control: max-age=0'
);

header(
    'Pragma: public'
);

/* =========================================================
   إنشاء الملف
========================================================= */

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

$stmt->close();

exit;