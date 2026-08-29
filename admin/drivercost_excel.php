
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/* =========================================================
   LANGUAGE
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   DRIVER ID
========================================================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die(
        $lang === 'ar'
            ? 'رقم السائق غير صحيح'
            : 'Invalid driver ID'
    );
}

/* =========================================================
   DATE FILTERS
========================================================= */

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');

/* =========================================================
   TRANSLATIONS
========================================================= */

$t = [

    'ar' => [

        'company'       => 'شركة الشرق لخدمات السيارات',
        'title'         => 'الملف المالي والتشغيلي للسائق',

        'driver'        => 'السائق',
        'plate'         => 'رقم اللوحة',
        'national_id'   => 'رقم الهوية',
        'phone'         => 'الجوال',
        'work_area'     => 'منطقة العمل',
        'truck_type'    => 'نوع السطحة',

        'period'        => 'الفترة',
        'all_period'    => 'كل الفترات',
        'report_date'   => 'تاريخ التقرير',

        'summary'       => 'ملخص التكاليف',

        'oil'           => 'الزيت',
        'tires'         => 'الإطارات',
        'maintenance'   => 'الصيانة',
        'grand_total'   => 'الإجمالي الكلي',

        'oil_records'   => 'سجل تغيير الزيت',
        'tire_records'  => 'سجل الإطارات',
        'maint_records' => 'سجل الصيانة',

        'date'          => 'التاريخ',
        'type'          => 'النوع',
        'km'            => 'العداد الحالي',
        'next_km'       => 'العداد القادم',
        'next_change'   => 'التغيير القادم',
        'vehicle'       => 'المركبة',
        'cost'          => 'التكلفة',
        'notes'         => 'الملاحظات',

        'no_data'       => 'لا توجد سجلات',

        'sar'           => 'ريال'
    ],

    'en' => [

        'company'       => 'Al Sharq Automotive Services Company',
        'title'         => 'Driver Financial & Operational Report',

        'driver'        => 'Driver',
        'plate'         => 'Plate Number',
        'national_id'   => 'National ID',
        'phone'         => 'Phone',
        'work_area'     => 'Work Area',
        'truck_type'    => 'Truck Type',

        'period'        => 'Period',
        'all_period'    => 'All Periods',
        'report_date'   => 'Report Date',

        'summary'       => 'Cost Summary',

        'oil'           => 'Oil',
        'tires'         => 'Tires',
        'maintenance'   => 'Maintenance',
        'grand_total'   => 'Grand Total',

        'oil_records'   => 'Oil Change Records',
        'tire_records'  => 'Tire Records',
        'maint_records' => 'Maintenance Records',

        'date'          => 'Date',
        'type'          => 'Type',
        'km'            => 'Current KM',
        'next_km'       => 'Next KM',
        'next_change'   => 'Next Change',
        'vehicle'       => 'Vehicle',
        'cost'          => 'Cost',
        'notes'         => 'Notes',

        'no_data'       => 'No records found',

        'sar'           => 'SAR'
    ]
];

$tr = $t[$lang];

/* =========================================================
   DRIVER DATA
========================================================= */

$stmt = $con->prepare("
    SELECT
        id,
        name,
        national_id,
        phone,
        work_area,
        truck_type,
        plate_number,
        imagedriver
    FROM drivers
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$driver = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$driver) {
    die(
        $lang === 'ar'
            ? 'السائق غير موجود'
            : 'Driver not found'
    );
}

/* =========================================================
   HELPER
========================================================= */

