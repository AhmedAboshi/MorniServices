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
   الفلاتر - نفس reportfleet.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$driver_id = (int)($_GET['driver_id'] ?? 0);

$plate = trim($_GET['plate'] ?? '');

$work = trim($_GET['work'] ?? '');

$status_filter = trim($_GET['status'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'report_title'      => 'تقرير المركبات',
        'company_report'    => 'تقرير الأسطول والمركبات',

        'id'                => '#',
        'image'             => 'الصورة',
        'driver'            => 'السائق',
        'plate'             => 'رقم اللوحة',
        'type'              => 'النوع',
        'model'             => 'الموديل',
        'color'             => 'اللون',
        'city'              => 'المدينة',

        'inspection'        => 'الفحص',
        'insurance'         => 'التأمين',
        'operation'         => 'كرت التشغيل',

        'status'            => 'الحالة',

        'healthy'           => 'سليم',
        'inspection_expired'=> 'الفحص منتهي',
        'insurance_expired' => 'التأمين منتهي',
        'operation_expired' => 'كرت التشغيل منتهي',

        'filters'           => 'الفلاتر المستخدمة',
        'search_filter'     => 'البحث',
        'driver_filter'    => 'السائق',
        'plate_filter'      => 'اللوحة',
        'city_filter'       => 'المدينة',
        'status_filter'     => 'الحالة',

        'all_records'       => 'جميع السجلات',

        'total'             => 'إجمالي المركبات',
        'healthy_total'     => 'المركبات السليمة',
        'inspection_total'  => 'فحص منتهي',
        'insurance_total'   => 'تأمين منتهي',
        'operation_total'   => 'تشغيل منتهي',

        'no_data'           => 'لا توجد مركبات مطابقة للفلاتر المحددة',

        'unknown'           => 'غير محدد'

    ],

    'en' => [

        'report_title'      => 'Fleet Report',
        'company_report'    => 'Fleet & Vehicle Report',

        'id'                => '#',
        'image'             => 'Image',
        'driver'            => 'Driver',
        'plate'             => 'Plate Number',
        'type'              => 'Type',
        'model'             => 'Model',
        'color'             => 'Color',
        'city'              => 'City',

        'inspection'        => 'Inspection',
        'insurance'         => 'Insurance',
        'operation'         => 'Operation Card',

        'status'            => 'Status',

        'healthy'           => 'Healthy',
        'inspection_expired'=> 'Inspection Expired',
        'insurance_expired' => 'Insurance Expired',
        'operation_expired' => 'Operation Expired',

        'filters'           => 'Applied Filters',
        'search_filter'     => 'Search',
        'driver_filter'    => 'Driver',
        'plate_filter'     => 'Plate',
        'city_filter'       => 'City',
        'status_filter'     => 'Status',

        'all_records'       => 'All Records',

        'total'             => 'Total Vehicles',
        'healthy_total'     => 'Healthy Vehicles',
        'inspection_total'  => 'Inspection Expired',
        'insurance_total'   => 'Insurance Expired',
        'operation_total'   => 'Operation Expired',

        'no_data'           => 'No vehicles match the selected filters',

        'unknown'           => 'Unknown'

    ]

];

$t = $text[$lang];

/* =========================================================
   بناء الفلاتر
========================================================= */

$where = " WHERE 1=1 ";

$params = [];

$types = "";

/* =========================================================
   البحث العام
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR f.driver LIKE ?
            OR f.model LIKE ?
            OR f.typefleet LIKE ?
            OR f.classify LIKE ?
            OR f.colorfleet LIKE ?
            OR f.work LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sssssss";
}

/* =========================================================
   فلتر السائق
========================================================= */

if ($driver_id > 0) {

    $driverName = '';

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

        $driverRow = $driverStmt
            ->get_result()
            ->fetch_assoc();

        if ($driverRow) {
            $driverName = trim($driverRow['name']);
        }

        $driverStmt->close();
    }

    if ($driverName !== '') {

        $where .= "
            AND f.driver LIKE ?
        ";

        $params[] = '%' . $driverName . '%';

        $types .= "s";

    } else {

        $where .= " AND 1=0 ";
    }
}

