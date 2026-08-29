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
   الفلاتر - نفس tires_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

$car_id = (int)($_GET['car_id'] ?? 0);

$driver_id = (int)($_GET['driver_id'] ?? 0);

$tire_type = trim($_GET['tire_type'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'            => 'تقرير الإطارات',

        'company_report'   => 'تقرير إطارات المركبات',

        'id'               => '#',

        'vehicle'          => 'المركبة',

        'plate'            => 'رقم اللوحة',

        'model'            => 'الموديل',

        'driver'           => 'السائق',

        'type'             => 'نوع الإطار',

        'change_date'      => 'تاريخ التركيب',

        'next_change'      => 'التغيير القادم',

        'current_km'       => 'العداد الحالي',

        'next_km'          => 'العداد القادم',

        'remaining'        => 'المتبقي',

        'cost'             => 'التكلفة',

        'notes'            => 'الملاحظات',

        'status'           => 'الحالة',

        'good'             => 'ممتاز',

        'soon'             => 'قريب',

        'late'             => 'متأخر',

        'expired'          => 'منتهي',

        'day'              => 'يوم',

        'filters'          => 'الفلاتر المستخدمة',

        'search_filter'    => 'البحث',

        'car_filter'       => 'المركبة',

        'driver_filter'    => 'السائق',

        'type_filter'      => 'نوع الإطار',

        'from_filter'      => 'من',

        'to_filter'        => 'إلى',

        'all_records'      => 'جميع السجلات',

        'total_records'    => 'إجمالي السجلات',

        'total_cost'       => 'إجمالي التكلفة',

        'average_cost'     => 'متوسط التكلفة',

        'total_cars'       => 'عدد المركبات',

        'good_total'       => 'ممتاز',

        'soon_total'       => 'قريب',

        'late_total'       => 'متأخر',

        'no_data'          => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'sar'              => 'ريال',

        'generated_at'     => 'تاريخ إنشاء التقرير'

    ],

    'en' => [

        'title'            => 'Tire Report',

        'company_report'   => 'Vehicle Tire Report',

        'id'               => '#',

        'vehicle'          => 'Vehicle',

        'plate'            => 'Plate Number',

        'model'            => 'Model',

        'driver'           => 'Driver',

        'type'             => 'Tire Type',

        'change_date'      => 'Install Date',

        'next_change'      => 'Next Change',

        'current_km'       => 'Current KM',

        'next_km'          => 'Next KM',

        'remaining'        => 'Remaining',

        'cost'             => 'Cost',

        'notes'            => 'Notes',

        'status'           => 'Status',

        'good'             => 'Good',

        'soon'             => 'Soon',

        'late'             => 'Overdue',

        'expired'          => 'Expired',

        'day'              => 'Days',

        'filters'          => 'Applied Filters',

        'search_filter'    => 'Search',

        'car_filter'       => 'Vehicle',

        'driver_filter'    => 'Driver',

        'type_filter'      => 'Tire Type',

        'from_filter'      => 'From',

        'to_filter'        => 'To',

        'all_records'      => 'All Records',

        'total_records'    => 'Total Records',

        'total_cost'       => 'Total Cost',

        'average_cost'     => 'Average Cost',

        'total_cars'       => 'Vehicles',

        'good_total'       => 'Good',

        'soon_total'       => 'Soon',

        'late_total'       => 'Overdue',

        'no_data'          => 'No tire records match the selected filters',

        'sar'              => 'SAR',

        'generated_at'     => 'Generated At'

    ]

];

$t = $text[$lang];

/* =========================================================
   WHERE
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
            OR t.tire_type LIKE ?
            OR t.notes LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sssss";
}

/* =========================================================
   المركبة
========================================================= */

if ($car_id > 0) {

    $where .= "
        AND t.car_id = ?
    ";

    $params[] = $car_id;

    $types .= "i";
}

/* =========================================================
   السائق
========================================================= */

if ($driver_id > 0) {

    $where .= "
        AND t.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}

/* =========================================================
   نوع الإطار
========================================================= */

