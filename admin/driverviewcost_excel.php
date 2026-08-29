
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

mysqli_set_charset($con, 'utf8mb4');

/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر - نفس driverviewcost.php
========================================================= */

$search   = trim($_GET['search'] ?? '');
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');
$driverId = (int)($_GET['driver_id'] ?? 0);

/* =========================================================
   ترجمة
========================================================= */

$text = [

    'ar' => [

        'company'      => 'شركة الشرق لخدمات السيارات',
        'title'        => 'تقرير تكاليف السائقين',
        'period'       => 'الفترة',
        'generated'    => 'تاريخ التقرير',

        'driver'       => 'السائق',
        'plate'        => 'رقم اللوحة',
        'phone'        => 'الجوال',
        'work_area'    => 'منطقة العمل',

        'oil'          => 'الزيت',
        'tires'        => 'الإطارات',
        'maintenance'  => 'الصيانة',
        'total'        => 'الإجمالي',

        'total_oil'    => 'إجمالي الزيت',
        'total_tires' => 'إجمالي الإطارات',
        'total_maintenance' => 'إجمالي الصيانة',
        'grand_total' => 'الإجمالي الكلي',

        'all_periods'  => 'كل الفترات',
        'all_drivers'  => 'جميع السائقين',
        'no_data'      => 'لا توجد بيانات مطابقة للفلاتر',

        'sar'          => 'ريال'
    ],

    'en' => [

        'company'      => 'Al Sharq Automotive Services Company',
        'title'        => 'Driver Cost Report',
        'period'       => 'Period',
        'generated'    => 'Report Date',

        'driver'       => 'Driver',
        'plate'        => 'Plate Number',
        'phone'        => 'Phone',
        'work_area'    => 'Work Area',

        'oil'          => 'Oil',
        'tires'        => 'Tires',
        'maintenance'  => 'Maintenance',
        'total'        => 'Total',

        'total_oil'    => 'Total Oil',
        'total_tires' => 'Total Tires',
        'total_maintenance' => 'Total Maintenance',
        'grand_total' => 'Grand Total',

        'all_periods'  => 'All Periods',
        'all_drivers'  => 'All Drivers',
        'no_data'      => 'No data matches the selected filters',

        'sar'          => 'SAR'
    ]
];

$t = $text[$lang];

/* =========================================================
   شروط التاريخ
========================================================= */

$oilDateSql = '';
$oilParams = [];
$oilTypes = '';

if ($from !== '') {
    $oilDateSql .= " AND DATE(o.change_date) >= ? ";
    $oilParams[] = $from;
    $oilTypes .= 's';
}

if ($to !== '') {
    $oilDateSql .= " AND DATE(o.change_date) <= ? ";
    $oilParams[] = $to;
    $oilTypes .= 's';
}

/* =========================================================
   الإطارات
========================================================= */

$tireDateSql = '';
$tireParams = [];
$tireTypes = '';

if ($from !== '') {
    $tireDateSql .= " AND DATE(t.change_date) >= ? ";
    $tireParams[] = $from;
    $tireTypes .= 's';
}

if ($to !== '') {
    $tireDateSql .= " AND DATE(t.change_date) <= ? ";
    $tireParams[] = $to;
    $tireTypes .= 's';
}

/* =========================================================
   الصيانة
========================================================= */

$maintenanceDateSql = '';
$maintenanceParams = [];
$maintenanceTypes = '';

if ($from !== '') {
    $maintenanceDateSql .= " AND DATE(m.maintenance_date) >= ? ";
    $maintenanceParams[] = $from;
    $maintenanceTypes .= 's';
}

if ($to !== '') {
    $maintenanceDateSql .= " AND DATE(m.maintenance_date) <= ? ";
    $maintenanceParams[] = $to;
    $maintenanceTypes .= 's';
}

/* =========================================================
   فلتر السائق
========================================================= */

$driverWhere = '';
$driverParams = [];
$driverTypes = '';