/* =========================================================
   فلتر اللوحة
========================================================= */

if ($plate !== '') {

    $where .= "
        AND f.plate LIKE ?
    ";

    $params[] = '%' . $plate . '%';

    $types .= "s";
}

/* =========================================================
   فلتر المدينة
========================================================= */

if ($work !== '') {

    $where .= "
        AND f.work LIKE ?
    ";

    $params[] = '%' . $work . '%';

    $types .= "s";
}

/* =========================================================
   فلتر الحالة
========================================================= */

switch ($status_filter) {

    case 'healthy':

        $where .= "
            AND (
                f.inspection_expiry IS NULL
                OR f.inspection_expiry >= CURDATE()
            )

            AND (
                f.insurance_expiration_date IS NULL
                OR f.insurance_expiration_date >= CURDATE()
            )

            AND (
                f.operation_expiry IS NULL
                OR f.operation_expiry >= CURDATE()
            )
        ";

        break;


    case 'inspection_expired':

        $where .= "
            AND f.inspection_expiry IS NOT NULL
            AND f.inspection_expiry < CURDATE()
        ";

        break;


    case 'insurance_expired':

        $where .= "
            AND f.insurance_expiration_date IS NOT NULL
            AND f.insurance_expiration_date < CURDATE()
        ";

        break;


    case 'operation_expired':

        $where .= "
            AND f.operation_expiry IS NOT NULL
            AND f.operation_expiry < CURDATE()
        ";

        break;
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "
    SELECT
        f.*
    FROM fleet f

    $where

    ORDER BY
        f.id DESC
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

    if (strlen($types) !== count($params)) {

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

    while ($setting = $settingsResult->fetch_assoc()) {

        $settingsData[
            $setting['setting_key']
        ] = $setting['setting_value'];
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

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير المركبات'
        : 'Fleet Report'
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

    foreach ($logoCandidates as $candidate) {

        $real = realpath($candidate);

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

    $drawing = new Drawing();

    $drawing->setName('Company Logo');

    $drawing->setDescription(
        $companyName
    );

    $drawing->setPath(
        $logoPath
    );

    $drawing->setHeight(60);

    $drawing->setCoordinates('L1');

    $drawing->setOffsetX(5);

    $drawing->setOffsetY(5);

    $drawing->setWorksheet(
        $sheet
    );
}

/* =========================================================
   العناوين
========================================================= */

$sheet->mergeCells('A1:L1');

$sheet->setCellValue(
    'A1',
    $companyName
);

$sheet->mergeCells('A2:L2');

$sheet->setCellValue(
    'A2',
    $t['company_report']
);

$sheet->mergeCells('A3:L3');

$sheet->setCellValue(
    'A3',
    date('Y-m-d H:i')
);

$sheet->mergeCells('A4:L4');

$sheet->setCellValue(
    'A4',
    ''
);

/* =========================================================
   تنسيق العنوان
========================================================= */

$sheet->getStyle('A1:L3')->applyFromArray([

    'alignment' => [

        'horizontal' =>
            Alignment::HORIZONTAL_CENTER,

        'vertical' =>
            Alignment::VERTICAL_CENTER
    ]

]);

$sheet->getStyle('A1:L1')->applyFromArray([

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
            'rgb' => '2563EB'
        ]
    ]
]);

$sheet->getStyle('A2:L2')->applyFromArray([

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
    ]
]);

$sheet->getStyle('A3:L3')->applyFromArray([

    'font' => [

        'size' => 10,

        'color' => [
            'rgb' => '666666'
        ]
    ]
]);

$sheet
    ->getRowDimension(1)
    ->setRowHeight(35);

$sheet
    ->getRowDimension(2)
    ->setRowHeight(30);