function fetchRows(
    mysqli $con,
    string $sql,
    string $types,
    array $params
): array {

    $stmt = $con->prepare($sql);

    if (!$stmt) {
        die(
            'SQL Error: ' .
            htmlspecialchars($con->error)
        );
    }

    if (!empty($params)) {
        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    if (!$stmt->execute()) {
        die(
            'Execute Error: ' .
            htmlspecialchars($stmt->error)
        );
    }

    $result = $stmt->get_result();

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

/* =========================================================
   OIL
========================================================= */

$oilSql = "
    SELECT
        id,
        change_date,
        oil_type,
        km_change,
        current_km,
        next_km,
        next_change,
        cost,
        notes
    FROM oil_changes
    WHERE driver_id = ?
";

$oilParams = [$id];
$oilTypes  = 'i';

if ($from !== '') {

    $oilSql .= "
        AND DATE(change_date) >= ?
    ";

    $oilParams[] = $from;
    $oilTypes .= 's';
}

if ($to !== '') {

    $oilSql .= "
        AND DATE(change_date) <= ?
    ";

    $oilParams[] = $to;
    $oilTypes .= 's';
}

$oilSql .= "
    ORDER BY change_date DESC, id DESC
";

$oilRows = fetchRows(
    $con,
    $oilSql,
    $oilTypes,
    $oilParams
);

/* =========================================================
   TIRES
========================================================= */

$tireSql = "
    SELECT
        id,
        change_date,
        tire_type,
        current_km,
        next_km,
        next_change,
        cost,
        notes
    FROM tires
    WHERE driver_id = ?
";

$tireParams = [$id];
$tireTypes  = 'i';

if ($from !== '') {

    $tireSql .= "
        AND DATE(change_date) >= ?
    ";

    $tireParams[] = $from;
    $tireTypes .= 's';
}

if ($to !== '') {

    $tireSql .= "
        AND DATE(change_date) <= ?
    ";

    $tireParams[] = $to;
    $tireTypes .= 's';
}

$tireSql .= "
    ORDER BY change_date DESC, id DESC
";

$tireRows = fetchRows(
    $con,
    $tireSql,
    $tireTypes,
    $tireParams
);

/* =========================================================
   MAINTENANCE
========================================================= */

$maintSql = "
    SELECT
        id,
        maintenance_date,
        maintenance_type,
        vehicle_name,
        cost,
        notes
    FROM maintenance
    WHERE driver_id = ?
";

$maintParams = [$id];
$maintTypes  = 'i';

if ($from !== '') {

    $maintSql .= "
        AND DATE(maintenance_date) >= ?
    ";

    $maintParams[] = $from;
    $maintTypes .= 's';
}

if ($to !== '') {

    $maintSql .= "
        AND DATE(maintenance_date) <= ?
    ";

    $maintParams[] = $to;
    $maintTypes .= 's';
}

$maintSql .= "
    ORDER BY maintenance_date DESC, id DESC
";

$maintenanceRows = fetchRows(
    $con,
    $maintSql,
    $maintTypes,
    $maintParams
);

/* =========================================================
   TOTALS
========================================================= */

$totalOil = 0;
$totalTires = 0;
$totalMaintenance = 0;

foreach ($oilRows as $item) {
    $totalOil += (float)($item['cost'] ?? 0);
}

foreach ($tireRows as $item) {
    $totalTires += (float)($item['cost'] ?? 0);
}

foreach ($maintenanceRows as $item) {
    $totalMaintenance += (float)($item['cost'] ?? 0);
}

$grandTotal =
    $totalOil +
    $totalTires +
    $totalMaintenance;

/* =========================================================
   SPREADSHEET
========================================================= */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'الملف المالي'
        : 'Driver Report'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   HEADER
========================================================= */

$sheet->mergeCells('A1:H1');

$sheet->setCellValue(
    'A1',
    $tr['company']
);

$sheet->mergeCells('A2:H2');

$sheet->setCellValue(
    'A2',
    $tr['title']
);

$periodText =
    ($from !== '' || $to !== '')
        ? (
            ($from !== '' ? $from : '...')
            .
            ' - '
            .
            ($to !== '' ? $to : '...')
        )
        : $tr['all_period'];

$sheet->mergeCells('A3:H3');

$sheet->setCellValue(
    'A3',
    $tr['period'] .
    ': ' .
    $periodText .
    ' | ' .
    $tr['report_date'] .
    ': ' .
    date('Y-m-d H:i')
);

/* =========================================================
   HEADER STYLE
========================================================= */

$sheet
    ->getStyle('A1:H1')
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 17,

            'color' => [
                'rgb' => 'FFFFFF'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => '0D6EFD'
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
    ->getStyle('A2:H2')
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 14,

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
    ->getStyle('A3:H3')
    ->applyFromArray([

        'font' => [

            'italic' => true,

            'color' => [
                'rgb' => '666666'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER
        ]
    ]);

$sheet
    ->getRowDimension(1)
    ->setRowHeight(32);

$sheet
    ->getRowDimension(2)
    ->setRowHeight(28);

/* =========================================================
   DRIVER INFORMATION
========================================================= */

$row = 5;

$driverInfo = [

    $tr['driver']
        => $driver['name'] ?? '-',

    $tr['plate']
        => $driver['plate_number'] ?? '-',

    $tr['national_id']
        => $driver['national_id'] ?? '-',

    $tr['phone']
        => $driver['phone'] ?? '-',

    $tr['work_area']
        => $driver['work_area'] ?? '-',

    $tr['truck_type']
        => $driver['truck_type'] ?? '-'
];

foreach ($driverInfo as $label => $value) {

    $sheet->setCellValue(
        "A{$row}",
        $label
    );

    $sheet->mergeCells(
        "B{$row}:H{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $value
    );

    $row++;
}

$sheet
    ->getStyle(
        "A5:H" . ($row - 1)
    )
    ->applyFromArray([

        'borders' => [

            'allBorders' => [

                'borderStyle' =>
                    Border::BORDER_THIN,

                'color' => [
                    'rgb' => 'DDDDDD'
                ]
            ]
        ],

        'alignment' => [

            'vertical' =>
                Alignment::VERTICAL_CENTER,

            'wrapText' => true
        ]
    ]);

$sheet
    ->getStyle(
        "A5:A" . ($row - 1)
    )
    ->getFont()
    ->setBold(true);

/* =========================================================
   COST SUMMARY
========================================================= */

$row += 2;

$sheet->mergeCells(
    "A{$row}:H{$row}"
);

$sheet->setCellValue(
    "A{$row}",
    $tr['summary']
);

$sheet
    ->getStyle(
        "A{$row}:H{$row}"
    )
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
                'rgb' => '343A40'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER
        ]
    ]);

$row++;

$summaryStart = $row;

$summary = [

    $tr['oil'] =>
        $totalOil,

    $tr['tires'] =>
        $totalTires,

    $tr['maintenance'] =>
        $totalMaintenance,

    $tr['grand_total'] =>
        $grandTotal
];

foreach ($summary as $label => $value) {

    $sheet->setCellValue(
        "A{$row}",
        $label
    );

    $sheet->mergeCells(
        "B{$row}:G{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $value
    );

    $sheet->setCellValue(
        "H{$row}",
        $tr['sar']
    );

    $row++;
}

$sheet
    ->getStyle(
        "A{$summaryStart}:H" .
        ($row - 1)
    )
    ->applyFromArray([

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
        "B{$summaryStart}:G" .
        ($row - 1)
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   SECTION FUNCTION
========================================================= */

function addSection(
    $sheet,
    &$row,
    $title,
    $headers,
    $records,
    $color,
    $noData
) {

    /* Section title */

    $sheet->mergeCells(
        "A{$row}:H{$row}"
    );

    $sheet->setCellValue(
        "A{$row}",
        $title
    );

    $sheet
        ->getStyle(
            "A{$row}:H{$row}"
        )
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
                    'rgb' => $color
                ]
            ],

            'alignment' => [

                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER
            ]
        ]);

    $row++;

    /* Headers */

    foreach ($headers as $i => $header) {

        $sheet->setCellValue(
            chr(65 + $i) . $row,
            $header
        );
    }

    $sheet
        ->getStyle(
            "A{$row}:H{$row}"
        )
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
                    'rgb' => '495057'
                ]
            ],

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
                        'rgb' => 'CCCCCC'
                    ]
                ]
            ]
        ]);

    $row++;

    /* No records */

    if (empty($records)) {

        $sheet->mergeCells(
            "A{$row}:H{$row}"
        );

        $sheet->setCellValue(
            "A{$row}",
            $noData
        );

        $row++;

        return;
    }

    /* Records */

    foreach ($records as $record) {

        foreach ($record as $i => $value) {

            $sheet->setCellValue(
                chr(65 + $i) . $row,
                $value
            );
        }

        $row++;
    }
}