if ($search !== '') {

    $driverWhere .= "
        AND (
            d.name LIKE ?
            OR d.plate_number LIKE ?
            OR d.phone LIKE ?
            OR d.work_area LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $driverParams[] = $value;
    $driverParams[] = $value;
    $driverParams[] = $value;
    $driverParams[] = $value;

    $driverTypes .= 'ssss';
}

if ($driverId > 0) {

    $driverWhere .= " AND d.id = ? ";

    $driverParams[] = $driverId;
    $driverTypes .= 'i';
}

/* =========================================================
   الاستعلام الرئيسي
   نفس تقرير driverviewcost.php
========================================================= */

$sql = "

    SELECT

        d.id,
        d.name,
        d.plate_number,
        d.phone,
        d.work_area,

        COALESCE(o.oil_total, 0) AS oil,

        COALESCE(t.tire_total, 0) AS tires,

        COALESCE(m.maint_total, 0) AS maintenance

    FROM drivers d

    LEFT JOIN (

        SELECT
            o.driver_id,
            SUM(o.cost) AS oil_total

        FROM oil_changes o

        WHERE 1 = 1

        $oilDateSql

        GROUP BY o.driver_id

    ) o

        ON o.driver_id = d.id

    LEFT JOIN (

        SELECT
            t.driver_id,
            SUM(t.cost) AS tire_total

        FROM tires t

        WHERE 1 = 1

        $tireDateSql

        GROUP BY t.driver_id

    ) t

        ON t.driver_id = d.id

    LEFT JOIN (

        SELECT
            m.driver_id,
            SUM(m.cost) AS maint_total

        FROM maintenance m

        WHERE 1 = 1

        $maintenanceDateSql

        GROUP BY m.driver_id

    ) m

        ON m.driver_id = d.id

    WHERE 1 = 1

    $driverWhere

    ORDER BY

        (
            COALESCE(o.oil_total, 0)
            +
            COALESCE(t.tire_total, 0)
            +
            COALESCE(m.maint_total, 0)
        ) DESC,

        d.name ASC
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
   جميع المتغيرات
========================================================= */

$allParams = array_merge(
    $oilParams,
    $tireParams,
    $maintenanceParams,
    $driverParams
);

$allTypes =
    $oilTypes .
    $tireTypes .
    $maintenanceTypes .
    $driverTypes;

if (!empty($allParams)) {

    if (strlen($allTypes) !== count($allParams)) {

        die('Filter parameters mismatch.');
    }

    $stmt->bind_param(
        $allTypes,
        ...$allParams
    );
}

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   قراءة النتائج
========================================================= */

$rows = [];

$totalOil = 0;
$totalTires = 0;
$totalMaintenance = 0;
$totalGrand = 0;

$topDriverName = '';
$topDriverCost = 0;

while ($row = $result->fetch_assoc()) {

    $oil = (float)($row['oil'] ?? 0);

    $tires = (float)($row['tires'] ?? 0);

    $maintenance =
        (float)($row['maintenance'] ?? 0);

    $total =
        $oil +
        $tires +
        $maintenance;

    $row['total'] = $total;

    $totalOil += $oil;
    $totalTires += $tires;
    $totalMaintenance += $maintenance;
    $totalGrand += $total;

    if ($total > $topDriverCost) {

        $topDriverCost = $total;

        $topDriverName =
            $row['name'] ?? '';
    }

    $rows[] = $row;
}

$stmt->close();

/* =========================================================
   Excel
========================================================= */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تكاليف السائقين'
        : 'Driver Costs'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   العنوان
========================================================= */

$sheet->mergeCells('A1:H1');

$sheet->setCellValue(
    'A1',
    $t['company']
);

$sheet->mergeCells('A2:H2');

$sheet->setCellValue(
    'A2',
    $t['title']
);

$periodText =
    ($from !== '' || $to !== '')
        ? (($from ?: '...') . ' - ' . ($to ?: '...'))
        : $t['all_periods'];

$sheet->mergeCells('A3:H3');

$sheet->setCellValue(
    'A3',
    $t['period'] .
    ': ' .
    $periodText .
    ' | ' .
    $t['generated'] .
    ': ' .
    date('Y-m-d H:i')
);

/* =========================================================
   تنسيق العناوين
========================================================= */

$sheet
    ->getStyle('A1:H1')
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
                Alignment::HORIZONTAL_CENTER
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
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER
        ]
    ]);

/* =========================================================
   معلومات الفلتر
========================================================= */

$row = 5;

if ($search !== '') {

    $sheet->setCellValue(
        "A{$row}",
        $lang === 'ar'
            ? 'البحث'
            : 'Search'
    );

    $sheet->mergeCells(
        "B{$row}:H{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $search
    );

    $row++;
}

if ($driverId > 0) {

    $driverStmt = $con->prepare("
        SELECT name, plate_number
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    $driverStmt->bind_param(
        "i",
        $driverId
    );

    $driverStmt->execute();

    $selectedDriver =
        $driverStmt
            ->get_result()
            ->fetch_assoc();

    $driverStmt->close();

    if ($selectedDriver) {

        $sheet->setCellValue(
            "A{$row}",
            $t['driver']
        );

        $sheet->mergeCells(
            "B{$row}:H{$row}"
        );

        $sheet->setCellValue(
            "B{$row}",
            $selectedDriver['name'] .
            ' - ' .
            $selectedDriver['plate_number']
        );

        $row++;
    }
}

/* =========================================================
   جدول التقرير
========================================================= */

$row += 2;

/* العنوان */

$sheet->mergeCells(
    "A{$row}:H{$row}"
);

$sheet->setCellValue(
    "A{$row}",
    $t['title']
);

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
                'rgb' => '343A40'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER
        ]
    ]);