$sheet
    ->getRowDimension(3)
    ->setRowHeight(22);

/* =========================================================
   عرض الفلاتر
========================================================= */

$filterText = [];

if ($search !== '') {

    $filterText[] =
        $t['search_filter'] .
        ': ' .
        $search;
}

if ($driver_id > 0) {

    $driverName = '';

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

        $driverData =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        if ($driverData) {
            $driverName =
                $driverData['name'];
        }

        $driverStmt->close();
    }

    if ($driverName !== '') {

        $filterText[] =
            $t['driver_filter'] .
            ': ' .
            $driverName;
    }
}

if ($plate !== '') {

    $filterText[] =
        $t['plate_filter'] .
        ': ' .
        $plate;
}

if ($work !== '') {

    $filterText[] =
        $t['city_filter'] .
        ': ' .
        $work;
}

if ($status_filter !== '') {

    $statusLabel =
        $status_filter;

    if ($status_filter === 'healthy') {

        $statusLabel =
            $t['healthy'];

    } elseif (
        $status_filter ===
        'inspection_expired'
    ) {

        $statusLabel =
            $t['inspection_expired'];

    } elseif (
        $status_filter ===
        'insurance_expired'
    ) {

        $statusLabel =
            $t['insurance_expired'];

    } elseif (
        $status_filter ===
        'operation_expired'
    ) {

        $statusLabel =
            $t['operation_expired'];
    }

    $filterText[] =
        $t['status_filter'] .
        ': ' .
        $statusLabel;
}

$filterString =
    !empty($filterText)
        ? implode(
            ' | ',
            $filterText
        )
        : $t['all_records'];

$sheet->mergeCells('A4:L4');

$sheet->setCellValue(
    'A4',
    $filterString
);

$sheet->getStyle('A4:L4')->applyFromArray([

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
    ],

    'fill' => [

        'fillType' =>
            Fill::FILL_SOLID,

        'startColor' => [
            'rgb' => 'F1F5F9'
        ]
    ]
]);

$sheet
    ->getRowDimension(4)
    ->setRowHeight(25);

/* =========================================================
   رؤوس الأعمدة
========================================================= */

$headers = [

    'A5' => $t['id'],

    'B5' => $t['image'],

    'C5' => $t['driver'],

    'D5' => $t['plate'],

    'E5' => $t['type'],

    'F5' => $t['model'],

    'G5' => $t['color'],

    'H5' => $t['city'],

    'I5' => $t['inspection'],

    'J5' => $t['insurance'],

    'K5' => $t['operation'],

    'L5' => $t['status']

];

foreach ($headers as $cell => $value) {

    $sheet->setCellValue(
        $cell,
        $value
    );
}

