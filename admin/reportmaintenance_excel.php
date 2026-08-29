<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر - نفس reportmaintenance.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$plate = trim($_GET['plate'] ?? '');

$driver = trim($_GET['driver'] ?? '');

$Workshop = trim($_GET['Workshop'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'           => 'تقرير الصيانة',

        'company_report'  => 'تقرير صيانة المركبات',

        'id'              => '#',
        'Workshop'         => 'الورشة',
        'plate'           => 'رقم اللوحة',
        'driver'          => 'السائق',
        'maintenance'     => 'نوع الصيانة',
        'cost'            => 'التكلفة',
        'date'             => 'التاريخ',

        'filters'         => 'الفلاتر المستخدمة',

        'search_filter'   => 'البحث',
        'plate_filter'    => 'اللوحة',
        'driver_filter'   => 'السائق',
        'vehicle_filter'  => 'المركبة',
        'from_filter'     => 'من',
        'to_filter'       => 'إلى',

        'all_records'     => 'جميع السجلات',

        'total_records'   => 'إجمالي السجلات',
        'total_cost'      => 'إجمالي التكلفة',
        'average_cost'    => 'متوسط التكلفة',

        'no_data'         => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'sar'             => 'ريال',

        'generated_at'    => 'تاريخ إنشاء التقرير'

    ],

    'en' => [

        'title'           => 'Maintenance Report',

        'company_report'  => 'Vehicle Maintenance Report',

        'id'              => '#',
        'Workshop'         => 'Workshop',
        'plate'           => 'Plate Number',
        'driver'          => 'Driver',
        'maintenance'     => 'Maintenance Type',
        'cost'            => 'Cost',
        'date'             => 'Date',

        'filters'         => 'Applied Filters',

        'search_filter'   => 'Search',
        'plate_filter'    => 'Plate',
        'driver_filter'   => 'Driver',
        'vehicle_filter'  => 'Vehicle',
        'from_filter'     => 'From',
        'to_filter'       => 'To',

        'all_records'     => 'All Records',

        'total_records'   => 'Total Records',
        'total_cost'      => 'Total Cost',
        'average_cost'    => 'Average Cost',

        'no_data'         => 'No maintenance records match the selected filters',

        'sar'             => 'SAR',

        'generated_at'    => 'Generated At'

    ]

];

$t = $text[$lang];

/* =========================================================
   بناء WHERE
========================================================= */

$where = " WHERE 1 = 1 ";

$params = [];

$types = "";

/* =========================================================
   البحث العام
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            plate_number LIKE ?
            OR driver LIKE ?
            OR vehicle_name LIKE ?
            OR maintenance_type LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "ssss";
}

/* =========================================================
   اللوحة
========================================================= */

if ($plate !== '') {

    $where .= "
        AND plate_number LIKE ?
    ";

    $params[] =
        '%' . $plate . '%';

    $types .= "s";
}

/* =========================================================
   السائق
========================================================= */

if ($driver !== '') {

    $where .= "
        AND driver LIKE ?
    ";

    $params[] =
        '%' . $driver . '%';

    $types .= "s";
}

/* =========================================================
   المركبة
========================================================= */

