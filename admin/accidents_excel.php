<?php

session_start();

include('../include/connected.php');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر
   نفس accidents.php بالضبط
========================================================= */

$search = trim($_GET['search'] ?? '');

$vehicle_id = (int)($_GET['vehicle_id'] ?? 0);

$driver_id = (int)($_GET['driver_id'] ?? 0);

$status = trim($_GET['status'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');


/* =========================================================
   بناء WHERE
========================================================= */

$where = " WHERE 1 = 1 ";

$params = [];

$types = "";


/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR f.model LIKE ?
            OR d.name LIKE ?
            OR a.location LIKE ?
            OR a.description LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sssss";
}


/* =========================================================
   فلتر المركبة
========================================================= */

if ($vehicle_id > 0) {

    $where .= "
        AND a.vehicle_id = ?
    ";

    $params[] = $vehicle_id;

    $types .= "i";
}


/* =========================================================
   فلتر السائق
========================================================= */

if ($driver_id > 0) {

    $where .= "
        AND a.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}


/* =========================================================
   فلتر الحالة
========================================================= */

if (
    $status !== '' &&
    in_array(
        $status,
        ['Open', 'In Progress', 'Closed'],
        true
    )
) {

    $where .= "
        AND a.status = ?
    ";

    $params[] = $status;

    $types .= "s";
}


/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND DATE(a.accident_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}


/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND DATE(a.accident_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}


/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT
    a.id,
    a.vehicle_id,
    a.driver_id,
    a.accident_date,
    a.location,
    a.description,
    a.damage_cost,
    a.status,
    f.plate,
    f.model,
    f.imgfleet,
    d.name AS driver_name

    FROM accidents a

    LEFT JOIN fleet f
        ON a.vehicle_id = f.id

    LEFT JOIN drivers d
        ON a.driver_id = d.id

    $where

    ORDER BY
        a.accident_date DESC,
        a.id DESC
";


/* =========================================================
   تجهيز الاستعلام
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        "SQL Error: " .
        htmlspecialchars($con->error)
    );
}


/* =========================================================
   bind_param
========================================================= */

if (!empty($params)) {

    if (strlen($types) !== count($params)) {

        die(
            "Filter parameters mismatch."
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
   إنشاء ملف Excel
========================================================= */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();


/* =========================================================
   اتجاه الورقة
========================================================= */

$sheet->setRightToLeft(
    $lang === 'ar'
);


/* =========================================================
   عنوان الورقة
========================================================= */

$sheet->setTitle(
    $lang === 'ar'
        ? 'الحوادث'
        : 'Accidents'
);


/* =========================================================
   العنوان الرئيسي
========================================================= */

$sheet->mergeCells('A1:K1');

$sheet->setCellValue(
    'A1',
    $lang === 'ar'
        ? 'تقرير حوادث المركبات'
        : 'Vehicle Accidents Report'
);


/* =========================================================
   تنسيق العنوان
========================================================= */

$sheet->getStyle('A1:J1')->applyFromArray([

    'font' => [

        'bold' => true,

        'size' => 16,

        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],

    'fill' => [

        'fillType' =>
            Fill::FILL_SOLID,

        'startColor' => [
            'rgb' => 'DC3545'
        ]
    ],

    'alignment' => [

        'horizontal' =>
            Alignment::HORIZONTAL_CENTER,

        'vertical' =>
            Alignment::VERTICAL_CENTER
    ]

]);

$sheet
    ->getRowDimension(1)
    ->setRowHeight(30);


/* =========================================================
   معلومات الفلاتر المستخدمة
========================================================= */

$filterText = [];


/* البحث */

if ($search !== '') {

    $filterText[] =
        ($lang === 'ar'
            ? 'بحث: '
            : 'Search: ')
        . $search;
}


/* المركبة */

if ($vehicle_id > 0) {

    $vehicleStmt = $con->prepare("
        SELECT plate
        FROM fleet
        WHERE id = ?
        LIMIT 1
    ");

    if ($vehicleStmt) {

        $vehicleStmt->bind_param(
            "i",
            $vehicle_id
        );

        $vehicleStmt->execute();

        $vehicleRow =
            $vehicleStmt
                ->get_result()
                ->fetch_assoc();

        if ($vehicleRow) {

            $filterText[] =
                ($lang === 'ar'
                    ? 'المركبة: '
                    : 'Vehicle: ')
                . $vehicleRow['plate'];
        }

        $vehicleStmt->close();
    }
}


/* السائق */

if ($driver_id > 0) {

    $driverStmt = $con->prepare("
        SELECT name
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    if ($driverStmt) {

        $driverStmt->bind_param(
            "i",
            $driver_id
        );

        $driverStmt->execute();

        $driverRow =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        if ($driverRow) {

            $filterText[] =
                ($lang === 'ar'
                    ? 'السائق: '
                    : 'Driver: ')
                . $driverRow['name'];
        }

        $driverStmt->close();
    }
}


/* الحالة */

if ($status !== '') {

    $statusLabel = $status;

    if ($lang === 'ar') {

        if ($status === 'Open') {
            $statusLabel = 'مفتوح';
        }

        elseif ($status === 'In Progress') {
            $statusLabel = 'قيد المعالجة';
        }

        elseif ($status === 'Closed') {
            $statusLabel = 'مغلق';
        }
    }

    $filterText[] =
        ($lang === 'ar'
            ? 'الحالة: '
            : 'Status: ')
        . $statusLabel;
}


/* من */

if ($from !== '') {

    $filterText[] =
        ($lang === 'ar'
            ? 'من: '
            : 'From: ')
        . $from;
}


/* إلى */

if ($to !== '') {

    $filterText[] =
        ($lang === 'ar'
            ? 'إلى: '
            : 'To: ')
        . $to;
}


/* =========================================================
   عرض الفلاتر
========================================================= */

$sheet->mergeCells('A2:K2');

$sheet->setCellValue(
    'A2',
    !empty($filterText)
        ? implode(' | ', $filterText)
        : (
            $lang === 'ar'
                ? 'جميع السجلات'
                : 'All Records'
        )
);

$sheet->getStyle('A2:J2')->applyFromArray([

    'font' => [
        'italic' => true,
        'size' => 10,
        'color' => [
            'rgb' => '666666'
        ]
    ],

    'alignment' => [
        'horizontal' =>
            Alignment::HORIZONTAL_CENTER,

        'vertical' =>
            Alignment::VERTICAL_CENTER
    ]

]);


/* =========================================================
   رؤوس الأعمدة
========================================================= */

$headers = [

    'A3' => '#',

    'B3' => $lang === 'ar' ? 'صورة المركبة' : 'Vehicle Image',

    'C3' => $lang === 'ar' ? 'المركبة' : 'Vehicle',

    'D3' => $lang === 'ar' ? 'رقم اللوحة' : 'Plate',

    'E3' => $lang === 'ar' ? 'السائق' : 'Driver',

    'F3' => $lang === 'ar' ? 'تاريخ الحادث' : 'Accident Date',

    'G3' => $lang === 'ar' ? 'الموقع' : 'Location',

    'H3' => $lang === 'ar' ? 'التكلفة' : 'Damage Cost',

    'I3' => $lang === 'ar' ? 'الحالة' : 'Status',

    'J3' => $lang === 'ar' ? 'الوصف' : 'Description',

    'K3' => $lang === 'ar' ? 'رقم الحادث' : 'Accident ID'

];

foreach ($headers as $cell => $value) {

    $sheet->setCellValue(
        $cell,
        $value
    );
}


/* =========================================================
   تنسيق رؤوس الأعمدة
========================================================= */

$sheet->getStyle('A3:J3')->applyFromArray([

    'font' => [

        'bold' => true,

        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],

    'fill' => [

        'fillType' =>
            Fill::FILL_SOLID,

        'startColor' => [
            'rgb' => '343A40'
        ]
    ],

    'alignment' => [

        'horizontal' =>
            Alignment::HORIZONTAL_CENTER,

        'vertical' =>
            Alignment::VERTICAL_CENTER
    ],

    'borders' => [

        'allBorders' => [

            'borderStyle' =>
                Border::BORDER_THIN,

            'color' => [
                'rgb' => 'CCCCCC'
            ]
        ]
    ]

]);


/* =========================================================
   البيانات
========================================================= */

$rowNumber = 4;

$counter = 1;

$totalCost = 0;

while ($row = $result->fetch_assoc()) {

    /* رقم */

    $sheet->setCellValue(
    "A{$rowNumber}",
    $counter
);

/* صورة المركبة */

$imageFile = '';

if (!empty($row['imgfleet'])) {

    $imageFile = __DIR__
        . '/../fleetimg/img//'
        . $row['imgfleet'];

    if (!file_exists($imageFile)) {
        $imageFile = '';
    }
}

/*
 * جعل صف Excel أكبر حتى تظهر الصورة
 */
$sheet
    ->getRowDimension($rowNumber)
    ->setRowHeight(65);

/*
 * إضافة الصورة فعليًا داخل خلية B
 */
if ($imageFile !== '') {

    $drawing = new Drawing();

    $drawing->setName('Vehicle');

    $drawing->setDescription(
        'Vehicle Image'
    );

    $drawing->setPath($imageFile);

    $drawing->setHeight(55);

    $drawing->setCoordinates(
        "B{$rowNumber}"
    );

    $drawing->setOffsetX(5);

    $drawing->setOffsetY(5);

    $drawing->setWorksheet($sheet);
}

/* المركبة */

$sheet->setCellValue(
    "C{$rowNumber}",
    $row['model'] ?? '-'
);

/* اللوحة */

$sheet->setCellValue(
    "D{$rowNumber}",
    $row['plate'] ?? '-'
);

/* السائق */

$sheet->setCellValue(
    "E{$rowNumber}",
    $row['driver_name'] ?? '-'
);

/* التاريخ */

$sheet->setCellValue(
    "F{$rowNumber}",
    $row['accident_date'] ?? ''
);

/* الموقع */

$sheet->setCellValue(
    "G{$rowNumber}",
    $row['location'] ?? '-'
);

/* التكلفة */

$cost = (float)($row['damage_cost'] ?? 0);

$sheet->setCellValue(
    "H{$rowNumber}",
    $cost
);

/* الحالة */

$statusText = $row['status'] ?? '-';

if ($lang === 'ar') {

    if ($statusText === 'Open') {
        $statusText = 'مفتوح';
    } elseif ($statusText === 'In Progress') {
        $statusText = 'قيد المعالجة';
    } elseif ($statusText === 'Closed') {
        $statusText = 'مغلق';
    }
}

$sheet->setCellValue(
    "I{$rowNumber}",
    $statusText
);

/* الوصف */

$sheet->setCellValue(
    "J{$rowNumber}",
    $row['description'] ?? ''
);

/* رقم الحادث */

$sheet->setCellValue(
    "K{$rowNumber}",
    (int)$row['id']
);


    $totalCost += $cost;

    $rowNumber++;

    $counter++;
}


/* =========================================================
   حالة عدم وجود بيانات
========================================================= */

if ($counter === 1) {

    $sheet->mergeCells(
        'A4:J4'
    );

    $sheet->setCellValue(
        'A4',
        $lang === 'ar'
            ? 'لا توجد نتائج مطابقة للفلاتر المحددة'
            : 'No results match the selected filters'
    );

    $sheet->getStyle('A4:J4')->applyFromArray([

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER
        ],

        'font' => [

            'bold' => true,

            'color' => [
                'rgb' => '777777'
            ]
        ]
    ]);

    $rowNumber = 5;
}


/* =========================================================
   إجمالي التكلفة
========================================================= */

$sheet->setCellValue(
    "F{$rowNumber}",
    $lang === 'ar'
        ? 'إجمالي التكلفة'
        : 'Total Cost'
);

$sheet->setCellValue(
    "G{$rowNumber}",
    $totalCost
);

$sheet->getStyle(
    "F{$rowNumber}:G{$rowNumber}"
)->applyFromArray([

    'font' => [

        'bold' => true
    ],

    'fill' => [

        'fillType' =>
            Fill::FILL_SOLID,

        'startColor' => [

            'rgb' => 'E9ECEF'
        ]
    ],

    'borders' => [

        'allBorders' => [

            'borderStyle' =>
                Border::BORDER_THIN,

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

    $lastDataRow =
        $rowNumber - 1;

    $sheet->getStyle(
        "A4:J{$lastDataRow}"
    )->applyFromArray([

        'alignment' => [

            'vertical' =>
                Alignment::VERTICAL_CENTER,

            'wrapText' => true
        ],

        'borders' => [

            'allBorders' => [

                'borderStyle' =>
                    Border::BORDER_THIN,

                'color' => [

                    'rgb' => 'DDDDDD'
                ]
            ]
        ]
    ]);


    /* تنسيق التكلفة */

    $sheet->getStyle(
        "G4:G{$lastDataRow}"
    )->getNumberFormat()
     ->setFormatCode('#,##0.00');
}


/* =========================================================
   تنسيق إجمالي التكلفة
========================================================= */

$sheet
    ->getStyle("G{$rowNumber}")
    ->getNumberFormat()
    ->setFormatCode('#,##0.00');


/* =========================================================
   أحجام الأعمدة
========================================================= */

$widths = [

    'A' => 8,
    'B' => 18,
    'C' => 25,
    'D' => 18,
    'E' => 25,
    'F' => 21,
    'G' => 30,
    'H' => 18,
    'I' => 18,
    'J' => 50,
    'K' => 12
];

foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}


/* =========================================================
   تجميد
========================================================= */

$sheet->freezePane('A4');


/* =========================================================
   Auto Filter داخل Excel
========================================================= */

$lastFilterRow =
    max(3, $rowNumber - 1);

$sheet->setAutoFilter(
    "A3:J{$lastFilterRow}"
);


/* =========================================================
   إعدادات الصفحة
========================================================= */

$sheet->getPageSetup()
    ->setOrientation(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
    );

$sheet->getPageSetup()
    ->setPaperSize(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
    );


/* =========================================================
   اسم الملف
========================================================= */

$filename =
    'accidents_' .
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
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
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

$writer = new Xlsx(
    $spreadsheet
);

$writer->save(
    'php://output'
);

$stmt->close();

exit;