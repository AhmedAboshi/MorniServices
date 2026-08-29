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
   الفلاتر - نفس drivers_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$work_area = trim($_GET['work_area'] ?? '');

$truck_type = trim($_GET['truck_type'] ?? '');

$document_status = trim(
    $_GET['document_status'] ?? ''
);

/* =========================================================
   التاريخ
========================================================= */

$today = date('Y-m-d');

$nearDate = date(
    'Y-m-d',
    strtotime('+30 days')
);

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title' => 'تقرير السائقين',

        'company_report' =>
            'تقرير بيانات السائقين والوثائق',

        'id' => '#',

        'image' => 'الصورة',

        'name' => 'اسم السائق',

        'national_id' => 'الهوية',

        'phone' => 'الجوال',

        'work_area' => 'منطقة العمل',

        'truck_type' => 'نوع السطحة',

        'iqama' => 'الإقامة',

        'license' => 'الرخصة',

        'card' => 'بطاقة السائق',

        'status' => 'الحالة',

        'expired' => 'منتهية',

        'valid' => 'سارية',

        'multiple' =>
            'أكثر من وثيقة منتهية',

        'filters' =>
            'الفلاتر المستخدمة',

        'search_filter' =>
            'البحث',

        'area_filter' =>
            'منطقة العمل',

        'type_filter' =>
            'نوع السطحة',

        'status_filter' =>
            'حالة الوثائق',

        'all_records' =>
            'جميع السجلات',

        'all_status' =>
            'جميع الحالات',

        'near' =>
            'قريبة من الانتهاء',

        'total_drivers' =>
            'إجمالي السائقين',

        'iqama_expired' =>
            'الإقامات المنتهية',

        'license_expired' =>
            'الرخص المنتهية',

        'card_expired' =>
            'البطاقات المنتهية',

        'multi_expired' =>
            'أكثر من وثيقة منتهية',

        'generated_at' =>
            'تاريخ إنشاء التقرير',

        'no_data' =>
            'لا توجد سجلات مطابقة للفلاتر'

    ],

    'en' => [

        'title' => 'Drivers Report',

        'company_report' =>
            'Driver Data and Document Report',

        'id' => '#',

        'image' => 'Image',

        'name' => 'Driver Name',

        'national_id' =>
            'National ID',

        'phone' => 'Phone',

        'work_area' =>
            'Work Area',

        'truck_type' =>
            'Truck Type',

        'iqama' => 'Iqama',

        'license' =>
            'License',

        'card' =>
            'Driver Card',

        'status' =>
            'Status',

        'expired' =>
            'Expired',

        'valid' =>
            'Valid',

        'multiple' =>
            'Multiple Expired Documents',

        'filters' =>
            'Applied Filters',

        'search_filter' =>
            'Search',

        'area_filter' =>
            'Work Area',

        'type_filter' =>
            'Truck Type',

        'status_filter' =>
            'Document Status',

        'all_records' =>
            'All Records',

        'all_status' =>
            'All Statuses',

        'near' =>
            'Near Expiry',

        'total_drivers' =>
            'Total Drivers',

        'iqama_expired' =>
            'Expired Iqamas',

        'license_expired' =>
            'Expired Licenses',

        'card_expired' =>
            'Expired Driver Cards',

        'multi_expired' =>
            'Multiple Expired Documents',

        'generated_at' =>
            'Generated At',

        'no_data' =>
            'No records match the selected filters'

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
            d.name LIKE ?
            OR d.national_id LIKE ?
            OR d.phone LIKE ?
            OR d.work_area LIKE ?
            OR d.truck_type LIKE ?
        )
    ";

    $value =
        '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sssss";
}

/* =========================================================
   منطقة العمل
========================================================= */

if ($work_area !== '') {

    $where .= "
        AND d.work_area LIKE ?
    ";

    $params[] =
        '%' . $work_area . '%';

    $types .= "s";
}

/* =========================================================
   نوع السطحة
========================================================= */

if ($truck_type !== '') {

    $where .= "
        AND d.truck_type LIKE ?
    ";

    $params[] =
        '%' . $truck_type . '%';

    $types .= "s";
}

/* =========================================================
   حالة الوثائق
========================================================= */