if ($Workshop !== '') {

    $where .= "
        AND Workshop LIKE ?
    ";

    $params[] =
        '%' . $Workshop . '%';

    $types .= "s";
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND DATE(maintenance_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND DATE(maintenance_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        id,
        vehicle_name,
        plate_number,
        driver,
        maintenance_type,
        cost,
        maintenance_date

    FROM maintenance

    $where

    ORDER BY
        maintenance_date DESC,
        id DESC
";

/* =========================================================
   Prepare
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

/* =========================================================
   Bind
========================================================= */

if (!empty($params)) {

    if (
        strlen($types) !==
        count($params)
    ) {

        die(
            'Filter parameters mismatch.'
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

/* =========================================================
   Execute
========================================================= */

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   البيانات
========================================================= */

$rows = [];

$totalCost = 0;

while ($row = $result->fetch_assoc()) {

    $cost =
        (float)($row['cost'] ?? 0);

    $totalCost += $cost;

    $rows[] = $row;
}

$totalRecords = count($rows);

$averageCost =
    $totalRecords > 0
        ? $totalCost / $totalRecords
        : 0;

/* =========================================================
   إعدادات الشركة
========================================================= */

$settingsData = [];

$settingsResult = $con->query("
    SELECT
        setting_key,
        setting_value
    FROM settings
");

if ($settingsResult) {

    while (
        $setting =
        $settingsResult->fetch_assoc()
    ) {

        $settingsData[
            $setting['setting_key']
        ] =
            $setting['setting_value'];
    }
}

$companyName =
    $settingsData['company_name']
    ?? 'AlSharqPlatform';

$companyLogo =
    $settingsData['company_logo']
    ?? '';

/* =========================================================
   إنشاء Spreadsheet
========================================================= */

$spreadsheet = new Spreadsheet();

$sheet =
    $spreadsheet->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير الصيانة'
        : 'Maintenance Report'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   الشعار
========================================================= */

$logoPath = '';

if ($companyLogo !== '') {

    $logoCandidates = [

        __DIR__ .
        '/../uploads/logo/' .
        basename($companyLogo),

        __DIR__ .
        '/../' .
        ltrim(
            str_replace(
                '\\',
                '/',
                $companyLogo
            ),
            '/'
        )

    ];

    foreach (
        $logoCandidates
        as $candidate
    ) {

        $real =
            realpath(
                $candidate
            );

        if (
            $real !== false &&
            is_file($real)
        ) {

            $logoPath = $real;

            break;
        }
    }
}

if ($logoPath !== '') {

    $drawing =
        new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();

    $drawing->setName(
        'Company Logo'
    );

    $drawing->setDescription(
        $companyName
    );

    $drawing->setPath(
        $logoPath
    );

    $drawing->setHeight(60);

    $drawing->setCoordinates(
        'G1'
    );

    $drawing->setOffsetX(5);

    $drawing->setOffsetY(5);

    $drawing->setWorksheet(
        $sheet
    );
}

/* =========================================================
   العناوين
========================================================= */

$sheet->mergeCells(
    'A1:G1'
);

$sheet->setCellValue(
    'A1',
    $companyName
);

$sheet->mergeCells(
    'A2:G2'
);

$sheet->setCellValue(
    'A2',
    $t['company_report']
);

$sheet->mergeCells(
    'A3:G3'
);

$sheet->setCellValue(
    'A3',
    $t['generated_at'] .
    ': ' .
    date('Y-m-d H:i')
);

/* =========================================================
   تنسيق العناوين
========================================================= */

$sheet
    ->getStyle('A1:G1')
    ->applyFromArray([

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

                'rgb' => 'F59E0B'
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
    ->getStyle('A2:G2')
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 13,

            'color' => [
                'rgb' => 'FFFFFF'
            ]

        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [

                'rgb' => '198754'
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
    ->getStyle('A3:G3')
    ->applyFromArray([

        'font' => [

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

$sheet
    ->getRowDimension(1)
    ->setRowHeight(35);

$sheet
    ->getRowDimension(2)
    ->setRowHeight(28);

$sheet
    ->getRowDimension(3)
    ->setRowHeight(22);

/* =========================================================
   الفلاتر
========================================================= */

$filterText = [];

/* البحث */

if ($search !== '') {

    $filterText[] =
        $t['search_filter'] .
        ': ' .
        $search;
}

/* اللوحة */

if ($plate !== '') {

    $filterText[] =
        $t['plate_filter'] .
        ': ' .
        $plate;
}

/* السائق */

if ($driver !== '') {

    $filterText[] =
        $t['driver_filter'] .
        ': ' .
        $driver;
}

/* المركبة */

if ($vehicle !== '') {

    $filterText[] =
        $t['vehicle_filter'] .
        ': ' .
        $vehicle;
}

/* من */

if ($from !== '') {

    $filterText[] =
        $t['from_filter'] .
        ': ' .
        $from;
}

/* إلى */

if ($to !== '') {

    $filterText[] =
        $t['to_filter'] .
        ': ' .
        $to;
}

$filterString =
    !empty($filterText)
        ? implode(
            ' | ',
            $filterText
        )
        : $t['all_records'];

$sheet->mergeCells(
    'A4:G4'
);

$sheet->setCellValue(
    'A4',
    $filterString
);

$sheet
    ->getStyle('A4:G4')
    ->applyFromArray([

        'font' => [

            'italic' => true,

            'size' => 10,

            'color' => [
                'rgb' => '666666'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [

                'rgb' => 'F1F5F9'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER,

            'wrapText' => true
        ]

    ]);

$sheet
    ->getRowDimension(4)
    ->setRowHeight(25);

/* =========================================================
   ملخص الإحصائيات
========================================================= */

$sheet->setCellValue(
    'A5',
    $t['total_records']
);

$sheet->setCellValue(
    'B5',
    $totalRecords
);

$sheet->setCellValue(
    'C5',
    $t['total_cost']
);

$sheet->setCellValue(
    'D5',
    $totalCost
);

$sheet->setCellValue(
    'E5',
    $t['average_cost']
);

$sheet->setCellValue(
    'F5',
    $averageCost
);

$sheet->getStyle(
    'A5:F5'
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

$sheet
    ->getStyle('D5')
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

$sheet
    ->getStyle('F5')
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   رؤوس الأعمدة
========================================================= */

$headers = [

    'A7' => $t['id'],

    'B7' => $t['Workshop'],

    'C7' => $t['plate'],

    'D7' => $t['driver'],

    'E7' => $t['maintenance'],

    'F7' => $t['cost'],

    'G7' => $t['date']

];

foreach (
    $headers
    as $cell => $value
) {

    $sheet->setCellValue(
        $cell,
        $value
    );
}

$sheet
    ->getStyle('A7:G7')
    ->applyFromArray([

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

$sheet
    ->getRowDimension(7)
    ->setRowHeight(28);

/* =========================================================
   البيانات
========================================================= */

$rowNumber = 8;

$counter = 1;

if (empty($rows)) {

    $sheet->mergeCells(
        'A8:G8'
    );

    $sheet->setCellValue(
        'A8',
        $t['no_data']
    );

    $sheet
        ->getStyle('A8:G8')
        ->applyFromArray([

            'font' => [

                'bold' => true,

                'color' => [
                    'rgb' => '777777'
                ]

            ],

            'alignment' => [

                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER
            ]

        ]);

    $rowNumber = 9;

} else {

    foreach ($rows as $row) {

        $sheet->setCellValue(
            "A{$rowNumber}",
            $counter
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
            $row['driver'] ?? '-'
        );

        $sheet->setCellValue(
            "E{$rowNumber}",
            $row['maintenance_type'] ?? '-'
        );

        $sheet->setCellValue(
            "F{$rowNumber}",
            (float)(
                $row['cost'] ?? 0
            )
        );

        $sheet->setCellValue(
            "G{$rowNumber}",
            $row['maintenance_date'] ?? ''
        );

        $sheet
            ->getStyle(
                "A{$rowNumber}:G{$rowNumber}"
            )
            ->applyFromArray([

                'alignment' => [

                    'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,

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

        $sheet
            ->getStyle(
                "F{$rowNumber}"
            )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );

        $rowNumber++;

        $counter++;
    }
}

/* =========================================================
   إجمالي التكلفة في أسفل الجدول
========================================================= */

$totalRow = $rowNumber + 1;

$sheet->mergeCells(
    "A{$totalRow}:E{$totalRow}"
);

$sheet->setCellValue(
    "A{$totalRow}",
    $t['total_cost']
);

$sheet->setCellValue(
    "F{$totalRow}",
    $totalCost
);

$sheet->getStyle(
    "A{$totalRow}:F{$totalRow}"
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

$sheet
    ->getStyle(
        "F{$totalRow}"
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   أحجام الأعمدة
========================================================= */

$widths = [

    'A' => 8,

    'B' => 30,

    'C' => 18,

    'D' => 25,

    'E' => 30,

    'F' => 18,

    'G' => 18

];

foreach (
    $widths
    as $column => $width
) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}

/* =========================================================
   تجميد
========================================================= */

$sheet->freezePane('A8');

/* =========================================================
   Auto Filter
========================================================= */

$lastDataRow =
    max(
        7,
        $rowNumber - 1
    );

$sheet->setAutoFilter(
    "A7:G{$lastDataRow}"
);

/* =========================================================
   إعدادات الطباعة
========================================================= */

$sheet
    ->getPageSetup()
    ->setOrientation(
        PageSetup::ORIENTATION_LANDSCAPE
    );

$sheet
    ->getPageSetup()
    ->setPaperSize(
        PageSetup::PAPERSIZE_A4
    );

$sheet
    ->getPageMargins()
    ->setTop(0.5)
    ->setBottom(0.5)
    ->setLeft(0.5)
    ->setRight(0.5);

$sheet
    ->getPageSetup()
    ->setRowsToRepeatAtTopByStartAndEnd(
        1,
        7
    );

$sheet
    ->getPageSetup()
    ->setPrintArea(
        "A1:G{$totalRow}"
    );

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'reportmaintenance_' .
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
    $fileName .
    '"'
);

header(
    'Cache-Control: max-age=0'
);

header(
    'Pragma: public'
);

/* =========================================================
   إخراج Excel
========================================================= */

$writer =
    new Xlsx(
        $spreadsheet
    );

$writer->save(
    'php://output'
);

$stmt->close();

exit;