/* =========================================================
   OIL SECTION
========================================================= */

$oilExport = [];

$counter = 1;

foreach ($oilRows as $item) {

    $oilExport[] = [

        $counter++,

        $item['change_date'] ?? '-',

        $item['oil_type'] ?? '-',

        $item['current_km']
            ?? $item['km_change']
            ?? 0,

        $item['next_km'] ?? 0,

        $item['next_change'] ?? '-',

        (float)($item['cost'] ?? 0),

        $item['notes'] ?? '-'
    ];
}

$row += 2;

addSection(
    $sheet,
    $row,
    $tr['oil_records'],
    [

        '#',
        $tr['date'],
        $tr['type'],
        $tr['km'],
        $tr['next_km'],
        $tr['next_change'],
        $tr['cost'],
        $tr['notes']

    ],
    $oilExport,
    '198754',
    $tr['no_data']
);

/* =========================================================
   TIRES SECTION
========================================================= */

$tireExport = [];

$counter = 1;

foreach ($tireRows as $item) {

    $tireExport[] = [

        $counter++,

        $item['change_date'] ?? '-',

        $item['tire_type'] ?? '-',

        $item['current_km'] ?? 0,

        $item['next_km'] ?? 0,

        $item['next_change'] ?? '-',

        (float)($item['cost'] ?? 0),

        $item['notes'] ?? '-'
    ];
}