if ($tire_type !== '') {

    $where .= "
        AND t.tire_type LIKE ?
    ";

    $params[] =
        '%' . $tire_type . '%';

    $types .= "s";
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND DATE(t.change_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND DATE(t.change_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        t.id,
        t.car_id,
        t.driver_id,
        t.tire_type,
        t.change_date,
        t.next_change,
        t.current_km,
        t.next_km,
        t.cost,
        t.notes,

        f.plate,
        f.model,

        COALESCE(
            NULLIF(d.name, ''),
            '-'
        ) AS driver_name

    FROM tires t

    LEFT JOIN fleet f
        ON f.id = t.car_id

    LEFT JOIN drivers d
        ON d.id = t.driver_id

    $where

    ORDER BY
        t.change_date DESC,
        t.id DESC

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
        strlen($types) !== count($params)
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

$good = 0;

$soon = 0;

$late = 0;

while ($row = $result->fetch_assoc()) {

    $cost =
        (float)(
            $row['cost'] ?? 0
        );

    $totalCost += $cost;

    if (!empty($row['car_id'])) {

        $vehicleIds[
            $row['car_id']
        ] = true;
    }

    /* =====================================================
       حالة الإطار
    ===================================================== */

    $days = null;

    $statusText = $t['good'];

    $statusColor = '198754';

    if (!empty($row['next_change'])) {

        $nextDate =
            strtotime(
                $row['next_change']
            );

        if ($nextDate !== false) {

            $days = (int)ceil(
                (
                    $nextDate -
                    strtotime(
                        date('Y-m-d')
                    )
                ) / 86400
            );

            if ($days < 0) {

                $statusText =
                    $t['late'];

                $statusColor =
                    'DC3545';

                $late++;

            } elseif (
                $days <= 30
            ) {

                $statusText =
                    $t['soon'];

                $statusColor =
                    'F59E0B';

                $soon++;

            } else {

                $statusText =
                    $t['good'];

                $statusColor =
                    '198754';

                $good++;
            }

        } else {

            $good++;
        }

    } else {

        /*
         * عند عدم وجود تاريخ قادم،
         * نستخدم العداد.
         */

        $currentKm =
            (int)(
                $row['current_km'] ?? 0
            );

        $nextKm =
            (int)(
                $row['next_km'] ?? 0
            );

        if ($nextKm > 0) {

            $remainingKm =
                $nextKm -
                $currentKm;

            if (
                $remainingKm <= 0
            ) {

                $statusText =
                    $t['late'];

                $statusColor =
                    'DC3545';

                $late++;

            } elseif (
                $remainingKm <= 1000
            ) {

                $statusText =
                    $t['soon'];

                $statusColor =
                    'F59E0B';

                $soon++;

            } else {

                $statusText =
                    $t['good'];

                $statusColor =
                    '198754';

                $good++;
            }

        } else {

            $good++;
        }
    }

    $row['days'] =
        $days;

    $row['status_text'] =
        $statusText;

    $row['status_color'] =
        $statusColor;

    $rows[] = $row;
}

$totalRecords =
    count($rows);

$totalCars =
    count($vehicleIds);

$averageCost =
    $totalRecords > 0
        ? $totalCost /
          $totalRecords
        : 0;

/* =========================================================
   جلب اسم المركبة والسائق للفلاتر
========================================================= */

$selectedCarPlate = '';

if ($car_id > 0) {

    $carStmt = $con->prepare("
        SELECT plate
        FROM fleet
        WHERE id = ?
        LIMIT 1
    ");

    if ($carStmt) {

        $carStmt->bind_param(
            "i",
            $car_id
        );

        $carStmt->execute();

        $carRow =
            $carStmt
                ->get_result()
                ->fetch_assoc();

        if ($carRow) {

            $selectedCarPlate =
                $carRow['plate'];
        }

        $carStmt->close();
    }
}

$selectedDriverName = '';

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

            $selectedDriverName =
                $driverRow['name'];
        }

        $driverStmt->close();
    }
}

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
    ??
    'AlSharqPlatform';

$companyLogo =
    $settingsData['company_logo']
    ??
    '';

/* =========================================================
   إنشاء Spreadsheet
========================================================= */

$spreadsheet =
    new Spreadsheet();

$sheet =
    $spreadsheet
        ->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير الإطارات'
        : 'Tire Report'
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
        'L1'
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
    'A1:L1'
);

$sheet->setCellValue(
    'A1',
    $companyName
);

$sheet->mergeCells(
    'A2:L2'
);

$sheet->setCellValue(
    'A2',
    $t['company_report']
);