$row++;

/* رؤوس الأعمدة */

$headers = [

    '#',

    $t['driver'],

    $t['plate'],

    $t['oil'],

    $t['tires'],

    $t['maintenance'],

    $t['total'],

    $lang === 'ar'
        ? 'ملاحظات'
        : 'Notes'
];

foreach ($headers as $i => $header) {

    $sheet->setCellValue(
        chr(65 + $i) . $row,
        $header
    );
}

$headerRow = $row;

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

/* البيانات */

if (empty($rows)) {

    $sheet->mergeCells(
        "A{$row}:H{$row}"
    );

    $sheet->setCellValue(
        "A{$row}",
        $t['no_data']
    );

    $row++;

} else {

    $counter = 1;

    foreach ($rows as $item) {

        $sheet->setCellValue(
            "A{$row}",
            $counter++
        );

        $sheet->setCellValue(
            "B{$row}",
            $item['name'] ?? '-'
        );

        $sheet->setCellValue(
            "C{$row}",
            $item['plate_number'] ?? '-'
        );

        $sheet->setCellValue(
            "D{$row}",
            (float)($item['oil'] ?? 0)
        );

        $sheet->setCellValue(
            "E{$row}",
            (float)($item['tires'] ?? 0)
        );

        $sheet->setCellValue(
            "F{$row}",
            (float)($item['maintenance'] ?? 0)
        );

        $sheet->setCellValue(
            "G{$row}",
            (float)($item['total'] ?? 0)
        );

        $sheet->setCellValue(
            "H{$row}",
            ''
        );

        $row++;
    }
}

/* =========================================================
   تنسيق الجدول
========================================================= */

$lastDataRow = $row - 1;

$sheet
    ->getStyle(
        "A{$headerRow}:H{$lastDataRow}"
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

if (!empty($rows)) {

    $sheet
        ->getStyle(
            "D" .
            ($headerRow + 1) .
            ":G{$lastDataRow}"
        )
        ->getNumberFormat()
        ->setFormatCode(
            '#,##0.00'
        );
}

/* =========================================================
   ملخص الإجماليات
========================================================= */

$row += 2;

$summaryHeaders = [

    $t['total_oil'],
    $t['total_tires'],
    $t['total_maintenance'],
    $t['grand_total']

];

$summaryValues = [

    $totalOil,
    $totalTires,
    $totalMaintenance,
    $totalGrand

];

for ($i = 0; $i < 4; $i++) {

    $col = chr(
        65 + ($i * 2)
    );

    $nextCol = chr(
        66 + ($i * 2)
    );

    $sheet->mergeCells(
        "{$col}{$row}:{$nextCol}{$row}"
    );

    $sheet->setCellValue(
        "{$col}{$row}",
        $summaryHeaders[$i]
    );

    $row++;

    $sheet->mergeCells(
        "{$col}{$row}:{$nextCol}{$row}"
    );

    $sheet->setCellValue(
        "{$col}{$row}",
        $summaryValues[$i]
    );

    $sheet
        ->getStyle(
            "{$col}{$row}:{$nextCol}{$row}"
        )
        ->getNumberFormat()
        ->setFormatCode(
            '#,##0.00'
        );

    $row++;
}

$sheet
    ->getStyle(
        "A" . ($row - 8) .
        ":H" . ($row - 1)
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

/* =========================================================
   أحجام الأعمدة
========================================================= */

$widths = [

    'A' => 8,
    'B' => 28,
    'C' => 20,
    'D' => 18,
    'E' => 18,
    'F' => 20,
    'G' => 20,
    'H' => 25
];

foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}

/* =========================================================
   إعدادات الصفحة
========================================================= */

$sheet->freezePane('A9');

$sheet
    ->getPageSetup()
    ->setOrientation(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
    );

$sheet
    ->getPageSetup()
    ->setPaperSize(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
    );

$sheet
    ->getPageMargins()
    ->setTop(0.5)
    ->setBottom(0.5)
    ->setLeft(0.5)
    ->setRight(0.5);

/* =========================================================
   اسم الملف
========================================================= */

$filename =
    'drivers_cost_report_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';

/* =========================================================
   إخراج Excel
========================================================= */

while (ob_get_level()) {
    ob_end_clean();
}

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

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;

