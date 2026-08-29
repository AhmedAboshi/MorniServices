<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
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
   الفلاتر - نفس oile_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$date_filter = $_GET['date_filter'] ?? 'all';

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

$plate = trim($_GET['plate'] ?? '');

$driver = trim($_GET['driver'] ?? '');

$oil_type = trim($_GET['oil_type'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'          => 'تقرير تغيير الزيت',
        'company_report' => 'تقرير تغيير زيت المركبات',

        'id'             => '#',
        'plate'          => 'رقم اللوحة',
        'model'          => 'الموديل',
        'driver'         => 'السائق',
        'oil_type'       => 'نوع الزيت',
        'date'           => 'تاريخ التغيير',
        'km_change'      => 'عداد التغيير',
        'current_km'     => 'العداد الحالي',
        'next_km'        => 'العداد القادم',
        'next_change'    => 'التغيير القادم',
        'cost'           => 'التكلفة',
        'notes'          => 'الملاحظات',
        'status'         => 'الحالة',

        'good'           => 'ممتاز',
        'soon'           => 'قريب',
        'late'           => 'متأخر',

        'filters'        => 'الفلاتر المستخدمة',
        'search_filter'  => 'البحث',
        'plate_filter'   => 'اللوحة',
        'driver_filter'  => 'السائق',
        'oil_filter'     => 'نوع الزيت',
        'period_filter'  => 'الفترة',
        'from_filter'    => 'من',
        'to_filter'      => 'إلى',

        'all'            => 'الكل',
        'week'           => 'آخر 7 أيام',
        'month'          => 'آخر 30 يوم',
        'custom'         => 'مخصص',

        'total'          => 'إجمالي السجلات',
        'total_cost'     => 'إجمالي التكلفة',
        'average_cost'   => 'متوسط التكلفة',
        'vehicles'       => 'المركبات',

        'overdue'        => 'متأخر',
        'urgent'         => 'قريب جداً',
        'good_total'     => 'ممتاز',

        'no_data'        => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'sar'            => 'ريال',

        'generated_at'   => 'تاريخ إنشاء التقرير'

    ],

    'en' => [

        'title'          => 'Oil Change Report',
        'company_report' => 'Vehicle Oil Change Report',

        'id'             => '#',
        'plate'          => 'Plate',
        'model'          => 'Model',
        'driver'         => 'Driver',
        'oil_type'       => 'Oil Type',
        'date'           => 'Change Date',
        'km_change'      => 'Change KM',
        'current_km'     => 'Current KM',
        'next_km'        => 'Next KM',
        'next_change'    => 'Next Change',
        'cost'           => 'Cost',
        'notes'          => 'Notes',
        'status'         => 'Status',

        'good'           => 'Good',
        'soon'           => 'Soon',
        'late'           => 'Overdue',

        'filters'        => 'Applied Filters',
        'search_filter'  => 'Search',
        'plate_filter'   => 'Plate',
        'driver_filter'  => 'Driver',
        'oil_filter'     => 'Oil Type',
        'period_filter'  => 'Period',
        'from_filter'    => 'From',
        'to_filter'      => 'To',

        'all'            => 'All',
        'week'           => 'Last 7 Days',
        'month'          => 'Last 30 Days',
        'custom'         => 'Custom',

        'total'          => 'Total Records',
        'total_cost'     => 'Total Cost',
        'average_cost'   => 'Average Cost',
        'vehicles'       => 'Vehicles',

        'overdue'        => 'Overdue',
        'urgent'         => 'Urgent',
        'good_total'     => 'Good',

        'no_data'        => 'No records match the selected filters',

        'sar'            => 'SAR',

        'generated_at'   => 'Generated At'

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
   البحث
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR f.model LIKE ?
            OR d.name LIKE ?
            OR o.driver LIKE ?
            OR o.oil_type LIKE ?
            OR o.notes LIKE ?
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
   اللوحة
========================================================= */

if ($plate !== '') {

    $where .= "
        AND f.plate LIKE ?
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
        AND (
            d.name LIKE ?
            OR o.driver LIKE ?
        )
    ";

    $driverValue =
        '%' . $driver . '%';

    $params[] = $driverValue;
    $params[] = $driverValue;

    $types .= "ss";
}

/* =========================================================
   نوع الزيت
========================================================= */

if ($oil_type !== '') {

    $where .= "
        AND o.oil_type LIKE ?
    ";

    $params[] =
        '%' . $oil_type . '%';

    $types .= "s";
}

/* =========================================================
   الفترة
========================================================= */

switch ($date_filter) {

    case 'week':

        $where .= "
            AND o.change_date >=
            DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ";

        break;

    case 'month':

        $where .= "
            AND o.change_date >=
            DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ";

        break;

    case 'custom':

        if ($from !== '' && $to !== '') {

            $where .= "
                AND DATE(o.change_date)
                BETWEEN ? AND ?
            ";

            $params[] = $from;
            $params[] = $to;

            $types .= "ss";
        }

        break;
}

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

        f.plate,
        f.model,

        COALESCE(
            NULLIF(d.name, ''),
            NULLIF(o.driver, ''),
            '-'
        ) AS driver_name

    FROM oil_changes o

    LEFT JOIN fleet f
        ON f.id = o.car_id

    LEFT JOIN drivers d
        ON d.id = o.driver_id

    $where

    ORDER BY
        o.change_date DESC,
        o.id DESC

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
   البيانات والإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

$vehicleIds = [];

$overdue = 0;

$urgent = 0;

$good = 0;

while ($row = $result->fetch_assoc()) {

    $cost =
        (float)(
            $row['cost'] ?? 0
        );

    $totalCost += $cost;

    $rows[] = $row;

    if (!empty($row['car_id'])) {

        $vehicleIds[
            $row['car_id']
        ] = true;
    }

    /* حالة الزيت */

    $currentKm =
        (int)(
            $row['current_km']
            ??
            $row['km_change']
            ??
            0
        );

    $nextKm =
        (int)(
            $row['next_km']
            ??
            0
        );

    if ($nextKm > 0) {

        $remainingKm =
            $nextKm - $currentKm;

        if ($remainingKm <= 0) {

            $row['status_text'] =
                $t['late'];

            $row['status_color'] =
                'DC3545';

            $overdue++;

        } elseif ($remainingKm <= 1000) {

            $row['status_text'] =
                $t['soon'];

            $row['status_color'] =
                'F59E0B';

            $urgent++;

        } else {

            $row['status_text'] =
                $t['good'];

            $row['status_color'] =
                '198754';

            $good++;
        }

    } else {

        $row['status_text'] =
            $t['good'];

        $row['status_color'] =
            '198754';

        $good++;
    }

    $rows[count($rows) - 1] = $row;
}

$totalRecords =
    count($rows);

$totalVehicles =
    count($vehicleIds);

$averageCost =
    $totalRecords > 0
        ? $totalCost /
          $totalRecords
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
   إنشاء Excel
========================================================= */

$spreadsheet =
    new Spreadsheet();

$sheet =
    $spreadsheet
        ->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير تغيير الزيت'
        : 'Oil Report'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   شعار الشركة
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

            $logoPath =
                $real;

            break;
        }
    }
}