$sheet->getStyle('A5:L5')->applyFromArray([

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
    ->getRowDimension(5)
    ->setRowHeight(28);

/* =========================================================
   البيانات
========================================================= */

$rowNumber = 6;

$counter = 1;

$totalExported = 0;

$healthyCount = 0;

$inspectionExpiredCount = 0;

$insuranceExpiredCount = 0;

$operationExpiredCount = 0;

while ($row = $result->fetch_assoc()) {

    /* =====================================================
       حالات الوثائق
    ===================================================== */

    $inspectionExpired =
        !empty(
            $row['inspection_expiry']
        )
        &&
        $row['inspection_expiry']
        < date('Y-m-d');

    $insuranceExpired =
        !empty(
            $row['insurance_expiration_date']
        )
        &&
        $row['insurance_expiration_date']
        < date('Y-m-d');

    $operationExpired =
        !empty(
            $row['operation_expiry']
        )
        &&
        $row['operation_expiry']
        < date('Y-m-d');

    if ($inspectionExpired) {
        $inspectionExpiredCount++;
    }

    if ($insuranceExpired) {
        $insuranceExpiredCount++;
    }

    if ($operationExpired) {
        $operationExpiredCount++;
    }

    if (
        !$inspectionExpired
        &&
        !$insuranceExpired
        &&
        !$operationExpired
    ) {

        $healthyCount++;

        $statusText =
            $t['healthy'];

        $statusColor =
            '198754';

    } elseif ($inspectionExpired) {

        $statusText =
            $t['inspection_expired'];

        $statusColor =
            'DC3545';

    } elseif ($insuranceExpired) {

        $statusText =
            $t['insurance_expired'];

        $statusColor =
            'F59E0B';

    } else {

        $statusText =
            $t['operation_expired'];

        $statusColor =
            'DC3545';
    }

    /* =====================================================
       ارتفاع الصف
    ===================================================== */

    $sheet
        ->getRowDimension($rowNumber)
        ->setRowHeight(72);

    /* =====================================================
       ID
    ===================================================== */

    $sheet->setCellValue(
        "A{$rowNumber}",
        $counter
    );

    /* =====================================================
       صورة المركبة
    ===================================================== */

    $imageFile = '';

    $imageName =
        trim(
            (string)(
                $row['imgfleet'] ?? ''
            )
        );

    if ($imageName !== '') {

        $cleanName =
            ltrim(
                str_replace(
                    '\\',
                    '/',
                    $imageName
                ),
                '/'
            );

        /*
         * نفس مسار الصور المستخدم
         * في reportfleet.php
         */

        $possibleImages = [

            __DIR__ .
            '/../fleetimg/img/' .
            basename($cleanName),

            __DIR__ .
            '/../uploads/fleet/' .
            basename($cleanName),

            __DIR__ .
            '/../uploads/' .
            basename($cleanName),

            __DIR__ .
            '/../' .
            $cleanName,

            __DIR__ .
            '/uploads/fleet/' .
            basename($cleanName)

        ];

        foreach ($possibleImages as $possibleImage) {

            $realImage =
                realpath(
                    $possibleImage
                );

            if (
                $realImage !== false
                &&
                is_file($realImage)
            ) {

                $imageFile =
                    $realImage;

                break;
            }
        }
    }

    if ($imageFile !== '') {

        $drawing = new Drawing();

        $drawing->setName(
            'Vehicle Image ' .
            $row['id']
        );

        $drawing->setDescription(
            $row['plate'] ?? ''
        );

        $drawing->setPath(
            $imageFile
        );

        $drawing->setHeight(60);

        $drawing->setCoordinates(
            "B{$rowNumber}"
        );

        $drawing->setOffsetX(5);

        $drawing->setOffsetY(5);

        $drawing->setWorksheet(
            $sheet
        );
    }

    /* =====================================================
       السائق
    ===================================================== */

    $sheet->setCellValue(
        "C{$rowNumber}",
        $row['driver'] ?? '-'
    );

    /* =====================================================
       اللوحة
    ===================================================== */

    $sheet->setCellValue(
        "D{$rowNumber}",
        $row['plate'] ?? '-'
    );

    /* =====================================================
       النوع
    ===================================================== */

    $sheet->setCellValue(
        "E{$rowNumber}",
        $row['typefleet'] ?? '-'
    );

    /* =====================================================
       الموديل
    ===================================================== */

    $sheet->setCellValue(
        "F{$rowNumber}",
        $row['model'] ?? '-'
    );

    /* =====================================================
       اللون
    ===================================================== */

    $sheet->setCellValue(
        "G{$rowNumber}",
        $row['colorfleet'] ?? '-'
    );

    /* =====================================================
       المدينة
    ===================================================== */

    $sheet->setCellValue(
        "H{$rowNumber}",
        $row['work'] ?? '-'
    );

    /* =====================================================
       الفحص
    ===================================================== */

    $sheet->setCellValue(
        "I{$rowNumber}",
        $row['inspection_expiry'] ?? '-'
    );

    /* =====================================================
       التأمين
    ===================================================== */

    $sheet->setCellValue(
        "J{$rowNumber}",
        $row['insurance_expiration_date'] ?? '-'
    );

    /* =====================================================
       التشغيل
    ===================================================== */

    $sheet->setCellValue(
        "K{$rowNumber}",
        $row['operation_expiry'] ?? '-'
    );

    /* =====================================================
       الحالة
    ===================================================== */

    $sheet->setCellValue(
        "L{$rowNumber}",
        $statusText
    );

    $sheet
        ->getStyle("L{$rowNumber}")
        ->getFont()
        ->setBold(true);

    $sheet
        ->getStyle("L{$rowNumber}")
        ->getFont()
        ->getColor()
        ->setRGB(
            $statusColor
        );

    /* =====================================================
       تلوين الوثائق المنتهية
    ===================================================== */

    if ($inspectionExpired) {

        $sheet
            ->getStyle(
                "I{$rowNumber}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "I{$rowNumber}"
            )
            ->getFont()
            ->getColor()
            ->setRGB(
                'DC3545'
            );
    }

    if ($insuranceExpired) {

        $sheet
            ->getStyle(
                "J{$rowNumber}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "J{$rowNumber}"
            )
            ->getFont()
            ->getColor()
            ->setRGB(
                'DC3545'
            );
    }

    if ($operationExpired) {

        $sheet
            ->getStyle(
                "K{$rowNumber}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "K{$rowNumber}"
            )
            ->getFont()
            ->getColor()
            ->setRGB(
                'DC3545'
            );
    }

    $rowNumber++;

    $counter++;

    $totalExported++;
}

/* =========================================================
   عدم وجود بيانات
========================================================= */

if ($totalExported === 0) {

    $sheet->mergeCells(
        'A6:L6'
    );

    $sheet->setCellValue(
        'A6',
        $t['no_data']
    );

    $sheet->getStyle(
        'A6:L6'
    )->applyFromArray([

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

    $rowNumber = 7;
}

/* =========================================================
   ملخص
========================================================= */

$summaryRow =
    $rowNumber + 1;

$sheet->mergeCells(
    "A{$summaryRow}:B{$summaryRow}"
);

$sheet->setCellValue(
    "A{$summaryRow}",
    $t['total'] .
    ': ' .
    $totalExported
);

$sheet->setCellValue(
    "C{$summaryRow}",
    $t['healthy_total'] .
    ': ' .
    $healthyCount
);

$sheet->setCellValue(
    "D{$summaryRow}",
    $t['inspection_total'] .
    ': ' .
    $inspectionExpiredCount
);

$sheet->setCellValue(
    "E{$summaryRow}",
    $t['insurance_total'] .
    ': ' .
    $insuranceExpiredCount
);

$sheet->setCellValue(
    "F{$summaryRow}",
    $t['operation_total'] .
    ': ' .
    $operationExpiredCount
);

$sheet->getStyle(
    "A{$summaryRow}:F{$summaryRow}"
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

/* =========================================================
   تنسيق الجدول
========================================================= */

$lastDataRow =
    max(
        5,
        $rowNumber - 1
    );

$sheet
    ->getStyle(
        "A5:L{$lastDataRow}"
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

/* =========================================================
   عرض الأعمدة
========================================================= */

$widths = [

    'A' => 8,
    'B' => 18,
    'C' => 25,
    'D' => 18,
    'E' => 18,
    'F' => 18,
    'G' => 15,
    'H' => 20,
    'I' => 18,
    'J' => 20,
    'K' => 20,
    'L' => 22

];

foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}

/* =========================================================
   تجميد
========================================================= */

$sheet->freezePane(
    'A6'
);

/* =========================================================
   Auto Filter
========================================================= */

$sheet->setAutoFilter(
    "A5:L{$lastDataRow}"
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
        5
    );

$sheet
    ->getPageSetup()
    ->setPrintArea(
        "A1:L{$summaryRow}"
    );

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'reportfleet_' .
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

$writer = new Xlsx(
    $spreadsheet
);

$writer->save(
    'php://output'
);

$stmt->close();

exit;