if ($document_status === 'expired') {

    $where .= "
        AND (
            (
                d.iqama_expiry_date IS NOT NULL
                AND d.iqama_expiry_date <> ''
                AND d.iqama_expiry_date < ?
            )
            OR
            (
                d.license_expiry_date IS NOT NULL
                AND d.license_expiry_date <> ''
                AND d.license_expiry_date < ?
            )
            OR
            (
                d.driver_card_expiration_date IS NOT NULL
                AND d.driver_card_expiration_date <> ''
                AND d.driver_card_expiration_date < ?
            )
        )
    ";

    $params[] = $today;
    $params[] = $today;
    $params[] = $today;

    $types .= "sss";

} elseif ($document_status === 'valid') {

    $where .= "
        AND (
            d.iqama_expiry_date IS NULL
            OR d.iqama_expiry_date = ''
            OR d.iqama_expiry_date >= ?
        )
        AND (
            d.license_expiry_date IS NULL
            OR d.license_expiry_date = ''
            OR d.license_expiry_date >= ?
        )
        AND (
            d.driver_card_expiration_date IS NULL
            OR d.driver_card_expiration_date = ''
            OR d.driver_card_expiration_date >= ?
        )
    ";

    $params[] = $today;
    $params[] = $today;
    $params[] = $today;

    $types .= "sss";

} elseif ($document_status === 'near') {

    $where .= "
        AND (
            (
                d.iqama_expiry_date IS NOT NULL
                AND d.iqama_expiry_date >= ?
                AND d.iqama_expiry_date <= ?
            )
            OR
            (
                d.license_expiry_date IS NOT NULL
                AND d.license_expiry_date >= ?
                AND d.license_expiry_date <= ?
            )
            OR
            (
                d.driver_card_expiration_date IS NOT NULL
                AND d.driver_card_expiration_date >= ?
                AND d.driver_card_expiration_date <= ?
            )
        )
    ";

    $params[] = $today;
    $params[] = $nearDate;

    $params[] = $today;
    $params[] = $nearDate;

    $params[] = $today;
    $params[] = $nearDate;

    $types .= "ssssss";
}

/* =========================================================
   SQL
========================================================= */

$sql = "

    SELECT

        d.id,
        d.name,
        d.national_id,
        d.phone,
        d.work_area,
        d.truck_type,
        d.imagedriver,

        d.iqama_expiry_date,
        d.license_expiry_date,
        d.driver_card_expiration_date

    FROM drivers d

    $where

    ORDER BY
        d.name ASC
";

/* =========================================================
   Prepare
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        "SQL Error: " .
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
            "Filter parameters mismatch."
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
        "Execute Error: " .
        htmlspecialchars($stmt->error)
    );
}

$result =
    $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$iqamaExpiredCount = 0;

$licenseExpiredCount = 0;

$cardExpiredCount = 0;

$multiExpiredCount = 0;

while (
    $row =
    $result->fetch_assoc()
) {

    $iqamaExpired =
        !empty(
            $row['iqama_expiry_date']
        )
        &&
        $row['iqama_expiry_date']
        < $today;

    $licenseExpired =
        !empty(
            $row['license_expiry_date']
        )
        &&
        $row['license_expiry_date']
        < $today;

    $cardExpired =
        !empty(
            $row['driver_card_expiration_date']
        )
        &&
        $row['driver_card_expiration_date']
        < $today;

    $expiredCount =
        ($iqamaExpired ? 1 : 0)
        +
        ($licenseExpired ? 1 : 0)
        +
        ($cardExpired ? 1 : 0);

    if ($iqamaExpired) {
        $iqamaExpiredCount++;
    }

    if ($licenseExpired) {
        $licenseExpiredCount++;
    }

    if ($cardExpired) {
        $cardExpiredCount++;
    }

    if ($expiredCount >= 2) {
        $multiExpiredCount++;
    }

    $row['iqama_expired'] =
        $iqamaExpired;

    $row['license_expired'] =
        $licenseExpired;

    $row['card_expired'] =
        $cardExpired;

    $row['expired_count'] =
        $expiredCount;

    if ($expiredCount >= 2) {

        $row['status_text'] =
            $t['multiple'];

        $row['status_color'] =
            '6F42C1';

    } elseif ($iqamaExpired) {

        $row['status_text'] =
            $t['iqama'];

        $row['status_color'] =
            'DC3545';

    } elseif ($licenseExpired) {

        $row['status_text'] =
            $t['license'];

        $row['status_color'] =
            'FD7E14';

    } elseif ($cardExpired) {

        $row['status_text'] =
            $t['card'];

        $row['status_color'] =
            '0D6EFD';

    } else {

        $row['status_text'] =
            $t['valid'];

        $row['status_color'] =
            '198754';
    }

    $rows[] =
        $row;
}

$totalDrivers =
    count($rows);

/* =========================================================
   إعدادات الشركة
========================================================= */