if ($logoPath !== '') {

    $drawing =
        new Drawing();

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
        'M1'
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
    'A1:M1'
);

$sheet->setCellValue(
    'A1',
    $companyName
);

$sheet->mergeCells(
    'A2:M2'
);

$sheet->setCellValue(
    'A2',
    $t['company_report']
);

$sheet->mergeCells(
    'A3:M3'
);

$sheet->setCellValue(
    'A3',
    $t['generated_at'] .
    ': ' .
    date('Y-m-d H:i')
);

/* =========================================================
   تنسيق العنوان
========================================================= */

$sheet
    ->getStyle('A1:M1')
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
    ->getStyle('A2:M2')
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
    ->getStyle('A3:M3')
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
   الفلاتر المستخدمة
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

/* الزيت */

if ($oil_type !== '') {

    $filterText[] =
        $t['oil_filter'] .
        ': ' .
        $oil_type;
}

/* الفترة */

if ($date_filter !== 'all') {

    $periodLabel =
        $date_filter;

    if ($date_filter === 'week') {

        $periodLabel =
            $t['week'];

    } elseif ($date_filter === 'month') {

        $periodLabel =
            $t['month'];

    } elseif ($date_filter === 'custom') {

        $periodLabel =
            $t['custom'];
    }

    $filterText[] =
        $t['period_filter'] .
        ': ' .
        $periodLabel;
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
        : $t['all'];

/* =========================================================
   عرض الفلاتر
========================================================= */

$sheet->mergeCells(
    'A4:M4'
);

$sheet->setCellValue(
    'A4',
    $filterString
);

$sheet
    ->getStyle('A4:M4')
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
    ->setRowHeight(28);

/* =========================================================
   ملخص
========================================================= */

$sheet->setCellValue(
    'A5',
    $t['total']
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

$sheet->setCellValue(
    'G5',
    $t['vehicles']
);

$sheet->setCellValue(
    'H5',
    $totalVehicles
);

$sheet->setCellValue(
    'I5',
    $t['overdue']
);

$sheet->setCellValue(
    'J5',
    $overdue
);

$sheet->setCellValue(
    'K5',
    $t['urgent']
);

$sheet->setCellValue(
    'L5',
    $urgent
);

$sheet->setCellValue(
    'M5',
    $good
);

$sheet
    ->getStyle('A5:M5')
    ->applyFromArray([

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
   رؤوس الجدول
========================================================= */

$headers = [

    'A7' => $t['id'],

    'B7' => $t['plate'],

    'C7' => $t['model'],

    'D7' => $t['driver'],

    'E7' => $t['oil_type'],

    'F7' => $t['date'],

    'G7' => $t['km_change'],

    'H7' => $t['current_km'],

    'I7' => $t['next_km'],

    'J7' => $t['next_change'],

    'K7' => $t['cost'],

    'L7' => $t['status'],

    'M7' => $t['notes']

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
    ->getStyle('A7:M7')
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
    ->setRowHeight(30);

/* =========================================================
   البيانات
========================================================= */

$rowNumber = 8;

$counter = 1;

if (empty($rows)) {

    $sheet->mergeCells(
        'A8:M8'
    );

    $sheet->setCellValue(
        'A8',
        $t['no_data']
    );

    $sheet
        ->getStyle('A8:M8')
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

    foreach (
        $rows
        as $row
    ) {

        $sheet->setCellValue(
            "A{$rowNumber}",
            $counter
        );

        $sheet->setCellValue(
            "B{$rowNumber}",
            $row['plate'] ?? '-'
        );

        $sheet->setCellValue(
            "C{$rowNumber}",
            $row['model'] ?? '-'
        );

        $sheet->setCellValue(
            "D{$rowNumber}",
            $row['driver_name'] ?? '-'
        );

        $sheet->setCellValue(
            "E{$rowNumber}",
            $row['oil_type'] ?? '-'
        );

        $sheet->setCellValue(
            "F{$rowNumber}",
            $row['change_date'] ?? ''
        );

        $sheet->setCellValue(
            "G{$rowNumber}",
            (int)(
                $row['km_change'] ?? 0
            )
        );

        $sheet->setCellValue(
            "H{$rowNumber}",
            (int)(
                $row['current_km']
                ??
                $row['km_change']
                ??
                0
            )
        );

        $sheet->setCellValue(
            "I{$rowNumber}",
            (int)(
                $row['next_km']
                ?? 0
            )
        );

        $sheet->setCellValue(
            "J{$rowNumber}",
            $row['next_change'] ?? ''
        );

        $sheet->setCellValue(
            "K{$rowNumber}",
            (float)(
                $row['cost'] ?? 0
            )
        );

        $sheet->setCellValue(
            "L{$rowNumber}",
            $row['status_text'] ?? $t['good']
        );

        $sheet->setCellValue(
            "M{$rowNumber}",
            $row['notes'] ?? ''
        );

        /* ---------------------------------------------
           تنسيق الصف
        --------------------------------------------- */

        $sheet
            ->getStyle(
                "A{$rowNumber}:M{$rowNumber}"
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

        /* تكلفة */

        $sheet
            ->getStyle(
                "K{$rowNumber}"
            )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );

        /* العدادات */

        $sheet
            ->getStyle(
                "G{$rowNumber}:I{$rowNumber}"
            )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0'
            );

        /* ---------------------------------------------
           لون الحالة
        --------------------------------------------- */

        $statusColor =
            $row['status_color']
            ?? '198754';

        $sheet
            ->getStyle(
                "L{$rowNumber}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "L{$rowNumber}"
            )
            ->getFont()
            ->getColor()
            ->setRGB(
                $statusColor
            );

        /* ---------------------------------------------
           تمييز الصف
        --------------------------------------------- */

        if (
            ($row['status_text'] ?? '')
            === $t['late']
        ) {

            $sheet
                ->getStyle(
                    "A{$rowNumber}:M{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "A{$rowNumber}:M{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'FFF1F2'
                );

        } elseif (
            ($row['status_text'] ?? '')
            === $t['soon']
        ) {

            $sheet
                ->getStyle(
                    "A{$rowNumber}:M{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "A{$rowNumber}:M{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'FFF8E1'
                );
        }

        $rowNumber++;

        $counter++;
    }
}

/* =========================================================
   إجمالي أسفل التقرير
========================================================= */

$totalRow =
    $rowNumber + 1;

$sheet->mergeCells(
    "A{$totalRow}:J{$totalRow}"
);

$sheet->setCellValue(
    "A{$totalRow}",
    $t['total_cost']
);

$sheet->setCellValue(
    "K{$totalRow}",
    $totalCost
);

$sheet->setCellValue(
    "L{$totalRow}",
    $t['average_cost']
);

$sheet->setCellValue(
    "M{$totalRow}",
    $averageCost
);

$sheet
    ->getStyle(
        "A{$totalRow}:M{$totalRow}"
    )
    ->applyFromArray([

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
        "K{$totalRow}"
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

$sheet
    ->getStyle(
        "M{$totalRow}"
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   عرض الأعمدة
========================================================= */

$widths = [

    'A' => 8,

    'B' => 18,

    'C' => 20,

    'D' => 25,

    'E' => 22,

    'F' => 18,

    'G' => 16,

    'H' => 16,

    'I' => 16,

    'J' => 18,

    'K' => 16,

    'L' => 15,

    'M' => 40

];

foreach (
    $widths
    as $column => $width
) {

    $sheet
        ->getColumnDimension(
            $column
        )
        ->setWidth(
            $width
        );
}

/* =========================================================
   تجميد
========================================================= */

$sheet->freezePane(
    'A8'
);

/* =========================================================
   AutoFilter
========================================================= */

$lastDataRow =
    max(
        7,
        $rowNumber - 1
    );

$sheet->setAutoFilter(
    "A7:M{$lastDataRow}"
);

/* =========================================================
   إعداد الطباعة
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
        "A1:M{$totalRow}"
    );

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'oile_report_' .
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
   إخراج الملف
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