$sheet->mergeCells(
    'A3:L3'
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
    ->getStyle('A1:L1')
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
    ->getStyle('A2:L2')
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
    ->getStyle('A3:L3')
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

/* المركبة */

if ($selectedCarPlate !== '') {

    $filterText[] =
        $t['car_filter'] .
        ': ' .
        $selectedCarPlate;
}

/* السائق */

if ($selectedDriverName !== '') {

    $filterText[] =
        $t['driver_filter'] .
        ': ' .
        $selectedDriverName;
}

/* نوع الإطار */

if ($tire_type !== '') {

    $filterText[] =
        $t['type_filter'] .
        ': ' .
        $tire_type;
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

/* =========================================================
   عرض الفلاتر
========================================================= */

$sheet->mergeCells(
    'A4:L4'
);

$sheet->setCellValue(
    'A4',
    $filterString
);

$sheet
    ->getStyle('A4:L4')
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

$sheet->setCellValue(
    'G5',
    $t['total_cars']
);

$sheet->setCellValue(
    'H5',
    $totalCars
);

$sheet->setCellValue(
    'I5',
    $t['good_total']
);

$sheet->setCellValue(
    'J5',
    $good
);

$sheet->setCellValue(
    'K5',
    $t['soon_total']
);

$sheet->setCellValue(
    'L5',
    $soon
);

$sheet
    ->getStyle('A5:L5')
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

    'B7' => $t['vehicle'],

    'C7' => $t['plate'],

    'D7' => $t['driver'],

    'E7' => $t['type'],

    'F7' => $t['change_date'],

    'G7' => $t['current_km'],

    'H7' => $t['next_km'],

    'I7' => $t['remaining'],

    'J7' => $t['next_change'],

    'K7' => $t['cost'],

    'L7' => $t['status']

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
    ->getStyle('A7:L7')
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
        'A8:L8'
    );

    $sheet->setCellValue(
        'A8',
        $t['no_data']
    );

    $sheet
        ->getStyle('A8:L8')
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

        /* =================================================
           القيم
        ================================================= */

        $currentKm =
            (int)(
                $row['current_km'] ?? 0
            );

        $nextKm =
            (int)(
                $row['next_km'] ?? 0
            );

        $remainingKm =
            $nextKm > 0
                ? $nextKm - $currentKm
                : null;

        /* =================================================
           البيانات
        ================================================= */

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
            $row['tire_type'] ?? '-'
        );

        $sheet->setCellValue(
            "F{$rowNumber}",
            $row['change_date'] ?? ''
        );

        $sheet->setCellValue(
            "G{$rowNumber}",
            $currentKm
        );

        $sheet->setCellValue(
            "H{$rowNumber}",
            $nextKm
        );

        if ($remainingKm !== null) {

            $sheet->setCellValue(
                "I{$rowNumber}",
                $remainingKm
            );

        } else {

            $sheet->setCellValue(
                "I{$rowNumber}",
                '-'
            );
        }

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

        /* =================================================
           تنسيق الصف
        ================================================= */

        $sheet
            ->getStyle(
                "A{$rowNumber}:L{$rowNumber}"
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

        /* العدادات */

        $sheet
            ->getStyle(
                "G{$rowNumber}:I{$rowNumber}"
            )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0'
            );

        /* التكلفة */

        $sheet
            ->getStyle(
                "K{$rowNumber}"
            )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );

        /* =================================================
           لون الحالة
        ================================================= */

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

        /* =================================================
           تلوين الصف
        ================================================= */

        if (
            ($row['status_text'] ?? '')
            === $t['late']
        ) {

            $sheet
                ->getStyle(
                    "A{$rowNumber}:L{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "A{$rowNumber}:L{$rowNumber}"
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
                    "A{$rowNumber}:L{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "A{$rowNumber}:L{$rowNumber}"
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
   إجمالي التكلفة
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
    $averageCost
);

$sheet
    ->getStyle(
        "A{$totalRow}:L{$totalRow}"
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
        "L{$totalRow}"
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

    'L' => 15

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
   Auto Filter
========================================================= */

$lastDataRow =
    max(
        7,
        $rowNumber - 1
    );

$sheet->setAutoFilter(
    "A7:L{$lastDataRow}"
);

/* =========================================================
   إعدادات الصفحة
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
        "A1:L{$totalRow}"
    );

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'tires_report_' .
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
   إنشاء الملف
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