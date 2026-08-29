<?php

session_start();

include('../include/connected.php');

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;


/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';

$lang = in_array($lang, ['ar', 'en'])
    ? $lang
    : 'ar';


/* =========================
   الفلاتر
========================= */

$search = trim($_GET['search'] ?? '');

$type_filter = trim(
    $_GET['maintenance_type'] ?? ''
);

$date_from = trim(
    $_GET['date_from'] ?? ''
);

$date_to = trim(
    $_GET['date_to'] ?? ''
);


/* =========================
   الاستعلام
========================= */

$sql = "
    SELECT
        maintenance.id,
        maintenance.vehicle_name,
        maintenance.plate_number,
        drivers.name AS driver_name,
        maintenance.maintenance_type,
        maintenance.cost,
        maintenance.notes,
        maintenance.maintenance_date
    FROM maintenance

    LEFT JOIN drivers
        ON maintenance.driver_id = drivers.id

    WHERE 1=1
";


$params = [];

$types = "";


/* البحث */

if($search !== ''){

    $sql .= "
        AND (
            maintenance.vehicle_name LIKE ?
            OR maintenance.plate_number LIKE ?
            OR drivers.name LIKE ?
            OR maintenance.maintenance_type LIKE ?
            OR maintenance.notes LIKE ?
        )
    ";

    $value = "%{$search}%";

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sssss";
}


/* نوع الصيانة */

if($type_filter !== ''){

    $sql .= "
        AND maintenance.maintenance_type = ?
    ";

    $params[] = $type_filter;

    $types .= "s";
}


/* من تاريخ */

if($date_from !== ''){

    $sql .= "
        AND maintenance.maintenance_date >= ?
    ";

    $params[] = $date_from;

    $types .= "s";
}


/* إلى تاريخ */

if($date_to !== ''){

    $sql .= "
        AND maintenance.maintenance_date <= ?
    ";

    $params[] = $date_to;

    $types .= "s";
}


$sql .= "
    ORDER BY
        maintenance.maintenance_date DESC,
        maintenance.id DESC
";


$stmt = $con->prepare($sql);


if(!empty($params)){

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$result = $stmt->get_result();


/* =========================
   إنشاء Excel
========================= */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();


if($lang === 'ar'){

    $sheet->setTitle('سجل الصيانة');

    $headers = [
        '#',
        'المركبة',
        'رقم اللوحة',
        'السائق',
        'نوع الصيانة',
        'التكلفة',
        'الملاحظات',
        'تاريخ الصيانة'
    ];

}else{

    $sheet->setTitle('Maintenance');

    $headers = [
        '#',
        'Vehicle',
        'Plate Number',
        'Driver',
        'Maintenance Type',
        'Cost',
        'Notes',
        'Maintenance Date'
    ];

}


/* =========================
   عنوان التقرير
========================= */

$sheet->mergeCells('A1:H1');

$sheet->setCellValue(
    'A1',
    $lang === 'ar'
        ? 'سجل صيانة المركبات'
        : 'Vehicle Maintenance Log'
);

$sheet->getStyle('A1:H1')->applyFromArray([

    'font' => [
        'bold' => true,
        'size' => 16
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]

]);

$sheet->getRowDimension(1)->setRowHeight(28);


/* =========================
   الفلاتر المستخدمة
========================= */

$filter_text = '';

if($search !== ''){
    $filter_text .=
        ($lang === 'ar' ? 'بحث: ' : 'Search: ')
        . $search
        . ' | ';
}

if($type_filter !== ''){
    $filter_text .=
        ($lang === 'ar' ? 'النوع: ' : 'Type: ')
        . $type_filter
        . ' | ';
}

if($date_from !== ''){
    $filter_text .=
        ($lang === 'ar' ? 'من: ' : 'From: ')
        . $date_from
        . ' | ';
}

if($date_to !== ''){
    $filter_text .=
        ($lang === 'ar' ? 'إلى: ' : 'To: ')
        . $date_to;
}


if($filter_text !== ''){

    $sheet->mergeCells('A2:H2');

    $sheet->setCellValue(
        'A2',
        $filter_text
    );

    $sheet->getStyle('A2:H2')->applyFromArray([

        'font' => [
            'italic' => true,
            'size' => 10
        ],

        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]

    ]);

}


/* =========================
   رؤوس الجدول
========================= */

$headerRow = 4;

foreach($headers as $index => $header){

    $column = chr(65 + $index);

    $sheet->setCellValue(
        $column . $headerRow,
        $header
    );

}


$sheet->getStyle(
    "A{$headerRow}:H{$headerRow}"
)->applyFromArray([

    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '28A745'
        ]
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]

]);


/* =========================
   البيانات
========================= */

$rowNumber = 5;

$total_cost = 0;

while($row = $result->fetch_assoc()){

    $cost = (float)($row['cost'] ?? 0);

    $total_cost += $cost;


    $sheet->setCellValue(
        "A{$rowNumber}",
        (int)$row['id']
    );

    $sheet->setCellValue(
        "B{$rowNumber}",
        $row['vehicle_name'] ?? '-'
    );

    $sheet->setCellValue(
        "C{$rowNumber}",
        $row['plate_number'] ?? '-'
    );

    $sheet->setCellValue(
        "D{$rowNumber}",
        $row['driver_name'] ?? '-'
    );

    $sheet->setCellValue(
        "E{$rowNumber}",
        $row['maintenance_type'] ?? '-'
    );

    $sheet->setCellValue(
        "F{$rowNumber}",
        $cost
    );

    $sheet->setCellValue(
        "G{$rowNumber}",
        $row['notes'] ?? '-'
    );

    $sheet->setCellValue(
        "H{$rowNumber}",
        $row['maintenance_date'] ?? '-'
    );


    $rowNumber++;

}


/* =========================
   الإجمالي
========================= */

$sheet->setCellValue(
    "E{$rowNumber}",
    $lang === 'ar'
        ? 'إجمالي التكلفة'
        : 'Total Cost'
);

$sheet->setCellValue(
    "F{$rowNumber}",
    $total_cost
);


$sheet->getStyle(
    "E{$rowNumber}:F{$rowNumber}"
)->applyFromArray([

    'font' => [
        'bold' => true
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => 'EAF7EE'
        ]
    ]

]);


/* =========================
   تنسيق البيانات
========================= */

$lastRow = $rowNumber;

$sheet->getStyle(
    "A{$headerRow}:H{$lastRow}"
)->applyFromArray([

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
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


/* =========================
   تنسيق التكلفة
========================= */

$sheet->getStyle(
    "F5:F{$lastRow}"
)->getNumberFormat()
  ->setFormatCode('#,##0.00');


/* =========================
   عرض الأعمدة
========================= */

$widths = [

    'A' => 10,
    'B' => 25,
    'C' => 18,
    'D' => 25,
    'E' => 25,
    'F' => 15,
    'G' => 40,
    'H' => 18

];


foreach($widths as $column => $width){

    $sheet->getColumnDimension($column)
          ->setWidth($width);

}


/* =========================
   اتجاه عربي
========================= */

if($lang === 'ar'){

    $sheet->setRightToLeft(true);

}


/* =========================
   تنزيل الملف
========================= */

$filename =
    'maintenance_report_'
    . date('Y-m-d')
    . '.xlsx';


header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');


$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;