$settingsData = [];

$settingsResult =
    $con->query("
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
   Spreadsheet
========================================================= */

$spreadsheet =
    new Spreadsheet();

$sheet =
    $spreadsheet
        ->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير السائقين'
        : 'Drivers Report'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   شعار الشركة
========================================================= */

$logoPath = '';

if ($companyLogo !== '') {

    $candidates = [

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
        $candidates
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
        'I1'
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
    'A1:I1'
);

$sheet->setCellValue(
    'A1',
    $companyName
);

$sheet->mergeCells(
    'A2:I2'
);

$sheet->setCellValue(
    'A2',
    $t['company_report']
);

$sheet->mergeCells(
    'A3:I3'
);

$sheet->setCellValue(
    'A3',
    $t['generated_at'] .
    ': ' .
    date('Y-m-d H:i')
);

/* =========================================================
   تنسيق الرأس
========================================================= */

$sheet
    ->getStyle('A1:I1')
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
    ->getStyle('A2:I2')
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
    ->getStyle('A3:I3')
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

if ($search !== '') {

    $filterText[] =
        $t['search_filter'] .
        ': ' .
        $search;
}

if ($work_area !== '') {

    $filterText[] =
        $t['area_filter'] .
        ': ' .
        $work_area;
}

if ($truck_type !== '') {

    $filterText[] =
        $t['type_filter'] .
        ': ' .
        $truck_type;
}

if ($document_status !== '') {

    if ($document_status === 'expired') {

        $statusLabel =
            $t['expired'];

    } elseif (
        $document_status === 'near'
    ) {

        $statusLabel =
            $t['near'];

    } elseif (
        $document_status === 'valid'
    ) {

        $statusLabel =
            $t['valid'];

    } else {

        $statusLabel =
            $document_status;
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

/* =========================================================
   سطر الفلاتر
========================================================= */

$sheet->mergeCells(
    'A4:I4'
);

$sheet->setCellValue(
    'A4',
    $filterString
);

$sheet
    ->getStyle('A4:I4')
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

/* =========================================================
   الملخص
========================================================= */

$sheet->setCellValue(
    'A5',
    $t['total_drivers']
);

$sheet->setCellValue(
    'B5',
    $totalDrivers
);

$sheet->setCellValue(
    'C5',
    $t['iqama_expired']
);

$sheet->setCellValue(
    'D5',
    $iqamaExpiredCount
);

$sheet->setCellValue(
    'E5',
    $t['license_expired']
);

$sheet->setCellValue(
    'F5',
    $licenseExpiredCount
);

$sheet->setCellValue(
    'G5',
    $t['card_expired']
);

$sheet->setCellValue(
    'H5',
    $cardExpiredCount
);

$sheet->setCellValue(
    'I5',
    $multiExpiredCount
);

$sheet
    ->getStyle('A5:I5')
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

/* =========================================================
   رؤوس الأعمدة
========================================================= */

$headers = [

    'A7' => $t['id'],

    'B7' => $t['image'],

    'C7' => $t['name'],

    'D7' => $t['national_id'],

    'E7' => $t['phone'],

    'F7' => $t['work_area'],

    'G7' => $t['truck_type'],

    'H7' => $t['documents'],

    'I7' => $t['status']

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
    ->getStyle('A7:I7')
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
    ->getRowDimension(7)
    ->setRowHeight(30);

/* =========================================================
   البيانات
========================================================= */

$rowNumber = 8;

$counter = 1;

if (empty($rows)) {

    $sheet->mergeCells(
        'A8:I8'
    );

    $sheet->setCellValue(
        'A8',
        $t['no_data']
    );

    $sheet
        ->getStyle('A8:I8')
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
           الرقم
        ================================================= */

        $sheet->setCellValue(
            "A{$rowNumber}",
            $counter
        );

        /* =================================================
           الصورة
        ================================================= */

        $imageFile = '';

        $imageName =
            trim(
                (string)(
                    $row['imagedriver']
                    ?? ''
                )
            );

        if ($imageName !== '') {

            $candidates = [

                __DIR__ .
                '/../uploads/' .
                basename($imageName),

                __DIR__ .
                '/../uploads/drivers/' .
                basename($imageName),

                __DIR__ .
                '/../uploads/driver/' .
                basename($imageName),

                __DIR__ .
                '/../fleetimg/img/' .
                basename($imageName)

            ];

            foreach (
                $candidates
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

                    $imageFile =
                        $real;

                    break;
                }
            }
        }

        $sheet
            ->getRowDimension(
                $rowNumber
            )
            ->setRowHeight(65);

        if ($imageFile !== '') {

            $drawing =
                new Drawing();

            $drawing->setName(
                'Driver Image ' .
                $counter
            );

            $drawing->setDescription(
                $row['name'] ?? 'Driver'
            );

            $drawing->setPath(
                $imageFile
            );

            $drawing->setHeight(55);

            $drawing->setCoordinates(
                "B{$rowNumber}"
            );

            $drawing->setOffsetX(5);

            $drawing->setOffsetY(5);

            $drawing->setWorksheet(
                $sheet
            );

        } else {

            $sheet->setCellValue(
                "B{$rowNumber}",
                '-'
            );
        }

        /* =================================================
           الاسم
        ================================================= */

        $sheet->setCellValue(
            "C{$rowNumber}",
            $row['name'] ?? '-'
        );

        /* =================================================
           الهوية
        ================================================= */

        $sheet->setCellValue(
            "D{$rowNumber}",
            $row['national_id'] ?? '-'
        );

        /* =================================================
           الجوال
        ================================================= */

        $sheet->setCellValue(
            "E{$rowNumber}",
            $row['phone'] ?? '-'
        );

        /* =================================================
           المنطقة
        ================================================= */

        $sheet->setCellValue(
            "F{$rowNumber}",
            $row['work_area'] ?? '-'
        );

        /* =================================================
           النوع
        ================================================= */

        $sheet->setCellValue(
            "G{$rowNumber}",
            $row['truck_type'] ?? '-'
        );

        /* =================================================
           الوثائق - كل وثيقة مستقلة
        ================================================= */

        $iqamaStatus =
            $row['iqama_expired']
                ? $t['expired']
                : $t['valid'];

        $licenseStatus =
            $row['license_expired']
                ? $t['expired']
                : $t['valid'];

        $cardStatus =
            $row['card_expired']
                ? $t['expired']
                : $t['valid'];

        $documentsText =
            $t['iqama'] .
            ': ' .
            ($row['iqama_expiry_date'] ?? '-') .
            ' - ' .
            $iqamaStatus .
            "\n" .

            $t['license'] .
            ': ' .
            ($row['license_expiry_date'] ?? '-') .
            ' - ' .
            $licenseStatus .
            "\n" .

            $t['card'] .
            ': ' .
            ($row['driver_card_expiration_date'] ?? '-') .
            ' - ' .
            $cardStatus;

        $sheet->setCellValue(
            "H{$rowNumber}",
            $documentsText
        );

        /* =================================================
           الحالة العامة
        ================================================= */

        $sheet->setCellValue(
            "I{$rowNumber}",
            $row['status_text']
        );

        /* =================================================
           التنسيق
        ================================================= */

        $sheet
            ->getStyle(
                "A{$rowNumber}:I{$rowNumber}"
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

        /* =================================================
           تلوين حالة الوثائق
        ================================================= */

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
                $row['status_color']
            );

        /* =================================================
           تلوين الوثائق داخل الخلية
        ================================================= */

        $sheet
            ->getStyle(
                "H{$rowNumber}"
            )
            ->getFont()
            ->setBold(false);

        /*
         * إذا كانت هناك وثائق منتهية،
         * نغير لون النص بشكل عام للتنبيه.
         */

        if (
            $row['expired_count'] >= 2
        ) {

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'F3E5F5'
                );

        } elseif (
            $row['iqama_expired']
        ) {

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'FDECEC'
                );

        } elseif (
            $row['license_expired']
        ) {

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'FFF2E8'
                );

        } elseif (
            $row['card_expired']
        ) {

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "H{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'EAF2FF'
                );
        }

        $rowNumber++;

        $counter++;
    }
}

/* =========================================================
   الأحجام
========================================================= */

$widths = [

    'A' => 8,

    'B' => 18,

    'C' => 25,

    'D' => 20,

    'E' => 18,

    'F' => 23,

    'G' => 20,

    'H' => 55,

    'I' => 24

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
    "A7:I{$lastDataRow}"
);

/* =========================================================
   إعداد الصفحة
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
        "A1:I{$rowNumber}"
    );

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'drivers_report_' .
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