$row += 2;

addSection(
    $sheet,
    $row,
    $tr['tire_records'],
    [

        '#',
        $tr['date'],
        $tr['type'],
        $tr['km'],
        $tr['next_km'],
        $tr['next_change'],
        $tr['cost'],
        $tr['notes']

    ],
    $tireExport,
    'FD7E14',
    $tr['no_data']
);

/* =========================================================
   MAINTENANCE SECTION
========================================================= */

$maintExport = [];

$counter = 1;

foreach ($maintenanceRows as $item) {

    $maintExport[] = [

        $counter++,

        $item['maintenance_date'] ?? '-',

        $item['maintenance_type'] ?? '-',

        $item['vehicle_name'] ?? '-',

        '',

        '',

        (float)($item['cost'] ?? 0),

        $item['notes'] ?? '-'
    ];
}

$row += 2;

addSection(
    $sheet,
    $row,
    $tr['maint_records'],
    [

        '#',
        $tr['date'],
        $tr['type'],
        $tr['vehicle'],
        '',
        '',
        $tr['cost'],
        $tr['notes']

    ],
    $maintExport,
    '0D6EFD',
    $tr['no_data']
);

/* =========================================================
   FINAL TOTAL
========================================================= */

$row += 2;

$sheet->mergeCells(
    "A{$row}:G{$row}"
);

$sheet->setCellValue(
    "A{$row}",
    $tr['grand_total']
);

$sheet->setCellValue(
    "H{$row}",
    $grandTotal
);

$sheet
    ->getStyle(
        "A{$row}:H{$row}"
    )
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 15,

            'color' => [
                'rgb' => '198754'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => 'E9F7EF'
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
                    'rgb' => '198754'
                ]
            ]
        ]
    ]);

$sheet
    ->getStyle("H{$row}")
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   GLOBAL FORMAT
========================================================= */

$sheet
    ->getStyle(
        "A1:H{$row}"
    )
    ->getAlignment()
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );

$sheet
    ->getStyle(
        "A1:H{$row}"
    )
    ->getAlignment()
    ->setWrapText(true);

/* =========================================================
   COLUMN WIDTH
========================================================= */

$widths = [

    'A' => 9,
    'B' => 20,
    'C' => 25,
    'D' => 18,
    'E' => 18,
    'F' => 24,
    'G' => 18,
    'H' => 45
];

foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}

/* =========================================================
   PAGE SETTINGS
========================================================= */

$sheet->freezePane('A5');

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
    ->setPrintArea(
        "A1:H{$row}"
    );

/* =========================================================
   FILE NAME
========================================================= */

$safePlate = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    (string)(
        $driver['plate_number']
        ?? 'driver'
    )
);

$fileName =
    'driver_cost_' .
    $safePlate .
    '_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';

/* =========================================================
   CLEAN OUTPUT
========================================================= */

while (ob_get_level()) {
    ob_end_clean();
}

/* =========================================================
   HEADERS
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
   SAVE
========================================================= */

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;

